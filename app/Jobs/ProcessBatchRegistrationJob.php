<?php

namespace App\Jobs;

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

    public $tries = 5;
    public $backoff = [10, 30, 60];

    public function __construct(
        public int $loThuocId,
        public int $userId
    ) {}

    public function handle(
        SignerClient $signer,
        DonViSanPhamService $donViSanPhamService,
        QrService $qrService,
        TonKhoService $tonKhoService
    ): void {
        $loThuoc = LoThuoc::with(['sanPham', 'toChuc'])->findOrFail($this->loThuocId);

        // 1. Tính toán 4 mã bytes32 chuẩn theo BatchTraceability.sol
        $batchId = '0x' . hash('sha256', 'BATCH_' . $loThuoc->maLo);
        $businessId = '0x' . hash('sha256', $loThuoc->maLoNghiepVu);
        $eventType = '0x' . hash('sha256', 'RECORD_BATCH');
        $payloadHash = '0x' . hash('sha256', json_encode([
            'maLo' => $loThuoc->maLo,
            'maLoNghiepVu' => $loThuoc->maLoNghiepVu,
            'soLuong' => (int) $loThuoc->soLuong,
            'ngaySanXuat' => (string) $loThuoc->ngaySanXuat,
            'hanSuDung' => (string) $loThuoc->hanSuDung,
        ]));

        $adminKeyId = config('truyXuat.blockchain.adminKeyId', 'ADMIN_MASTER_KEY');

        // 2. Đối soát On-chain trước khi ký (Đảm bảo tính Idempotent khi retry)
        $isAlreadyRecorded = $signer->isBatchEventRecorded($batchId, $businessId, $eventType);
        $txHash = null;
        $blockNumber = null;

        $existingTx = BlockchainTransaction::where('loThuocId', $loThuoc->id)->first();

        if ($isAlreadyRecorded) {
            Log::info("[ProcessBatchRegistrationJob] Lô [{$loThuoc->maLoNghiepVu}] đã được ghi nhận On-chain trước đó. Bỏ qua bước gọi Smart Contract.");
            $txHash = ($existingTx && !str_starts_with($existingTx->txHash, 'PENDING_')) 
                ? $existingTx->txHash 
                : 'ONCHAIN_CONFIRMED_' . substr(md5($batchId), 0, 32);
        } else {
            // Đảm bảo gas cho ví Admin
            $signer->ensureGas($adminKeyId);

            // Ký và gửi giao dịch On-chain qua Signer Service
            $res = $signer->tx($adminKeyId, 'recordBatchEvent', [
                'batchId'     => $batchId,
                'businessId'  => $businessId,
                'eventType'   => $eventType,
                'payloadHash' => $payloadHash,
            ]);

            if (!$res['ok']) {
                throw new Exception("Lỗi đăng ký lô thuốc On-chain: " . ($res['reason'] ?? 'Không rõ nguyên nhân'));
            }

            $txHash = $res['txHash'];
            $blockNumber = $res['blockNumber'] ?? null;
        }

        // 3. Cập nhật MySQL trong Transaction an toàn
        DB::transaction(function () use (
            $loThuoc, 
            $txHash, 
            $blockNumber, 
            $donViSanPhamService, 
            $qrService, 
            $tonKhoService
        ) {
            // Cập nhật trạng thái transaction trong CSDL
            $bctx = BlockchainTransaction::where('loThuocId', $loThuoc->id)->first();
            if ($bctx) {
                $bctx->update([
                    'txHash'      => $txHash,
                    'blockNumber' => $blockNumber,
                    'trangThai'   => 'CONFIRMED',
                ]);
            } else {
                BlockchainTransaction::create([
                    'txHash'           => $txHash,
                    'blockNumber'      => $blockNumber,
                    'eventName'        => 'BatchEventRecorded',
                    'doiTuongToChucId' => $loThuoc->toChucId,
                    'businessId'       => $loThuoc->maLo,
                    'network'          => config('truyXuat.blockchain.network', 'Ethereum Sepolia Testnet'),
                    'trangThai'        => 'CONFIRMED',
                    'toChucId'         => $loThuoc->toChucId,
                    'sanPhamId'        => $loThuoc->sanPhamId,
                    'loThuocId'        => $loThuoc->id,
                ]);
            }

            // Đảm bảo lô ở trạng thái CREATED
            $loThuoc->update(['trangThai' => 'CREATED']);

            // 4. Sinh đơn vị thuốc lẻ (Nếu chưa sinh)
            $coDonVi = DB::table('donViSanPham')->where('loThuocId', $loThuoc->id)->exists();
            if (!$coDonVi) {
                $donViSanPhamService->sinhHangLoat($loThuoc);
            }

            // 5. Sinh mã QR cấp lô và cấp đơn vị (Nếu chưa sinh)
            $coQr = DB::table('qrCode')->where('loThuocId', $loThuoc->id)->where('loaiQR', 'BATCH')->exists();
            if (!$coQr) {
                $qrService->sinhChoLo($loThuoc);
            }

            // 6. Khởi tạo tồn kho ban đầu đúng một lần duy nhất
            $coTonKho = DB::table('tonKhoLo')
                ->where('loThuocId', $loThuoc->id)
                ->where('toChucId', $loThuoc->toChucId)
                ->exists();

            if (!$coTonKho) {
                $tonKhoService->cong($loThuoc->id, $loThuoc->toChucId, $loThuoc->soLuong);
            }

            // 7. Ghi audit log vào nhatKyHeThong
            NhatKyHeThong::create([
                'taiKhoanId' => $this->userId,
                'doiTuongId' => $loThuoc->id,
                'hanhDong'   => 'DANG_KY_LO_THUOC_ONCHAIN',
                'ketQua'     => 'THANH_CONG',
                'chiTiet'    => "Đăng ký thành công lô thuốc [{$loThuoc->maLoNghiepVu}] trên Blockchain. TxHash: {$txHash}",
            ]);
        });

        Log::info("[ProcessBatchRegistrationJob] Hoàn tất đăng ký lô thuốc [{$loThuoc->maLoNghiepVu}]. TxHash: {$txHash}");
    }
}
