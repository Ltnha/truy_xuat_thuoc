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

class ProcessProductRegistrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $backoff = 10;

    public function __construct(
        public int $sanPhamId,
        public int $yeuCauId,
        public int $taiKhoanDuyetId
    ) {}

    public function handle(SignerClient $signerClient): void
    {
        $sanPham = SanPham::with('nhaSanXuat')->find($this->sanPhamId);

        if (! $sanPham || $sanPham->trangThaiDuyet !== 'DA_DUYET') {
            Log::warning("Job ProcessProductRegistrationJob bỏ qua do sản phẩm ID {$this->sanPhamId} không ở trạng thái DA_DUYET.");
            return;
        }

        // Kiểm tra Idempotency: Nếu đã có giao dịch CONFIRMED thì kết thúc
        $existingTx = BlockchainTransaction::where('sanPhamId', $sanPham->id)
            ->where('eventName', 'ProductRegistered')
            ->where('trangThai', 'CONFIRMED')
            ->first();

        if ($existingTx) {
            Log::info("Sản phẩm ID {$sanPham->id} đã được xác nhận on-chain trước đó. TxHash: {$existingTx->txHash}");
            return;
        }

        // Chuyển đổi dữ liệu sang format bytes32 của Smart Contract BatchTraceability.sol
        $batchIdBytes32 = '0x' . hash('sha256', $sanPham->maSanPham);
        $businessIdBytes32 = '0x' . hash('sha256', $sanPham->soDangKy);
        $eventTypeBytes32 = '0x' . hash('sha256', 'PRODUCT_REGISTERED');
        $payloadHashBytes32 = '0x' . hash('sha256', json_encode([
            'tenSanPham' => $sanPham->tenSanPham,
            'soDangKy' => $sanPham->soDangKy,
            'thanhPhan' => $sanPham->thanhPhan,
            'hamLuong' => $sanPham->hamLuong,
            'dangBaoChe' => $sanPham->dangBaoChe,
            'quyCachDongGoi' => $sanPham->quyCachDongGoi,
            'nhaSanXuatId' => $sanPham->nhaSanXuatId,
        ]));

        try {
            // Gọi Signer Service thực thi hàm recordBatchEvent
            $response = $signerClient->sendTransaction([
                'action' => 'recordBatchEvent',
                'params' => [
                    'batchId' => $batchIdBytes32,
                    'businessId' => $businessIdBytes32,
                    'eventType' => $eventTypeBytes32,
                    'payloadHash' => $payloadHashBytes32,
                ],
            ]);

            $txHash = $response['txHash'] ?? null;

            if (! $txHash) {
                throw new \RuntimeException('Signer Service không trả về txHash.');
            }

            DB::transaction(function () use ($sanPham, $txHash): void {
                BlockchainTransaction::updateOrCreate(
                    [
                        'sanPhamId' => $sanPham->id,
                        'eventName' => 'ProductRegistered',
                    ],
                    [
                        'txHash' => $txHash,
                        'businessId' => $sanPham->maSanPham,
                        'toChucId' => $sanPham->nhaSanXuatId,
                        'network' => 'Ethereum Sepolia Testnet',
                        'trangThai' => 'CONFIRMED',
                        'thoiGian' => now(),
                    ]
                );

                NhatKyHeThong::create([
                    'taiKhoanId' => $this->taiKhoanDuyetId,
                    'doiTuongId' => $this->taiKhoanDuyetId,
                    'hanhDong' => 'DONG_BO_BLOCKCHAIN_SAN_PHAM',
                    'ketQua' => 'THANH_CONG',
                    'chiTiet' => "Đã đồng bộ sản phẩm [{$sanPham->tenSanPham}] lên blockchain. TxHash: {$txHash}",
                ]);
            });

            Log::info("Đồng bộ sản phẩm [{$sanPham->tenSanPham}] lên Sepolia thành công. TxHash: {$txHash}");
        } catch (\Throwable $e) {
            Log::error("Lỗi đồng bộ sản phẩm ID {$sanPham->id} lên Blockchain: " . $e->getMessage());

            BlockchainTransaction::updateOrCreate(
                [
                    'sanPhamId' => $sanPham->id,
                    'eventName' => 'ProductRegistered',
                ],
                [
                    'txHash' => 'FAILED_' . uniqid(),
                    'businessId' => $sanPham->maSanPham,
                    'toChucId' => $sanPham->nhaSanXuatId,
                    'network' => 'Ethereum Sepolia Testnet',
                    'trangThai' => 'FAILED',
                    'thoiGian' => now(),
                ]
            );

            throw $e;
        }
    }
}
