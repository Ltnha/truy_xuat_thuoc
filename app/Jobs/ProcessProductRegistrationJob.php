<?php

namespace App\Jobs;

use App\Models\BlockchainTransaction;
use App\Models\NhatKyHeThong;
use App\Models\SanPham;
use App\Services\Blockchain\SignerClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ProcessProductRegistrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [10, 30, 60, 120, 300];

    public function __construct(
        public int $sanPhamId,
        public int $yeuCauId,
        public int $taiKhoanDuyetId
    ) {}

    public function handle(SignerClient $signer): void
    {
        $sanPham = SanPham::query()->findOrFail($this->sanPhamId);
        if ($sanPham->trangThaiDuyet !== 'DA_DUYET') {
            Log::info("[ProcessProductRegistrationJob] Bỏ qua sản phẩm #{$sanPham->id}: chưa được duyệt off-chain.");
            return;
        }

        $productId = '0x' . hash('sha256', 'PRODUCT_' . (string) $sanPham->maSanPham);
        $payload = [
            'maSanPham' => (string) $sanPham->maSanPham,
            'tenSanPham' => (string) $sanPham->tenSanPham,
            'soDangKy' => (string) $sanPham->soDangKy,
            'thanhPhan' => $sanPham->thanhPhan,
            'hamLuong' => $sanPham->hamLuong,
            'dangBaoChe' => $sanPham->dangBaoChe,
            'quyCachDongGoi' => $sanPham->quyCachDongGoi,
            'nhaSanXuatId' => (int) $sanPham->nhaSanXuatId,
        ];
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
        if ($json === false) throw new RuntimeException('Không thể chuẩn hóa hồ sơ sản phẩm.');
        $payloadHash = '0x' . hash('sha256', $json);

        $existing = $signer->isProductApproved($productId);
        $txHash = $existing['txHash'] ?? null;
        $blockNumber = isset($existing['blockNumber']) ? (int) $existing['blockNumber'] : null;

        if (! empty($existing['isApproved'])) {
            if (strtolower((string) ($existing['payloadHash'] ?? '')) !== strtolower($payloadHash)) {
                throw new RuntimeException('Sản phẩm đã được approveProduct với payloadHash khác; contract không hỗ trợ duyệt lại.');
            }
        } else {
            $adminKeyId = config('truyXuat.blockchain.adminKeyId', env('ADMIN_MASTER_KEY_ID', 'ADMIN_MASTER_KEY'));
            $signer->ensureGas($adminKeyId);
            $result = $signer->tx($adminKeyId, 'approveProduct', [
                'productId' => $productId,
                'payloadHash' => $payloadHash,
            ]);
            if (empty($result['ok']) || empty($result['txHash'])) {
                throw new RuntimeException('approveProduct thất bại: ' . ($result['reason'] ?? 'Signer không trả txHash.'));
            }
            $txHash = (string) $result['txHash'];
            $blockNumber = isset($result['blockNumber']) ? (int) $result['blockNumber'] : null;
        }

        if (! is_string($txHash) || ! preg_match('/^0x[a-fA-F0-9]{64}$/', $txHash)) {
            throw new RuntimeException('Không truy xuất được txHash ProductApproved thật; không tạo hash thay thế.');
        }

        DB::transaction(function () use ($sanPham, $txHash, $blockNumber): void {
            BlockchainTransaction::query()->updateOrCreate(
                ['sanPhamId' => $sanPham->id, 'eventName' => 'ProductApproved'],
                [
                    'txHash' => $txHash,
                    'blockNumber' => $blockNumber,
                    'businessId' => (string) $sanPham->maSanPham,
                    'toChucId' => $sanPham->nhaSanXuatId,
                    'network' => config('truyXuat.blockchain.mang', 'Ethereum Sepolia Testnet'),
                    'trangThai' => 'CONFIRMED',
                    'thoiGian' => now(),
                ]
            );

            NhatKyHeThong::query()->create([
                'taiKhoanId' => $this->taiKhoanDuyetId,
                'doiTuongId' => $sanPham->id,
                'hanhDong' => 'DONG_BO_APPROVE_PRODUCT_ONCHAIN',
                'ketQua' => 'THANH_CONG',
                'chiTiet' => "Đã ghi nhận ProductApproved cho [{$sanPham->tenSanPham}]. TxHash: {$txHash}",
            ]);
        });
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('[ProcessProductRegistrationJob] FAILED product #' . $this->sanPhamId . ': ' . ($exception?->getMessage() ?? ''));
        BlockchainTransaction::query()
            ->where('sanPhamId', $this->sanPhamId)
            ->where('eventName', 'ProductApproved')
            ->where('trangThai', 'PENDING')
            ->update(['trangThai' => 'FAILED', 'thoiGian' => now()]);
    }
}
