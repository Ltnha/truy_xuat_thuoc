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
        Log::info('[ProcessBatchTransferJob] Job bắt đầu xử lý chuyển giao.', [
            'chuyenGiaoId' => $this->chuyenGiaoId,
            'eventName' => $this->eventName,
            'blockchainTransactionId' => $this->blockchainTransactionId,
            'queueConnection' => config('queue.default'),
        ]);

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
            Log::info('[ProcessBatchTransferJob] Bắt đầu đối soát event trước khi ký.', [
                'chuyenGiaoId' => $cg->id,
                'eventName' => $this->eventName,
                'transferId' => $transferId,
            ]);
            $eventCheck = $signer->findTransferEvent($transferId, $this->eventName);
            Log::info('[ProcessBatchTransferJob] Đối soát event hoàn tất.', [
                'chuyenGiaoId' => $cg->id,
                'eventName' => $this->eventName,
                'isRecorded' => ! empty($eventCheck['isRecorded']),
            ]);
            if (! empty($eventCheck['isRecorded'])) {
                $candidate = $eventCheck['txHash'] ?? null;
                if (is_string($candidate) && preg_match('/^0x[a-fA-F0-9]{64}$/', $candidate)) {
                    $txHash = $candidate;
                    $blockNumber = isset($eventCheck['blockNumber']) ? (int) $eventCheck['blockNumber'] : null;
                    $blockTimestamp = isset($eventCheck['blockTimestamp']) ? (int) $eventCheck['blockTimestamp'] : null;
                }
            }
        } catch (\Throwable $e) {
            // Fallback đọc mapping transfers(bytes32) trực tiếp từ contract storage.
            // Đây là RPC eth_call, không cần truy vấn lịch sử eth_getLogs đã bị prune.
            Log::warning('[ProcessBatchTransferJob] RPC không đọc được lịch sử event; chuyển sang kiểm tra trạng thái transfer trong contract storage.', [
                'chuyenGiaoId' => $cg->id,
                'eventName' => $this->eventName,
                'transferId' => $transferId,
                'reason' => $e->getMessage(),
            ]);

            $state = $signer->getTransferState($transferId);
            $transfer = $state['transfer'] ?? null;
            $onChainStatus = $transfer ? (int) ($transfer['status'] ?? 0) : 0;

            if (! $transfer) {
                // Chỉ an toàn gửi mới khi submitTransfer chưa tồn tại trên contract.
                if ($action !== 'submitTransfer') {
                    throw new Exception(
                        'Không tìm thấy transferId trên contract; không thể thực hiện ' . $action . '. Lỗi đối soát event gốc: ' . $e->getMessage(),
                        previous: $e
                    );
                }

                Log::warning('[ProcessBatchTransferJob] Transfer chưa tồn tại trên contract; an toàn tiếp tục submitTransfer dù RPC không đọc được event history.', [
                    'chuyenGiaoId' => $cg->id,
                    'transferId' => $transferId,
                ]);
            } elseif ($action !== 'submitTransfer' && $onChainStatus === 1) {
                // Status 1 = PENDING. Contract xác nhận transfer đã submit và đang chờ bên nhận.
                // Với receive/reject/cancel, có thể tiếp tục action tương ứng mà không resend submit.
                Log::warning('[ProcessBatchTransferJob] Đã xác minh transfer đang PENDING bằng contract storage; tiếp tục action mà không cần eth_getLogs.', [
                    'chuyenGiaoId' => $cg->id,
                    'transferId' => $transferId,
                    'action' => $action,
                    'onChainStatus' => $onChainStatus,
                ]);
            } else {
                // Transfer đã có trạng thái khác hoặc submit đã tồn tại: không thể lấy txHash từ
                // storage mapping. Dừng an toàn để tránh gửi trùng; cần archive-capable RPC khôi phục txHash.
                throw new Exception(
                    'Không thể xác minh an toàn giao dịch do RPC không cung cấp event history. Trạng thái transfer on-chain='
                    . $onChainStatus . '; action=' . $action
                    . '. Cần RPC hỗ trợ archive eth_getLogs để khôi phục txHash, không gửi lại giao dịch.',
                    previous: $e
                );
            }
        }

        if (! $txHash) {
            if ($action === 'rejectTransfer') {
                Log::info('[ProcessBatchTransferJob] Nhà phân phối đang yêu cầu ký giao dịch từ chối trên Sepolia.', [
                    'chuyenGiaoId' => $cg->id,
                    'transferId' => $transferId,
                    'benGuiId' => $cg->benGuiId,
                    'benNhanId' => $cg->benNhanId,
                    'signerAddress' => $actor->diaChiVi,
                    'eventName' => $this->eventName,
                ]);
            }

            Log::info('[ProcessBatchTransferJob] Bắt đầu kiểm tra/nạp gas cho ví ký.', [
                'action' => $action,
                'signerAddress' => $actor->diaChiVi,
                'chuyenGiaoId' => $cg->id,
            ]);
            $signer->ensureGas((string) $actor->diaChiVi);
            Log::info('[ProcessBatchTransferJob] Kiểm tra/nạp gas hoàn tất; chuẩn bị gọi Signer /tx.', [
                'action' => $action,
                'chuyenGiaoId' => $cg->id,
            ]);
            $result = $signer->tx((string) $actor->signerKeyId, $action, $params);
            Log::info('[ProcessBatchTransferJob] Signer /tx đã trả phản hồi.', [
                'action' => $action,
                'ok' => ! empty($result['ok']),
                'txHash' => $result['txHash'] ?? null,
                'reason' => $result['reasonVi'] ?? $result['reason'] ?? null,
            ]);

            if (empty($result['ok']) || empty($result['txHash'])) {
                throw new Exception('Signer không gửi được ' . $action . ': ' . ($result['reasonVi'] ?? $result['reason'] ?? 'Không có txHash.'));
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
                $tonKhoService->chuyenChuSoHuu(
                    (int) $loThuoc->id,
                    (int) $lockedCg->benGuiId,
                    (int) $lockedCg->benNhanId,
                    (int) $lockedCg->soLuong
                );
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

        if ($this->eventName === 'TransferRejected') {
            Log::info('[ProcessBatchTransferJob] Giao dịch từ chối đã xác nhận; quyền sở hữu lô vẫn thuộc bên gửi.', [
                'chuyenGiaoId' => $cg->id,
                'txHash' => $txHash,
                'blockNumber' => $blockNumber,
            ]);
        }
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
