<?php

namespace App\Jobs;

use App\Models\BlockchainIdentity;
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
        ];
        $action = $actionMap[$this->eventName] ?? null;
        if (! $action) {
            throw new Exception('Sự kiện chuyển giao không được hỗ trợ: ' . $this->eventName);
        }

        $actorOrgId = $action === 'submitTransfer' ? (int) $cg->benGuiId : (int) $cg->benNhanId;
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
        } elseif ($action === 'rejectTransfer') {
            $params['lyDo'] = (string) ($cg->lyDoTuChoi ?? 'Từ chối tiếp nhận');
        }

        // Đối soát: truyền bytes32 (không truyền UUID thô — tránh lệch keccak vs sha256)
        // Lỗi RPC range → bỏ qua, tiếp tục gửi tx
        $txHash = null;
        $blockNumber = null;
        try {
            $eventCheck = $signer->findTransferEvent($maChuyenGiaoBytes32, $this->eventName);
            if (! empty($eventCheck['isRecorded'])) {
                $txHash = $eventCheck['txHash'] ?? null;
                $blockNumber = $eventCheck['blockNumber'] ?? null;
            }
        } catch (\Throwable $e) {
            Log::warning('[ProcessBatchTransferJob] findTransferEvent bỏ qua: ' . $e->getMessage());
        }

        if (! is_string($txHash) || ! preg_match('/^0x[a-fA-F0-9]{64}$/', $txHash)) {

            // Nạp Gas bằng địa chỉ ví Ethereum thực tế
            $signer->ensureGas((string) $identity->diaChiVi);

            // Ký giao dịch bằng signerKeyId
            $result = $signer->tx(
                (string) $identity->signerKeyId,
                $action,
                $params
            );
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
