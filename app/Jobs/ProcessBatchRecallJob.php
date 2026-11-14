<?php

namespace App\Jobs;

use App\Enums\TransferStatus;
use App\Jobs\ProcessBatchTransferJob;
use App\Models\BlockchainTransaction;
use App\Models\ChuyenGiao;
use App\Models\LoThuoc;
use App\Models\NhatKyHeThong;
use App\Models\ThuHoiLoHang;
use App\Services\Blockchain\SignerClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use kornrunner\Keccak;

class ProcessBatchRecallJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [10, 30, 60, 120, 300];

    public function __construct(public int $thuHoiLoHangId) {}

    public function handle(SignerClient $signer): void
    {
        $thuHoi = ThuHoiLoHang::with('loThuoc')->findOrFail($this->thuHoiLoHangId);
        $loThuoc = $thuHoi->loThuoc;

        if (!$loThuoc) {
            Log::warning("[ProcessBatchRecallJob] Không tìm thấy lô thuốc cho ThuHoiLoHang #{$thuHoi->id}");
            return;
        }

        $maLoStr = $loThuoc->maLoNghiepVu ?? $loThuoc->maLo ?? (string) $loThuoc->id;
        $businessIdStr = (string) $thuHoi->id;

        $batchId = '0x' . hash('sha256', 'BATCH_' . (string) $loThuoc->maLo);
        $payloadData = [
            'maLo' => (string) $loThuoc->maLo,
            'maLoNghiepVu' => (string) ($loThuoc->maLoNghiepVu ?? $loThuoc->maLo),
            'thuHoiId' => (int) $thuHoi->id,
            'lyDo' => (string) $thuHoi->lyDo,
            'ngayThuHoi' => (string) ($thuHoi->ngayThuHoi ?? now()),
        ];
        $payloadJson = json_encode($payloadData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
        if ($payloadJson === false) {
            throw new \RuntimeException('Không thể chuẩn hóa hồ sơ thu hồi để tạo payloadHash.');
        }
        $payloadHash = '0x' . hash('sha256', $payloadJson);

        // Contract v2: recallBatch chỉ cho NSX tạo lô hoặc địa chỉ có ROLE_CO_QUAN.
        // Cơ quan quản lý thực hiện thu hồi bằng owner/admin wallet.
        $adminKeyId = config('truyXuat.blockchain.adminKeyId', env('ADMIN_MASTER_KEY_ID', 'ADMIN_MASTER_KEY'));

        $batchState = $signer->getBatchState($batchId);
        if (empty($batchState['batch']['exists'])) {
            throw new \RuntimeException('Không thể thu hồi on-chain: batch chưa được registerBatch.');
        }

        $existingConfirmedTx = BlockchainTransaction::query()
            ->where('loThuocId', $loThuoc->id)
            ->where('eventName', 'BatchRecalled')
            ->where('trangThai', 'CONFIRMED')
            ->whereNotNull('txHash')
            ->latest('id')
            ->first();
        $dbTxHash = $existingConfirmedTx?->txHash;
        if (! is_string($dbTxHash) || ! preg_match('/^0x[a-fA-F0-9]{64}$/', $dbTxHash)) $dbTxHash = null;
        $txHash = $batchState['recallTxHash'] ?? $dbTxHash;
        $blockNumber = isset($batchState['recallBlockNumber'])
            ? (int) $batchState['recallBlockNumber']
            : ($existingConfirmedTx?->blockNumber ? (int) $existingConfirmedTx->blockNumber : null);
        $blockTimestamp = isset($batchState['recallBlockTimestamp']) ? (int) $batchState['recallBlockTimestamp'] : null;

        if (empty($batchState['batch']['recalled'])) {
            $signer->ensureGas($adminKeyId);
            $result = $signer->tx($adminKeyId, 'recallBatch', [
                'batchId' => $batchId,
                'payloadHash' => $payloadHash,
            ]);

            if (empty($result['ok']) || empty($result['txHash'])) {
                throw new \RuntimeException('Lỗi recallBatch: ' . ($result['reasonVi'] ?? $result['reason'] ?? 'Signer không trả về txHash.'));
            }
            $txHash = (string) $result['txHash'];
            $blockNumber = isset($result['blockNumber']) ? (int) $result['blockNumber'] : null;
            $blockTimestamp = isset($result['blockTimestamp']) ? (int) $result['blockTimestamp'] : $blockTimestamp;
        }

        if (! is_string($txHash) || ! preg_match('/^0x[a-fA-F0-9]{64}$/', $txHash)) {
            throw new \RuntimeException('Lô đã bị thu hồi on-chain nhưng không truy xuất được txHash BatchRecalled thật.');
        }

        $this->capNhatThanhCong($thuHoi, (string) $thuHoi->id, $txHash, $blockNumber, $blockTimestamp);
    }

    protected function capNhatThanhCong(ThuHoiLoHang $thuHoi, string $businessIdStr, string $txHash, ?int $blockNumber = null, ?int $blockTimestamp = null): void
    {
        DB::transaction(function () use ($thuHoi, $businessIdStr, $txHash, $blockNumber, $blockTimestamp) {
            $blockTime = $blockTimestamp ? \Illuminate\Support\Carbon::createFromTimestamp($blockTimestamp) : now();
            $loThuoc = LoThuoc::query()->whereKey($thuHoi->loThuocId)->lockForUpdate()->firstOrFail();
            $loThuoc->update(['trangThai' => 'RECALLED']);

            BlockchainTransaction::where(function ($q) use ($thuHoi, $businessIdStr) {
                $q->where('businessId', $businessIdStr)
                  ->orWhere('loThuocId', $thuHoi->loThuocId);
            })
            ->whereIn('eventName', ['BatchRecalled', 'RECALL_BATCH'])
            ->update([
                'trangThai' => 'CONFIRMED',
                'txHash' => $txHash,
                'blockNumber' => $blockNumber,
                'thoiGian' => $blockTime,
            ]);

            // Contract không tự xóa pendingTransferOf khi recall. Sau khi BatchRecalled
            // mined, yêu cầu đúng ví bên gửi ký cancelTransfer để đồng bộ trạng thái transfer.
            $pendingTransfers = ChuyenGiao::query()
                ->where('loThuocId', $thuHoi->loThuocId)
                ->where('trangThai', TransferStatus::pending->value)
                ->get();

            foreach ($pendingTransfers as $pendingTransfer) {
                $hasCancelRequest = BlockchainTransaction::query()
                    ->where('chuyenGiaoId', $pendingTransfer->id)
                    ->where('eventName', 'TransferCancelled')
                    ->where('trangThai', 'PENDING')
                    ->exists();
                if ($hasCancelRequest) {
                    continue;
                }

                $cancelTx = BlockchainTransaction::query()->create([
                    'txHash' => null,
                    'blockNumber' => null,
                    'eventName' => 'TransferCancelled',
                    'doiTuongToChucId' => $pendingTransfer->benGuiId,
                    'businessId' => $pendingTransfer->maChuyenGiao,
                    'network' => config('truyXuat.blockchain.mang', 'Ethereum Sepolia'),
                    'trangThai' => 'PENDING',
                    'toChucId' => $pendingTransfer->benGuiId,
                    'sanPhamId' => $loThuoc->sanPhamId,
                    'loThuocId' => $loThuoc->id,
                    'chuyenGiaoId' => $pendingTransfer->id,
                    'thoiGian' => now(),
                ]);

                ProcessBatchTransferJob::dispatch(
                    $pendingTransfer->id,
                    $thuHoi->coQuanQuyetDinhId,
                    $cancelTx->id,
                    'TransferCancelled',
                    'TRANSFER_CANCELLED'
                )->afterCommit();
            }

            NhatKyHeThong::create([
                'taiKhoanId' => $thuHoi->coQuanQuyetDinhId,
                'doiTuongId' => $thuHoi->loThuocId,
                'hanhDong' => 'NEO_BLOCKCHAIN_THU_HOI_THANH_CONG',
                'ketQua' => 'THANH_CONG',
                'thoiGian' => $blockTime,
                'chiTiet' => "Neo thành công quyết định thu hồi lô [{$thuHoi->loThuoc?->maLoNghiepVu}] lên Blockchain. TxHash: {$txHash}",
            ]);
        });
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessBatchRecallJob thất bại hoàn toàn: {$exception->getMessage()}");

        $thuHoi = ThuHoiLoHang::find($this->thuHoiLoHangId);
        $reason = $exception->getMessage();
        $isDefinitiveRevert = (bool) preg_match('/revert|unauthorized|batchnotfound|batchalreadyrecalled|invaliddata/i', $reason);
        if ($thuHoi && $isDefinitiveRevert) {
            BlockchainTransaction::where(function ($q) use ($thuHoi) {
                $q->where('businessId', (string) $thuHoi->id)
                  ->orWhere('loThuocId', $thuHoi->loThuocId);
            })
            ->whereIn('eventName', ['BatchRecalled', 'RECALL_BATCH'])
            ->where('trangThai', 'PENDING')
            ->update([
                'trangThai' => 'FAILED',
                'thoiGian' => now(),
            ]);
        } elseif ($thuHoi) {
            Log::warning('[ProcessBatchRecallJob] Giữ PENDING để đối soát/retry vì chưa có bằng chứng tx revert.');
        }
    }
}
