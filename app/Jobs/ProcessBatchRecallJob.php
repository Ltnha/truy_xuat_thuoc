<?php

namespace App\Jobs;

use App\Models\BlockchainTransaction;
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

        $txHash = $batchState['recallTxHash'] ?? null;
        $blockNumber = isset($batchState['recallBlockNumber']) ? (int) $batchState['recallBlockNumber'] : null;

        if (empty($batchState['batch']['recalled'])) {
            $signer->ensureGas($adminKeyId);
            $result = $signer->tx($adminKeyId, 'recallBatch', [
                'batchId' => $batchId,
                'payloadHash' => $payloadHash,
            ]);

            if (empty($result['ok']) || empty($result['txHash'])) {
                throw new \RuntimeException('Lỗi recallBatch: ' . ($result['reason'] ?? 'Signer không trả về txHash.'));
            }
            $txHash = (string) $result['txHash'];
            $blockNumber = isset($result['blockNumber']) ? (int) $result['blockNumber'] : null;
        }

        if (! is_string($txHash) || ! preg_match('/^0x[a-fA-F0-9]{64}$/', $txHash)) {
            throw new \RuntimeException('Lô đã bị thu hồi on-chain nhưng không truy xuất được txHash BatchRecalled thật.');
        }

        $this->capNhatThanhCong($thuHoi, (string) $thuHoi->id, $txHash, $blockNumber);
    }

    protected function capNhatThanhCong(ThuHoiLoHang $thuHoi, string $businessIdStr, string $txHash, ?int $blockNumber = null): void
    {
        DB::transaction(function () use ($thuHoi, $businessIdStr, $txHash, $blockNumber) {
            BlockchainTransaction::where(function ($q) use ($thuHoi, $businessIdStr) {
                $q->where('businessId', $businessIdStr)
                  ->orWhere('loThuocId', $thuHoi->loThuocId);
            })
            ->whereIn('eventName', ['BatchRecalled', 'RECALL_BATCH'])
            ->update([
                'trangThai' => 'CONFIRMED',
                'txHash' => $txHash,
                'blockNumber' => $blockNumber,
                'thoiGian' => now(),
            ]);

            NhatKyHeThong::create([
                'taiKhoanId' => $thuHoi->coQuanQuyetDinhId,
                'doiTuongId' => $thuHoi->loThuocId,
                'hanhDong' => 'NEO_BLOCKCHAIN_THU_HOI_THANH_CONG',
                'ketQua' => 'THANH_CONG',
                'thoiGian' => now(),
                'chiTiet' => "Neo thành công quyết định thu hồi lô [{$thuHoi->loThuoc?->maLoNghiepVu}] lên Blockchain. TxHash: {$txHash}",
            ]);
        });
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessBatchRecallJob thất bại hoàn toàn: {$exception->getMessage()}");

        $thuHoi = ThuHoiLoHang::find($this->thuHoiLoHangId);
        if ($thuHoi) {
            BlockchainTransaction::where(function ($q) use ($thuHoi) {
                $q->where('businessId', (string) $thuHoi->id)
                  ->orWhere('loThuocId', $thuHoi->loThuocId);
            })
            ->whereIn('eventName', ['BatchRecalled', 'RECALL_BATCH'])
            ->update([
                'trangThai' => 'FAILED',
                'thoiGian' => now(),
            ]);
        }
    }
}
