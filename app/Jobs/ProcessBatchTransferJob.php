<?php

namespace App\Jobs;

use App\Enums\BatchStatus;
use App\Enums\TransferStatus;
use App\Models\BlockchainIdentity;
use App\Models\BlockchainTransaction;
use App\Models\ChuyenGiao;
use App\Models\NhatKyHeThong;
use App\Services\Blockchain\SignerClient;
use App\Services\ChuoiCungUng\TonKhoService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessBatchTransferJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [10, 30, 60, 120, 300];

    public function __construct(
        public int $chuyenGiaoId,
        public int $userId,
        public ?int $blockchainTransactionId = null,
        public string $eventName = 'TransferSubmitted',
        public string $eventType = 'TRANSFER_BATCH'
    ) {}

    public function handle(SignerClient $signer, TonKhoService $tonKhoService): void
    {
        $cg = ChuyenGiao::with(['loThuoc', 'benGui', 'benNhan'])->findOrFail($this->chuyenGiaoId);
        if (! $cg->loThuoc || ! $cg->benGui || ! $cg->benNhan) {
            throw new Exception("Chuyển giao #{$cg->id} thiếu lô thuốc hoặc tổ chức gửi/nhận.");
        }

        $transaction = $this->findTransaction($cg);
        if (
            is_string($transaction->txHash)
            && preg_match('/^0x[a-fA-F0-9]{64}$/', $transaction->txHash)
            && strtoupper((string) $transaction->trangThai) === 'CONFIRMED'
        ) {
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

        // Contract v2 xác thực msg.sender là chủ thể gửi/nhận thực tế.
        // Không được dùng admin wallet thay cho ví của tổ chức trong các action transfer.
        $actorOrgId = in_array($action, ['submitTransfer', 'cancelTransfer'], true)
            ? (int) $cg->benGuiId
            : (int) $cg->benNhanId;

        $actor = BlockchainIdentity::query()
            ->where('toChucId', $actorOrgId)
            ->where('trangThai', 'ACTIVE')
            ->whereNotNull('signerKeyId')
            ->whereNotNull('diaChiVi')
            ->first();

        if (! $actor || trim((string) $actor->signerKeyId) === '' || ! preg_match('/^0x[a-fA-F0-9]{40}$/', (string) $actor->diaChiVi)) {
            throw new Exception("Tổ chức #{$actorOrgId} chưa có ví Blockchain ACTIVE hợp lệ để ký {$action}.");
        }

        // ID phải khớp tuyệt đối với quy ước tạo batchId lúc registerBatch.
        $transferId = '0x' . hash('sha256', (string) $cg->maChuyenGiao);
        $batchId = '0x' . hash('sha256', 'BATCH_' . (string) $cg->loThuoc->maLo);

        $params = ['transferId' => $transferId];
        if ($action === 'submitTransfer') {
            $receiver = BlockchainIdentity::query()
                ->where('toChucId', $cg->benNhanId)
                ->where('trangThai', 'ACTIVE')
                ->whereNotNull('diaChiVi')
                ->first();

            if (! $receiver || ! preg_match('/^0x[a-fA-F0-9]{40}$/', (string) $receiver->diaChiVi)) {
                throw new Exception('Tổ chức nhận chưa có ví Blockchain ACTIVE hợp lệ.');
            }

            $params['batchId'] = $batchId;
            $params['receiver'] = $receiver->diaChiVi;
        } elseif ($action === 'rejectTransfer') {
            $reason = trim((string) ($cg->lyDoTuChoi ?? 'Từ chối tiếp nhận'));
            $params['reasonHash'] = '0x' . hash('sha256', $reason);
        }

        // Idempotency: chỉ nhận txHash thật từ log event đã mined.
        $txHash = null;
        $blockNumber = null;
        $blockTimestamp = null;
        try {
            $eventCheck = $signer->findTransferEvent($transferId, $this->eventName);
            if (! empty($eventCheck['isRecorded'])) {
                $candidate = $eventCheck['txHash'] ?? null;
                if (is_string($candidate) && preg_match('/^0x[a-fA-F0-9]{64}$/', $candidate)) {
                    $txHash = $candidate;
                    $blockNumber = isset($eventCheck['blockNumber']) ? (int) $eventCheck['blockNumber'] : null;
                    $blockTimestamp = isset($eventCheck['blockTimestamp']) ? (int) $eventCheck['blockTimestamp'] : null;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[ProcessBatchTransferJob] Chưa đối soát được event; hoãn retry để tránh gửi giao dịch trùng: ' . $e->getMessage());
            throw $e;
        }

        if (! $txHash) {
            $signer->ensureGas((string) $actor->diaChiVi);
            $result = $signer->tx((string) $actor->signerKeyId, $action, $params);

            if (empty($result['ok']) || empty($result['txHash'])) {
                throw new Exception('Signer không gửi được ' . $action . ': ' . ($result['reason'] ?? 'Không có txHash.'));
            }

            $txHash = (string) $result['txHash'];
            $blockNumber = isset($result['blockNumber']) ? (int) $result['blockNumber'] : null;
            $blockTimestamp = isset($result['blockTimestamp']) ? (int) $result['blockTimestamp'] : null;
        }

        if (! preg_match('/^0x[a-fA-F0-9]{64}$/', $txHash)) {
            throw new Exception('Signer không trả về Ethereum transaction hash hợp lệ.');
        }

        DB::transaction(function () use ($transaction, $txHash, $blockNumber, $blockTimestamp, $cg, $tonKhoService): void {
            $blockTime = $blockTimestamp ? \Illuminate\Support\Carbon::createFromTimestamp($blockTimestamp) : now();
            $transaction->update([
                'txHash' => $txHash,
                'blockNumber' => $blockNumber,
                'trangThai' => 'CONFIRMED',
                'thoiGian' => $blockTime,
            ]);

            // Chỉ đồng bộ trạng thái nghiệp vụ/tồn kho sau khi event tương ứng đã mined.
            $lockedCg = ChuyenGiao::query()->whereKey($cg->id)->lockForUpdate()->firstOrFail();
            $loThuoc = $lockedCg->loThuoc()->lockForUpdate()->firstOrFail();

            if ($this->eventName === 'TransferSubmitted') {
                $trangThaiLo = $loThuoc->trangThai instanceof BatchStatus
                    ? $loThuoc->trangThai
                    : BatchStatus::tryFrom((string) $loThuoc->trangThai);
                if ($trangThaiLo === BatchStatus::created) {
                    $loThuoc->update(['trangThai' => BatchStatus::inTransit->value]);
                }
            } elseif ($this->eventName === 'TransferReceived' && $lockedCg->trangThai === TransferStatus::pending) {
                $tonKhoService->tru((int) $loThuoc->id, (int) $lockedCg->benGuiId, (int) $lockedCg->soLuong);
                $tonKhoService->cong((int) $loThuoc->id, (int) $lockedCg->benNhanId, (int) $lockedCg->soLuong);
                $lockedCg->update([
                    'trangThai' => TransferStatus::received,
                    'thoiGianXacNhan' => $blockTime,
                ]);
            } elseif ($this->eventName === 'TransferRejected' && $lockedCg->trangThai === TransferStatus::pending) {
                $lockedCg->update([
                    'trangThai' => TransferStatus::rejected,
                    'thoiGianXacNhan' => $blockTime,
                ]);
                $conPending = ChuyenGiao::query()
                    ->where('loThuocId', $loThuoc->id)
                    ->where('benGuiId', $lockedCg->benGuiId)
                    ->where('trangThai', TransferStatus::pending->value)
                    ->exists();
                $daNhan = ChuyenGiao::query()
                    ->where('loThuocId', $loThuoc->id)
                    ->where('benGuiId', $lockedCg->benGuiId)
                    ->where('trangThai', TransferStatus::received->value)
                    ->exists();
                $trangThaiLo = $loThuoc->trangThai instanceof BatchStatus
                    ? $loThuoc->trangThai
                    : BatchStatus::tryFrom((string) $loThuoc->trangThai);
                if (! $conPending && ! $daNhan && (int) $loThuoc->toChucId === (int) $lockedCg->benGuiId && $trangThaiLo === BatchStatus::inTransit) {
                    $loThuoc->update(['trangThai' => BatchStatus::created->value]);
                }
            } elseif ($this->eventName === 'TransferCancelled' && $lockedCg->trangThai === TransferStatus::pending) {
                $lockedCg->update([
                    'trangThai' => TransferStatus::cancelled,
                    'thoiGianXacNhan' => $blockTime,
                ]);
                $daNhanTruocDo = ChuyenGiao::query()
                    ->where('loThuocId', $loThuoc->id)
                    ->where('benGuiId', $lockedCg->benGuiId)
                    ->where('trangThai', TransferStatus::received->value)
                    ->exists();
                $trangThaiLo = $loThuoc->trangThai instanceof BatchStatus
                    ? $loThuoc->trangThai
                    : BatchStatus::tryFrom((string) $loThuoc->trangThai);
                if (! $daNhanTruocDo && (int) $loThuoc->toChucId === (int) $lockedCg->benGuiId && $trangThaiLo === BatchStatus::inTransit) {
                    $loThuoc->update(['trangThai' => BatchStatus::created->value]);
                }
            }

            NhatKyHeThong::query()->create([
                'taiKhoanId' => $this->userId,
                'doiTuongId' => $cg->id,
                'hanhDong' => 'CHUYEN_GIAO_ONCHAIN_' . strtoupper($this->eventName),
                'ketQua' => 'THANH_CONG',
                'thoiGian' => now(),
                'chiTiet' => "{$this->eventName} đã xác nhận trên Ethereum Sepolia. TxHash: {$txHash}",
            ]);
        });

        Log::info("[ProcessBatchTransferJob] {$this->eventName} CG #{$cg->id} confirmed: {$txHash}");
    }

    private function findTransaction(ChuyenGiao $cg): BlockchainTransaction
    {
        $txId = $this->blockchainTransactionId;
        if (! $txId) {
            $txId = BlockchainTransaction::query()
                ->where('chuyenGiaoId', $cg->id)
                ->where('eventName', $this->eventName)
                ->latest('id')
                ->value('id');
        }

        if (! $txId) {
            throw new Exception("Không tìm thấy blockchainTransaction cho CG #{$cg->id} / {$this->eventName}.");
        }

        return BlockchainTransaction::query()
            ->whereKey($txId)
            ->where('chuyenGiaoId', $cg->id)
            ->firstOrFail();
    }

    public function failed(?\Throwable $exception): void
    {
        $reason = (string) ($exception?->getMessage() ?? '');
        $isDefinitiveRevert = (bool) preg_match('/revert|unauthorized|notcurrentowner|nottransfer|batchnotfound|invalidtransferstatus|pendingtransferexists|transferalreadyexists|batchalreadyrecalled|invaliddata|invalidaddress/i', $reason);
        $txId = $this->blockchainTransactionId;
        if ($txId && $isDefinitiveRevert) {
            $transaction = BlockchainTransaction::query()->find($txId);
            if ($transaction && strtoupper((string) $transaction->trangThai) === 'PENDING') {
                $transaction->update(['trangThai' => 'FAILED', 'thoiGian' => now()]);
            }
        }

        Log::error('[ProcessBatchTransferJob] FAILED CG #' . $this->chuyenGiaoId . ' / ' . $this->eventName . ': ' . $reason . ($isDefinitiveRevert ? '' : ' (giữ PENDING để đối soát/retry, chưa kết luận tx thất bại)'));
    }
}
