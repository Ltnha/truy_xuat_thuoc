<?php

namespace App\Jobs;

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

/**
 * Ký on-chain sự kiện chuyển giao lô (recordBatchEvent với eventType TRANSFER_BATCH)
 * và cập nhật blockchainTransaction → CONFIRMED.
 *
 * Lịch sử nghiệp vụ nằm ở bảng chuyenGiao (đã tạo ở Controller).
 * Job này chỉ neo bằng chứng lên Sepolia + đồng bộ txHash.
 */
class ProcessBatchTransferJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $backoff = [10, 30, 60];

    public function __construct(
        public int $chuyenGiaoId,
        public int $userId
    ) {}

    public function handle(SignerClient $signer): void
    {
        $cg = ChuyenGiao::with(['loThuoc', 'benGui', 'benNhan'])->findOrFail($this->chuyenGiaoId);
        $loThuoc = $cg->loThuoc;

        if (!$loThuoc) {
            throw new Exception("Chuyển giao #{$this->chuyenGiaoId} không gắn lô thuốc hợp lệ.");
        }

        // 🚨 GUARD: Nếu đơn chuyển giao đã bị TỪ CHỐI hoặc HỦY bởi NPP,
        // không cần ký on-chain nữa. Đánh FAILED blockchainTx PENDING và thoát.
        $trangThaiCG = $cg->trangThai instanceof \App\Enums\TransferStatus
            ? $cg->trangThai->value
            : strtoupper((string) $cg->trangThai);

        if (in_array($trangThaiCG, ['CANCELLED', 'REJECTED'], true)) {
            Log::info("[ProcessBatchTransferJob] CG #{$cg->id} đã bị TỪ CHỐI/HỦY (trangThai={$trangThaiCG}). Bỏ qua ký on-chain.");
            // Đảm bảo blockchainTransaction PENDING gắn với đơn này được đánh FAILED
            // để UI phía NSX dừng hiển thị \"Đang xử lý Queue\"
            \App\Models\BlockchainTransaction::where('chuyenGiaoId', $cg->id)
                ->where('trangThai', 'PENDING')
                ->update(['trangThai' => 'FAILED', 'thoiGian' => now()]);
            return; // Xóa job khỏi queue mà không retry
        }

        // 1. Tính 4 tham số bytes32 (cùng pattern BatchTraceability.sol)
        $batchId = '0x' . hash('sha256', 'BATCH_' . $loThuoc->maLo);
        $businessId = '0x' . hash('sha256', 'TRANSFER_' . $cg->id . '_' . $loThuoc->maLo);
        $eventType = '0x' . hash('sha256', 'TRANSFER_BATCH');
        $payloadHash = '0x' . hash('sha256', json_encode([
            'chuyenGiaoId' => $cg->id,
            'maLo'         => $loThuoc->maLo,
            'maLoNghiepVu' => $loThuoc->maLoNghiepVu,
            'benGuiId'     => $cg->benGuiId,
            'benNhanId'    => $cg->benNhanId,
            'soLuong'      => (int) $cg->soLuong,
            'thoiGian'     => (string) ($cg->thoiGianKhoiTao ?? now()),
        ], JSON_UNESCAPED_UNICODE));

        $adminKeyId = config('truyXuat.blockchain.adminKeyId', 'ADMIN_MASTER_KEY');

        // 2. Idempotent: đã ghi on-chain chưa?
        $isAlreadyRecorded = false;
        if (method_exists($signer, 'isBatchEventRecorded')) {
            try {
                $isAlreadyRecorded = $signer->isBatchEventRecorded($batchId, $businessId, $eventType);
            } catch (\Throwable $e) {
                Log::warning('[ProcessBatchTransferJob] isBatchEventRecorded lỗi: ' . $e->getMessage());
            }
        }

        $txHash = null;
        $blockNumber = null;

        $existingTx = BlockchainTransaction::where('chuyenGiaoId', $cg->id)
            ->orderByDesc('id')
            ->first();

        if ($isAlreadyRecorded) {
            Log::info("[ProcessBatchTransferJob] CG #{$cg->id} đã có on-chain. Bỏ qua gửi contract.");
            $txHash = ($existingTx && !str_starts_with((string) $existingTx->txHash, 'PENDING_'))
                ? $existingTx->txHash
                : 'ONCHAIN_TRANSFER_' . substr(md5($businessId), 0, 32);
        } else {
            $signer->ensureGas($adminKeyId);

            $res = $signer->tx($adminKeyId, 'recordBatchEvent', [
                'batchId'     => $batchId,
                'businessId'  => $businessId,
                'eventType'   => $eventType,
                'payloadHash' => $payloadHash,
            ]);

            if (empty($res['ok'])) {
                throw new Exception('Lỗi ký chuyển giao On-chain: ' . ($res['reason'] ?? 'Không rõ nguyên nhân'));
            }

            $txHash = $res['txHash'];
            $blockNumber = $res['blockNumber'] ?? null;
        }

        // 3. Cập nhật MySQL
        DB::transaction(function () use ($cg, $loThuoc, $txHash, $blockNumber, $existingTx) {
            if ($existingTx) {
                $existingTx->update([
                    'txHash'      => $txHash,
                    'blockNumber' => $blockNumber,
                    'trangThai'   => 'CONFIRMED',
                    'thoiGian'    => now(),
                ]);
            } else {
                BlockchainTransaction::create([
                    'txHash'           => $txHash,
                    'blockNumber'      => $blockNumber,
                    'eventName'        => 'BatchEventRecorded',
                    'doiTuongToChucId' => $cg->benGuiId,
                    'businessId'       => (string) $cg->id,
                    'network'          => config('truyXuat.blockchain.network', 'Ethereum Sepolia Testnet'),
                    'trangThai'        => 'CONFIRMED',
                    'toChucId'         => $cg->benGuiId,
                    'sanPhamId'        => $loThuoc->sanPhamId,
                    'loThuocId'        => $loThuoc->id,
                    'chuyenGiaoId'     => $cg->id,
                    'thoiGian'         => now(),
                ]);
            }

            if (class_exists(NhatKyHeThong::class)) {
                NhatKyHeThong::create([
                    'taiKhoanId' => $this->userId,
                    'doiTuongId' => $cg->id,
                    'hanhDong'   => 'CHUYEN_GIAO_ONCHAIN',
                    'ketQua'     => 'THANH_CONG',
                    'chiTiet'    => "Neo chuyển giao #{$cg->id} lô [{$loThuoc->maLoNghiepVu}] On-chain. TxHash: {$txHash}",
                ]);
            }
        });

        Log::info("[ProcessBatchTransferJob] Hoàn tất CG #{$cg->id}. TxHash: {$txHash}");
    }

    /**
     * Khi hết retry → đánh dấu transaction FAILED (để UI hiện gửi lại)
     */
    public function failed(?\Throwable $exception): void
    {
        $tx = BlockchainTransaction::where('chuyenGiaoId', $this->chuyenGiaoId)
            ->orderByDesc('id')
            ->first();

        if ($tx && strtoupper((string) $tx->trangThai) === 'PENDING') {
            $tx->update([
                'trangThai' => 'FAILED',
            ]);
        }

        Log::error('[ProcessBatchTransferJob] FAILED CG #' . $this->chuyenGiaoId . ': ' . ($exception?->getMessage() ?? ''));
    }
}
