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

        // Không chỉ kiểm tra cờ approved: xác minh hash hồ sơ hiện tại vẫn khớp bản đã duyệt.
        $productPayload = [
            'maSanPham' => (string) $loThuoc->sanPham->maSanPham,
            'tenSanPham' => (string) $loThuoc->sanPham->tenSanPham,
            'soDangKy' => (string) $loThuoc->sanPham->soDangKy,
            'thanhPhan' => $loThuoc->sanPham->thanhPhan,
            'hamLuong' => $loThuoc->sanPham->hamLuong,
            'dangBaoChe' => $loThuoc->sanPham->dangBaoChe,
            'quyCachDongGoi' => $loThuoc->sanPham->quyCachDongGoi,
            'nhaSanXuatId' => (int) $loThuoc->sanPham->nhaSanXuatId,
        ];
        $productPayloadJson = json_encode($productPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
        if ($productPayloadJson === false) {
            throw new Exception('Không thể chuẩn hóa hồ sơ sản phẩm để xác minh payloadHash.');
        }
        $expectedProductHash = '0x' . hash('sha256', $productPayloadJson);
        if (strtolower((string) ($approval['payloadHash'] ?? '')) !== strtolower($expectedProductHash)) {
            throw new Exception('Hash hồ sơ sản phẩm trong DB không khớp approvedProducts trên blockchain. Không thể tạo lô.');
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
        $existingConfirmedTx = BlockchainTransaction::query()
            ->where('loThuocId', $loThuoc->id)
            ->where('eventName', 'BatchRegistered')
            ->where('trangThai', 'CONFIRMED')
            ->whereNotNull('txHash')
            ->latest('id')
            ->first();
        $dbTxHash = $existingConfirmedTx?->txHash;
        if (! is_string($dbTxHash) || ! preg_match('/^0x[a-fA-F0-9]{64}$/', $dbTxHash)) $dbTxHash = null;
        $txHash = $batchState['registerTxHash'] ?? $dbTxHash;
        $blockNumber = isset($batchState['registerBlockNumber'])
            ? (int) $batchState['registerBlockNumber']
            : ($existingConfirmedTx?->blockNumber ? (int) $existingConfirmedTx->blockNumber : null);
        $blockTimestamp = isset($batchState['registerBlockTimestamp']) ? (int) $batchState['registerBlockTimestamp'] : null;

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
            $blockTimestamp = isset($result['blockTimestamp']) ? (int) $result['blockTimestamp'] : $blockTimestamp;
        }

        if (! is_string($txHash) || ! preg_match('/^0x[a-fA-F0-9]{64}$/', $txHash)) {
            throw new Exception('Lô đã tồn tại on-chain nhưng không truy xuất được txHash BatchRegistered thật; cần đối soát RPC, không tạo hash thay thế.');
        }

        DB::transaction(function () use ($loThuoc, $txHash, $blockNumber, $blockTimestamp, $donViSanPhamService, $qrService, $tonKhoService): void {
            $blockTime = $blockTimestamp ? \Illuminate\Support\Carbon::createFromTimestamp($blockTimestamp) : now();
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
                'thoiGian' => $blockTime,
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
        $reason = (string) ($exception?->getMessage() ?? '');
        $isDefinitiveRevert = (bool) preg_match('/revert|unauthorized|batchalreadyexists|invaliddata|productalreadyapproved/i', $reason);
        if ($isDefinitiveRevert) {
            BlockchainTransaction::query()
                ->where('loThuocId', $this->loThuocId)
                ->whereIn('eventName', ['BatchRegistered', 'BatchEventRecorded'])
                ->where('trangThai', 'PENDING')
                ->update(['trangThai' => 'FAILED', 'thoiGian' => now()]);
        } else {
            Log::warning('[ProcessBatchRegistrationJob] Giữ PENDING để đối soát/retry vì chưa có bằng chứng tx revert.');
        }
    }
}
