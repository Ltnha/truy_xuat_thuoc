<?php

namespace App\Jobs;

use App\Models\BlockchainIdentity;
use App\Services\ChuoiCungUng\TonKhoService;
use App\Models\BlockchainTransaction;
use App\Models\ChuyenGiao;
use App\Models\NhatKyHeThong;
use App\Services\Blockchain\SignerClient;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/** Gửi transaction transfer lên Sepolia; txHash chỉ nhận từ receipt Signer. */
class ProcessBatchTransferJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $backoff = [10, 30, 60, 120];

    public function __construct(
        public int $chuyenGiaoId,
        public int $userId,
        public ?int $blockchainTransactionId = null,
        public string $eventName = 'TransferSubmitted',
        public string $eventType = 'TRANSFER_BATCH'
    ) {
    }

    public function handle(SignerClient $signer): void
    {
        $cg = ChuyenGiao::with(['loThuoc', 'benGui', 'benNhan'])->findOrFail($this->chuyenGiaoId);
        if (! $cg->loThuoc || ! $cg->benGui || ! $cg->benNhan) {
            throw new Exception("Chuyển giao #{$this->chuyenGiaoId} thiếu thông tin lô hoặc tổ chức.");
        }

        // Tương thích job cũ trong queue (thiếu blockchainTransactionId)
        $txId = $this->blockchainTransactionId;
        if (! $txId) {
            $txId = BlockchainTransaction::query()
                ->where('chuyenGiaoId', $cg->id)
                ->where('eventName', $this->eventName)
                ->orderByDesc('id')
                ->value('id');
        }
        if (! $txId) {
            throw new Exception("Không tìm thấy blockchainTransaction cho CG #{$cg->id} / {$this->eventName}.");
        }

        $transaction = BlockchainTransaction::query()
            ->whereKey($txId)
            ->where('chuyenGiaoId', $cg->id)
            ->firstOrFail();

        if (is_string($transaction->txHash) && preg_match('/^0x[a-fA-F0-9]{64}$/', $transaction->txHash)
            && strtoupper((string) $transaction->trangThai) === 'CONFIRMED') {
            return;
        }

        $actionMap = [
            'TransferSubmitted' => 'submitTransfer',
            'TransferReceived' => 'receiveTransfer',
            'TransferRejected' => 'rejectTransfer',
            'TransferCancelled' => 'cancelTransfer',
        ];
        $action = $actionMap[$this->eventName] ?? null;
        if (! $action) {
            throw new Exception('Sự kiện chuyển giao không được hỗ trợ: ' . $this->eventName);
        }

        $actorOrgId = in_array($action, ['submitTransfer', 'cancelTransfer'], true)
            ? (int) $cg->benGuiId
            : (int) $cg->benNhanId;
        $identity = BlockchainIdentity::query()
            ->where('toChucId', $actorOrgId)
            ->where('trangThai', 'ACTIVE')
            ->first();
        if (! $identity || ! is_string($identity->signerKeyId) || $identity->signerKeyId === ''
            || ! is_string($identity->diaChiVi) || $identity->diaChiVi === '') {
            throw new Exception("Tổ chức #{$actorOrgId} chưa có ví Blockchain ACTIVE. Không thể ký {$action}.");
        }

        // Phải dùng CÙNG thuật toán hash khi submit và khi đối soát
        $maChuyenGiaoBytes32 = '0x' . hash('sha256', (string) $cg->maChuyenGiao);
        $params = ['maChuyenGiao' => $maChuyenGiaoBytes32];
        if ($action === 'submitTransfer') {
            $identityNhan = BlockchainIdentity::query()
                ->where('toChucId', $cg->benNhanId)
                ->where('trangThai', 'ACTIVE')
                ->first();
            if (! $identityNhan || ! is_string($identityNhan->diaChiVi) || $identityNhan->diaChiVi === '') {
                throw new Exception('Tổ chức nhận chưa có ví Blockchain ACTIVE.');
            }
            $params['benNhan'] = $identityNhan->diaChiVi;
        } elseif (in_array($action, ['rejectTransfer', 'cancelTransfer'], true)) {
            $params['lyDo'] = (string) ($cg->lyDoTuChoi ?? ($action === 'cancelTransfer' ? 'Bên gửi hủy chuyển giao' : 'Từ chối tiếp nhận'));
        }

        // Đối soát là bắt buộc trước retry. Nếu RPC lỗi, throw để retry sau;
        // tuyệt đối không gửi transaction mới khi chưa biết transaction cũ đã mined chưa.
        $eventCheck = $signer->findTransferEvent($maChuyenGiaoBytes32, $this->eventName);
        $txHash = null;
        $blockNumber = null;
        if (! empty($eventCheck['isRecorded'])) {
            $txHash = $eventCheck['txHash'] ?? null;
            $blockNumber = $eventCheck['blockNumber'] ?? null;
            if (! is_string($txHash) || ! preg_match('/^0x[a-fA-F0-9]{64}$/', $txHash)) {
                throw new Exception('Event đã có trên chain nhưng không truy xuất được transaction hash thật.');
            }
        }

        if (empty($eventCheck['isRecorded'])) {
            $signer->ensureGas((string) $identity->signerKeyId);
            $result = $signer->tx((string) $identity->signerKeyId, $action, $params);
            if (empty($result['ok']) || empty($result['txHash'])) {
                throw new Exception('Signer không gửi được ' . $action . ': ' . ($result['reason'] ?? 'Không có txHash.'));
            }
            $txHash = $result['txHash'];
            $blockNumber = $result['blockNumber'] ?? null;
        }

        if (! is_string($txHash) || ! preg_match('/^0x[a-fA-F0-9]{64}$/', $txHash)) {
            throw new Exception('Signer không trả về Ethereum transaction hash hợp lệ.');
        }

        DB::transaction(function () use ($transaction, $txHash, $blockNumber, $cg): void {
            $transaction->update([
                'txHash' => $txHash,
                'blockNumber' => $blockNumber,
                'trangThai' => 'CONFIRMED',
                'thoiGian' => now(),
            ]);

            // Chỉ cập nhật nghiệp vụ và tồn kho sau khi receipt on-chain đã xác nhận.
            $lockedCg = ChuyenGiao::query()->whereKey($cg->id)->lockForUpdate()->firstOrFail();
            if ($this->eventName === 'TransferSubmitted'
                && (int) $cg->loThuoc->toChucId === (int) $cg->benGuiId
                && (string) ($cg->loThuoc->trangThai?->value ?? $cg->loThuoc->trangThai) === 'CREATED') {
                $cg->loThuoc->update(['trangThai' => \App\Enums\BatchStatus::inTransit->value]);
            }

            if ($this->eventName === 'TransferReceived' && $lockedCg->trangThai === \App\Enums\TransferStatus::pending) {
                $tonKho = app(TonKhoService::class);
                $tonKho->tru((int) $cg->loThuoc->id, (int) $cg->benGuiId, (int) $cg->soLuong);
                $tonKho->cong((int) $cg->loThuoc->id, (int) $cg->benNhanId, (int) $cg->soLuong);
                $lockedCg->update(['trangThai' => \App\Enums\TransferStatus::received, 'thoiGianXacNhan' => now()]);
            } elseif ($this->eventName === 'TransferRejected' && $lockedCg->trangThai === \App\Enums\TransferStatus::pending) {
                $lockedCg->update(['trangThai' => \App\Enums\TransferStatus::rejected, 'thoiGianXacNhan' => now()]);
            } elseif ($this->eventName === 'TransferCancelled' && $lockedCg->trangThai === \App\Enums\TransferStatus::pending) {
                $lockedCg->update(['trangThai' => \App\Enums\TransferStatus::cancelled, 'thoiGianXacNhan' => now()]);
            }

            if (in_array($this->eventName, ['TransferRejected', 'TransferCancelled'], true)
                && (int) $cg->loThuoc->toChucId === (int) $cg->benGuiId) {
                $hasOtherPending = ChuyenGiao::query()
                    ->where('loThuocId', $cg->loThuoc->id)
                    ->where('benGuiId', $cg->benGuiId)
                    ->where('id', '!=', $cg->id)
                    ->where('trangThai', \App\Enums\TransferStatus::pending->value)
                    ->exists();
                $hasReceived = ChuyenGiao::query()
                    ->where('loThuocId', $cg->loThuoc->id)
                    ->where('benGuiId', $cg->benGuiId)
                    ->where('trangThai', \App\Enums\TransferStatus::received->value)
                    ->exists();

                if (! $hasOtherPending && ! $hasReceived
                    && (string) ($cg->loThuoc->trangThai?->value ?? $cg->loThuoc->trangThai) === \App\Enums\BatchStatus::inTransit->value) {
                    $cg->loThuoc->update(['trangThai' => \App\Enums\BatchStatus::created->value]);
                }
            }

            NhatKyHeThong::query()->create([
                'taiKhoanId' => $this->userId,
                'doiTuongId' => $cg->id,
                'hanhDong' => 'CHUYEN_GIAO_ONCHAIN',
                'ketQua' => 'THANH_CONG',
                'thoiGian' => now(),
                'chiTiet' => "{$this->eventName} CONFIRMED. TxHash: {$txHash}",
            ]);
        });

        Log::info("[ProcessBatchTransferJob] {$this->eventName} CG #{$cg->id} confirmed: {$txHash}");
    }

    public function failed(?\Throwable $exception): void
    {
        $txId = $this->blockchainTransactionId;
        if ($txId) {
            $transaction = BlockchainTransaction::query()->find($txId);
            if ($transaction && strtoupper((string) $transaction->trangThai) === 'PENDING') {
                $hash = (string) ($transaction->txHash ?? '');
                if (! preg_match('/^0x[a-fA-F0-9]{64}$/', $hash)) {
                    $transaction->update(['trangThai' => 'FAILED', 'thoiGian' => now()]);
                }
            }
        }
        Log::error('[ProcessBatchTransferJob] FAILED CG #' . $this->chuyenGiaoId . ' / ' . $this->eventName . ': ' . ($exception?->getMessage() ?? ''));
    }
}
