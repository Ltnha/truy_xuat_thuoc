<?php

namespace App\Jobs;

use App\Models\BlockchainIdentity;
use App\Models\BlockchainTransaction;
use App\Models\LoThuoc;
use App\Models\NhatKyHeThong;
use App\Services\Blockchain\SignerClient;
use App\Services\ChuoiCungUng\TonKhoService;
use App\Services\LoThuoc\DonViSanPhamService;
use App\Services\LoThuoc\QrService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessBatchRegistrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [10, 30, 60, 120, 300];

    public function __construct(public int $loThuocId, public int $userId) {}

    public function handle(
        SignerClient $signer,
        DonViSanPhamService $donViSanPhamService,
        QrService $qrService,
        TonKhoService $tonKhoService
    ): void {
        $loThuoc = LoThuoc::with(['sanPham', 'toChuc'])->findOrFail($this->loThuocId);
        if (! $loThuoc->sanPham) {
            throw new Exception("Lô #{$loThuoc->id} không có sản phẩm liên kết.");
        }

        // Chuẩn ID phải trùng với ID mà controller duyệt sản phẩm đã ghi lên chain.
        $productId = '0x' . hash('sha256', 'PRODUCT_' . (string) $loThuoc->sanPham->maSanPham);
        $approval = $signer->isProductApproved($productId);
        if (empty($approval['isApproved'])) {
            throw new Exception('Không thể đăng ký lô: sản phẩm chưa được approveProduct xác nhận trên blockchain.');
        }

        // Contract v2 chỉ cho NSX có role ROLE_NSX gọi registerBatch.
        $identity = BlockchainIdentity::query()
            ->where('toChucId', $loThuoc->toChucId)
            ->where('trangThai', 'ACTIVE')
            ->whereNotNull('signerKeyId')
            ->whereNotNull('diaChiVi')
            ->first();

        if (! $identity || trim((string) $identity->signerKeyId) === '' || ! preg_match('/^0x[a-fA-F0-9]{40}$/', (string) $identity->diaChiVi)) {
            throw new Exception('Nhà sản xuất chưa có ví Blockchain ACTIVE hợp lệ để đăng ký lô.');
        }

        $batchId = '0x' . hash('sha256', 'BATCH_' . (string) $loThuoc->maLo);
        $batchState = $signer->getBatchState($batchId);
        $txHash = $batchState['registerTxHash'] ?? null;
        $blockNumber = isset($batchState['registerBlockNumber']) ? (int) $batchState['registerBlockNumber'] : null;

        if (empty($batchState['batch']['exists'])) {
            // Phí gas do treasury/admin nạp, nhưng transaction do ví NSX ký.
            $signer->ensureGas((string) $identity->diaChiVi);
            $result = $signer->tx((string) $identity->signerKeyId, 'registerBatch', [
                'batchId' => $batchId,
            ]);

            if (empty($result['ok']) || empty($result['txHash'])) {
                throw new Exception('Lỗi registerBatch: ' . ($result['reason'] ?? 'Signer không trả về txHash.'));
            }
            $txHash = (string) $result['txHash'];
            $blockNumber = isset($result['blockNumber']) ? (int) $result['blockNumber'] : null;
        }

        if (! is_string($txHash) || ! preg_match('/^0x[a-fA-F0-9]{64}$/', $txHash)) {
            throw new Exception('Lô đã tồn tại on-chain nhưng không truy xuất được txHash BatchRegistered thật; cần đối soát RPC, không tạo hash thay thế.');
        }

        DB::transaction(function () use ($loThuoc, $txHash, $blockNumber, $donViSanPhamService, $qrService, $tonKhoService): void {
            $bctx = BlockchainTransaction::query()
                ->where('loThuocId', $loThuoc->id)
                ->whereIn('eventName', ['BatchRegistered', 'BatchEventRecorded'])
                ->latest('id')
                ->first();

            $data = [
                'txHash' => $txHash,
                'blockNumber' => $blockNumber,
                'eventName' => 'BatchRegistered',
                'businessId' => (string) $loThuoc->maLo,
                'network' => config('truyXuat.blockchain.mang', 'Ethereum Sepolia Testnet'),
                'trangThai' => 'CONFIRMED',
                'toChucId' => $loThuoc->toChucId,
                'sanPhamId' => $loThuoc->sanPhamId,
                'loThuocId' => $loThuoc->id,
                'thoiGian' => now(),
            ];

            if ($bctx) {
                $bctx->update($data);
            } else {
                BlockchainTransaction::query()->create(array_merge($data, [
                    'doiTuongToChucId' => $loThuoc->toChucId,
                ]));
            }

            $loThuoc->update(['trangThai' => 'CREATED']);

            if (! DB::table('donViSanPham')->where('loThuocId', $loThuoc->id)->exists()) {
                $donViSanPhamService->sinhHangLoat($loThuoc);
            }

            if (! DB::table('qrCode')->where('loThuocId', $loThuoc->id)->where('loaiQR', 'BATCH')->exists()) {
                $qrService->sinhChoLo($loThuoc);
            }

            $coTonKho = DB::table('tonKhoLo')
                ->where('loThuocId', $loThuoc->id)
                ->where('toChucId', $loThuoc->toChucId)
                ->exists();

            if (! $coTonKho) {
                $tonKhoService->cong($loThuoc->id, $loThuoc->toChucId, $loThuoc->soLuong);
            }

            NhatKyHeThong::query()->create([
                'taiKhoanId' => $this->userId,
                'doiTuongId' => $loThuoc->id,
                'hanhDong' => 'DANG_KY_LO_THUOC_ONCHAIN',
                'ketQua' => 'THANH_CONG',
                'chiTiet' => "Đăng ký lô [{$loThuoc->maLoNghiepVu}] bằng registerBatch thành công. TxHash: {$txHash}",
            ]);
        });

        Log::info("[ProcessBatchRegistrationJob] BatchRegistered confirmed for batch {$loThuoc->maLo}: {$txHash}");
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('[ProcessBatchRegistrationJob] FAILED batch #' . $this->loThuocId . ': ' . ($exception?->getMessage() ?? ''));
        BlockchainTransaction::query()
            ->where('loThuocId', $this->loThuocId)
            ->whereIn('eventName', ['BatchRegistered', 'BatchEventRecorded'])
            ->where('trangThai', 'PENDING')
            ->update(['trangThai' => 'FAILED', 'thoiGian' => now()]);
    }
}
