<?php

namespace App\Http\Controllers\CoQuan;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessProductRegistrationJob;
use App\Models\BlockchainTransaction;
use App\Models\NhatKyHeThong;
use App\Models\SanPham;
use App\Models\ToChuc;
use App\Models\YeuCauDangKySanPham;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DuyetSanPhamController extends Controller
{
    /**
     * Hiển thị danh sách hồ sơ chưa duyệt / bị từ chối và danh mục sản phẩm đã duyệt
     */
    public function index(): View
    {
        // TAB 1: Chỉ lấy các hồ sơ CHỜ DUYỆT hoặc BỊ TỪ CHỐI
        $yeuCauDangKys = YeuCauDangKySanPham::with(['sanPham.nhaSanXuat', 'nguoiXuLy'])
            ->whereIn('trangThai', ['CHO_DUYET', 'TU_CHOI'])
            ->latest('id')
            ->get();

        // TAB 2: Danh mục sản phẩm ĐÃ DUYỆT (Lưu hành & On-Chain)
        $sanPhamDaDuyets = SanPham::with(['nhaSanXuat', 'blockchainTransactions' => fn($q) => $q->where('eventName', 'ProductApproved')])
            ->where('trangThaiDuyet', 'DA_DUYET')
            ->latest('id')
            ->get();

        return view('coQuanQuanLy.xetDuyetSanPham', compact('yeuCauDangKys', 'sanPhamDaDuyets'));
    }

    /**
     * Phê duyệt hồ sơ sản phẩm: Lưu CSDL & đẩy Job đồng bộ Blockchain vào Queue
     */
    public function pheDuyet(Request $request, YeuCauDangKySanPham $yeuCau)
    {
        $user = $request->user();

        if ($yeuCau->trangThai !== 'CHO_DUYET') {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Hồ sơ này không ở trạng thái chờ duyệt.'], 400);
            }
            return back()->with('loi', 'Hồ sơ này không ở trạng thái chờ duyệt.');
        }

        $sanPham = $yeuCau->sanPham;

        // BƯỚC 1: XÁC ĐỊNH TỔ CHỨC CƠ QUAN QUẢN LÝ
        $toChucCoQuan = $user->toChuc
            ?? ToChuc::where('loaiToChuc', 'CO_QUAN_QUAN_LY')->first();

        if (! $toChucCoQuan) {
            $thongBaoLoi = 'Không tìm thấy thông tin Tổ chức Cơ quan Quản lý trong CSDL.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $thongBaoLoi], 400);
            }
            return back()->with('loi', $thongBaoLoi);
        }

        try {
            $now = now();

            // BƯỚC 2: LƯU TRẠNG THÁI DUYỆT OFF-CHAIN & TẠO GIAO DỊCH PENDING
            DB::transaction(function () use ($yeuCau, $sanPham, $user, $toChucCoQuan, $now): void {
                $yeuCau->update([
                    'trangThai' => 'DA_DUYET',
                    'nguoiXuLyId' => $user->id,
                    'ngayXuLy' => $now,
                ]);

                $sanPham->update([
                    'trangThaiDuyet' => 'DA_DUYET',
                ]);

                BlockchainTransaction::updateOrCreate(
                    [
                        'sanPhamId' => $sanPham->id,
                        'eventName' => 'ProductApproved',
                    ],
                    [
                        'txHash' => null,
                        'businessId' => $sanPham->maSanPham,
                        'toChucId' => $toChucCoQuan->id,
                        'network' => 'Ethereum Sepolia Testnet',
                        'trangThai' => 'PENDING',
                        'thoiGian' => $now,
                    ]
                );

                NhatKyHeThong::create([
                    'taiKhoanId' => $user->id,
                    'doiTuongId' => $sanPham->id,
                    'hanhDong' => 'PHE_DUYET_SAN_PHAM',
                    'ketQua' => 'THANH_CONG',
                    'chiTiet' => "Đã phê duyệt off-chain cho thuốc [{$sanPham->tenSanPham}] và đẩy vào hàng đợi đồng bộ Blockchain.",
                ]);
            });

            // BƯỚC 3: ĐẨY JOB VÀO HÀNG ĐỢI QUEUE
            ProcessProductRegistrationJob::dispatch($sanPham->id, $yeuCau->id, $user->id);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Đã phê duyệt hồ sơ! Hệ thống đang ghi nhận On-Chain trong hàng đợi...',
                    'sanPhamId' => $sanPham->id,
                    'tenSanPham' => $sanPham->tenSanPham,
                ]);
            }

            return back()->with('thanhCong', "Đã phê duyệt sản phẩm [{$sanPham->tenSanPham}] và đẩy vào hàng đợi ghi Blockchain.");
        } catch (\Throwable $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi phê duyệt hồ sơ: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('loi', 'Lỗi phê duyệt sản phẩm: ' . $e->getMessage());
        }
    }

    /**
     * Kiểm tra trạng thái đồng bộ Blockchain (ProductApproved) của sản phẩm
     */
    public function trangThaiBlockchain(SanPham $sanPham)
    {
        $tx = BlockchainTransaction::query()
            ->where('sanPhamId', $sanPham->id)
            ->where('eventName', 'ProductApproved')
            ->latest('id')
            ->first();

        if (! $tx) {
            return response()->json([
                'trangThai' => 'PENDING',
                'txHash' => null,
                'tenSanPham' => $sanPham->tenSanPham,
            ]);
        }

        return response()->json([
            'trangThai' => $tx->trangThai,
            'txHash' => $tx->txHash,
            'tenSanPham' => $sanPham->tenSanPham,
        ]);
    }

    /**
     * Từ chối hồ sơ đăng ký lưu hành sản phẩm
     */
    public function tuChoi(Request $request, YeuCauDangKySanPham $yeuCau)
    {
        $user = $request->user();

        if ($yeuCau->trangThai !== 'CHO_DUYET') {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Hồ sơ này không ở trạng thái chờ duyệt.'], 400);
            }
            return back()->with('loi', 'Hồ sơ này không ở trạng thái chờ duyệt.');
        }

        $duLieu = $request->validate([
            'lyDoTuChoi' => ['required', 'string', 'max:500'],
        ]);

        $sanPham = $yeuCau->sanPham;

        DB::transaction(function () use ($yeuCau, $sanPham, $duLieu, $user): void {
            $now = now();

            $yeuCau->update([
                'trangThai' => 'TU_CHOI',
                'lyDoTuChoi' => $duLieu['lyDoTuChoi'],
                'nguoiXuLyId' => $user->id,
                'ngayXuLy' => $now,
            ]);

            $sanPham->update([
                'trangThaiDuyet' => 'TU_CHOI',
            ]);

            NhatKyHeThong::create([
                'taiKhoanId' => $user->id,
                'doiTuongId' => $user->id,
                'hanhDong' => 'TU_CHOI_SAN_PHAM',
                'ketQua' => 'THANH_CONG',
                'chiTiet' => "Từ chối hồ sơ đăng ký thuốc [{$sanPham->tenSanPham}]. Lý do: {$duLieu['lyDoTuChoi']}",
            ]);
        });

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Đã từ chối hồ sơ đăng ký của thuốc [{$sanPham->tenSanPham}].",
                'tenSanPham' => $sanPham->tenSanPham,
            ]);
        }

        return back()->with('thanhCong', "Đã từ chối hồ sơ đăng ký của thuốc [{$sanPham->tenSanPham}].");
    }
}
