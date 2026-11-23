<?php

namespace App\Http\Controllers\LoThuoc;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessBatchRegistrationJob;
use App\Models\BlockchainTransaction;
use App\Models\LoThuoc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ChiTietLoThuocController extends Controller
{
    /**
     * Trang chi tiết lô thuốc (giao diện mới - kết nối CSDL thật)
     */
    public function show(int $id)
    {
        $user = Auth::user();

        $loThuoc = LoThuoc::with(['sanPham', 'blockchainTransactions', 'qrCode'])
            ->where('id', $id)
            ->where('toChucId', $user->toChucId ?? 0)
            ->firstOrFail();

        $batchTx = BlockchainTransaction::query()
            ->where('loThuocId', $loThuoc->id)
            ->where('eventName', 'BatchRegistered')
            ->where('trangThai', 'CONFIRMED')
            ->whereNotNull('txHash')
            ->latest('id')
            ->first();

        // Đảm bảo lô luôn có mã QR sẵn sàng
        if (! $loThuoc->qrCode) {
            app(\App\Services\LoThuoc\QrService::class)->sinhChoLo($loThuoc);
            $loThuoc->load('qrCode');
        }
        $qrDaSanSang = (bool) $loThuoc->qrCode;

        return view('nhaSanXuat.chiTietLoThuoc', compact('loThuoc', 'qrDaSanSang'));
    }

    /**
     * API lấy dữ liệu chi tiết từ CSDL thật để JS render
     */
    public function getChiTietAPI(int $id)
    {
        $user = Auth::user();

        $loThuoc = LoThuoc::with([
            'sanPham',
            'blockchainTransactions',
            'chuyenGiao.benNhan',
        ])
            ->where('id', $id)
            ->where('toChucId', $user->toChucId ?? 0)
            ->first();

        if (!$loThuoc) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy lô thuốc hoặc bạn không có quyền xem.',
            ], 404);
        }

        // Lấy transaction mới nhất (theo id giảm dần)
        $tx = $loThuoc->blockchainTransactions
            ->sortByDesc('id')
            ->first();

        // Chuẩn hóa dữ liệu chuyển giao
        $danhSachChuyenGiao = [];
        if ($loThuoc->relationLoaded('chuyenGiao') && $loThuoc->chuyenGiao) {
            foreach ($loThuoc->chuyenGiao as $cg) {
                $danhSachChuyenGiao[] = [
                    'id'              => $cg->id,
                    'soLuong'         => $cg->soLuong ?? null,
                    'trangThai'       => $cg->trangThai?->value ?? (string) $cg->trangThai,
                    'thoiGianKhoiTao' => $cg->thoiGianKhoiTao,
                    'thoiGianXacNhan' => $cg->thoiGianXacNhan,
                    'benNhan'         => $cg->benNhan ? [
                        'id'        => $cg->benNhan->id,
                        'tenToChuc' => $cg->benNhan->tenToChuc ?? 'N/A',
                    ] : null,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id'                     => $loThuoc->id,
                'maLo'                   => $loThuoc->maLo,
                'maLoNghiepVu'           => $loThuoc->maLoNghiepVu,
                'soLuong'                => $loThuoc->soLuong,
                'donViTinh'              => $loThuoc->donViTinh ?? 'Hộp',
                'ngaySanXuat'            => $loThuoc->ngaySanXuat,
                'hanSuDung'              => $loThuoc->hanSuDung,
                'trangThai'              => $loThuoc->trangThai,
                'created_at'             => $loThuoc->thoiGianTao ?? null,
                'san_pham'               => $loThuoc->sanPham ? [
                    'id'          => $loThuoc->sanPham->id,
                    'tenSanPham'  => $loThuoc->sanPham->tenSanPham,
                    'soDangKy'    => $loThuoc->sanPham->soDangKy,
                ] : null,
                'blockchain_transaction' => $tx ? [
                    'id'          => $tx->id,
                    'txHash'      => $tx->txHash,
                    'blockNumber' => $tx->blockNumber,
                    'eventName'   => $tx->eventName,
                    'trangThai'   => $tx->trangThai,
                    'network'     => $tx->network,
                    'thoiGian'    => $tx->thoiGian,
                    'businessId'  => $tx->businessId,
                ] : null,
                'chuyen_giao'            => $danhSachChuyenGiao,
            ],
        ]);
    }

    /**
     * API: Gửi / Gửi lại lô lên Blockchain (đẩy vào Queue thật)
     */
    public function guiBlockchain(int $id)
    {
        $user = Auth::user();

        $loThuoc = LoThuoc::where('id', $id)
            ->where('toChucId', $user->toChucId ?? 0)
            ->first();

        if (!$loThuoc) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy lô thuốc.',
            ], 404);
        }

        // Kiểm tra đã confirmed chưa
        $txHienTai = BlockchainTransaction::where('loThuocId', $loThuoc->id)
            ->orderByDesc('id')
            ->first();

        if ($txHienTai) {
            $st = strtoupper((string) ($txHienTai->trangThai ?? ''));
            $hash = (string) ($txHienTai->txHash ?? '');

            if (in_array($st, ['CONFIRMED', 'SUCCESS'], true) && preg_match('/^0x[a-fA-F0-9]{64}$/', $hash)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lô thuốc này đã được ghi nhận On-chain thành công.',
                ], 422);
            }

            // Nếu đang PENDING thì không tạo record mới
            if ($st === 'PENDING') {
                // Vẫn dispatch lại job để retry
                ProcessBatchRegistrationJob::dispatch($loThuoc->id, $user->id);

                return response()->json([
                    'success' => true,
                    'message' => 'Đã yêu cầu xử lý lại hàng đợi Blockchain.',
                ]);
            }
        }

        // Tạo record PENDING mới
        BlockchainTransaction::create([
            'txHash'           => null,
            'blockNumber'      => null,
            'eventName'        => 'BatchRegistered',
            'doiTuongToChucId' => $loThuoc->toChucId,
            'businessId'       => $loThuoc->maLo,
            'network'          => config('truyXuat.blockchain.mang', 'Ethereum Sepolia'),
            'trangThai'        => 'PENDING',
            'toChucId'         => $loThuoc->toChucId,
            'sanPhamId'        => $loThuoc->sanPhamId,
            'loThuocId'        => $loThuoc->id,
            'thoiGian'         => now(),
        ]);

        // Đẩy vào Queue thật
        ProcessBatchRegistrationJob::dispatch($loThuoc->id, $user->id);

        return response()->json([
            'success' => true,
            'message' => 'Đã đưa yêu cầu gửi Blockchain vào hàng đợi.',
        ]);
    }

    /**
     * Hiển thị hoặc tải xuống mã QR duy nhất của lô thuốc.
     * Cho phép nhà sản xuất sở hữu lô xem và tải về ngay khi tạo lô thuốc.
     */
    public function taiMaQR(Request $request, int $id)
    {
        $user = Auth::user();

        $loThuoc = LoThuoc::query()
            ->whereKey($id)
            ->where('toChucId', $user->toChucId ?? 0)
            ->firstOrFail();

        $qr = DB::table('qrCode')
            ->where('loThuocId', $loThuoc->id)
            ->where('trangThai', 'ACTIVE')
            ->first();

        if (! $qr || ! is_string($qr->giaTriQR) || trim($qr->giaTriQR) === '') {
            app(\App\Services\LoThuoc\QrService::class)->sinhChoLo($loThuoc);
            $qr = DB::table('qrCode')
                ->where('loThuocId', $loThuoc->id)
                ->where('trangThai', 'ACTIVE')
                ->first();
        }

        if (! $qr || ! is_string($qr->giaTriQR) || trim($qr->giaTriQR) === '') {
            abort(404, 'Không thể tạo hoặc tìm thấy mã QR cho lô thuốc.');
        }

        $urlTraCuu = route('tra-cuu', ['maDinhDanh' => $qr->giaTriQR]);
        $svgRaw = QrCode::format('svg')
            ->size(420)
            ->margin(2)
            ->errorCorrection('H')
            ->generate($urlTraCuu);

        // Nhúng metadata vào SVG để hỗ trợ đọc trực tiếp nhanh và chính xác
        $svgString = (string) $svgRaw;
        $metaTag = '<!-- PharmaChain-QR: ' . htmlspecialchars($urlTraCuu, ENT_QUOTES, 'UTF-8') . ' | maLo: ' . htmlspecialchars($loThuoc->maLoNghiepVu, ENT_QUOTES, 'UTF-8') . ' -->';
        if (str_contains($svgString, '<svg')) {
            $svgString = preg_replace('/<svg([^>]*)>/i', '<svg$1 data-qr-target="' . htmlspecialchars($urlTraCuu, ENT_QUOTES, 'UTF-8') . '" data-ma-lo="' . htmlspecialchars($loThuoc->maLoNghiepVu, ENT_QUOTES, 'UTF-8') . '">' . "\n" . $metaTag, $svgString, 1);
        }

        $maFile = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $loThuoc->maLoNghiepVu);
        $cheDo = $request->boolean('download') ? 'attachment' : 'inline';

        return response($svgString, 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Content-Disposition' => $cheDo . '; filename="QR-' . $maFile . '.svg"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
