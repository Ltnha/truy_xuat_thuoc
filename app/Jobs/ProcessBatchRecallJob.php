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

        // 1. Tính toán 4 tham số bytes32 chuẩn theo BatchTraceability.sol
        $batchId = '0x' . hash('sha256', 'BATCH_' . $loThuoc->maLo);
        $businessId = '0x' . hash('sha256', 'RECALL_' . $thuHoi->id . '_' . $loThuoc->maLo);
        // EVENT_RECALL_BATCH trên Smart Contract = keccak256("RECALL_BATCH")
        $eventType = '0x' . Keccak::hash('RECALL_BATCH', 256);

        $payloadData = [
            'maLo' => $loThuoc->maLo,
            'maLoNghiepVu' => $maLoStr,
            'thuHoiId' => $thuHoi->id,
            'lyDo' => $thuHoi->lyDo,
            'ngayThuHoi' => (string) ($thuHoi->ngayThuHoi ?? now()),
        ];
        $payloadHash = '0x' . hash('sha256', json_encode($payloadData, JSON_UNESCAPED_UNICODE));

        $driver = config('truyXuat.blockchain.driver', 'fake');

        // Chỉ sinh mã giả lập nếu driver được cấu hình rõ ràng là 'fake'
        if ($driver === 'fake') {
            $fakeTxHash = '0x' . bin2hex(random_bytes(32));
            $this->capNhatThanhCong($thuHoi, $businessIdStr, $fakeTxHash, rand(5000000, 6000000));
            return;
        }

        // 2. Kiểm tra Idempotency trên Contract qua Signer Service
        $isAlreadyRecorded = false;
        try {
            $isAlreadyRecorded = $signer->isBatchEventRecorded($batchId, $businessId, $eventType);
        } catch (\Throwable $e) {
            Log::warning("[ProcessBatchRecallJob] Kiểm tra Idempotency cảnh báo: " . $e->getMessage());
        }

        if ($isAlreadyRecorded) {
            Log::info("[ProcessBatchRecallJob] Quyết định thu hồi #{$thuHoi->id} đã có trên Blockchain.");
            $this->capNhatThanhCong($thuHoi, $businessIdStr, 'ONCHAIN_RECALL_' . substr(md5($businessId), 0, 32));
            return;
        }

        // 3. Đảm bảo nạp Gas tự động cho ví Admin nếu số dư thấp
        $adminKeyId = config('truyXuat.blockchain.adminKeyId', 'ADMIN_MASTER_KEY');
        try {
            $signer->ensureGas($adminKeyId);
        } catch (\Throwable $e) {
            Log::warning("[ProcessBatchRecallJob] ensureGas cảnh báo: " . $e->getMessage());
        }

        // 4. Ký và gửi giao dịch recordBatchEvent qua Signer Service (node index.js)
        $res = $signer->tx($adminKeyId, 'recordBatchEvent', [
            'batchId' => $batchId,
            'businessId' => $businessId,
            'eventType' => $eventType,
            'payloadHash' => $payloadHash,
        ]);

        if (!empty($res['ok']) && !empty($res['txHash'])) {
            $this->capNhatThanhCong($thuHoi, $businessIdStr, $res['txHash'], $res['blockNumber'] ?? null);
        } else {
            throw new \Exception("Ký giao dịch Blockchain thất bại: " . ($res['reason'] ?? json_encode($res)));
        }
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
