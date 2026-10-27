<?php

namespace App\Http\Controllers\NhaSanXuat;

use App\Http\Controllers\Controller;
use App\Models\NhatKyHeThong;
use App\Models\SanPham;
use App\Models\YeuCauDangKySanPham;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SanPhamController extends Controller
{
    public function index(Request $request): View
    {
        $toChucId = $request->user()->toChucId;
        abort_if($toChucId === null, 403, 'Tài khoản chưa được liên kết với tổ chức.');

        $sanPhams = SanPham::query()
            ->with([
                'yeuCauDangKySanPhams' => fn($q) => $q->orderBy('versionHoSo', 'asc'),
                'blockchainTransactions' => fn($q) => $q->where('eventName', 'ProductRegistered')->latest('id'),
            ])
            ->where('nhaSanXuatId', $toChucId)
            ->latest('id')
            ->get();

        return view('nhaSanXuat.quanLySanPham', compact('sanPhams'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $toChucId = $user->toChucId;
        abort_if($toChucId === null, 403, 'Tài khoản chưa được liên kết với tổ chức.');

        $duLieu = $request->validate([
            'tenSanPham' => ['required', 'string', 'max:255'],
            'soDangKy' => ['required', 'string', 'max:50', 'unique:sanPham,soDangKy'],
            'thanhPhan' => ['required', 'string', 'max:255'],
            'hamLuong' => ['required', 'string', 'max:50'],
            'dangBaoChe' => ['required', 'string', 'max:100'],
            'quyCachDongGoi' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($duLieu, $toChucId, $user): void {
            $maSanPham = (string) Str::uuid();

            $sanPham = SanPham::create([
                'maSanPham' => $maSanPham,
                'tenSanPham' => $duLieu['tenSanPham'],
                'soDangKy' => strtoupper($duLieu['soDangKy']),
                'thanhPhan' => $duLieu['thanhPhan'],
                'hamLuong' => $duLieu['hamLuong'],
                'dangBaoChe' => $duLieu['dangBaoChe'],
                'quyCachDongGoi' => $duLieu['quyCachDongGoi'] ?? null,
                'nhaSanXuatId' => $toChucId,
                'trangThaiDuyet' => 'CHO_DUYET',
            ]);

            YeuCauDangKySanPham::create([
                'sanPhamId' => $sanPham->id,
                'tenSanPham' => $duLieu['tenSanPham'],
                'soDangKy' => strtoupper($duLieu['soDangKy']),
                'thanhPhan' => $duLieu['thanhPhan'],
                'hamLuong' => $duLieu['hamLuong'],
                'dangBaoChe' => $duLieu['dangBaoChe'],
                'quyCachDongGoi' => $duLieu['quyCachDongGoi'] ?? null,
                'versionHoSo' => 1,
                'ngayGui' => now(),
                'trangThai' => 'CHO_DUYET',
            ]);

            NhatKyHeThong::create([
                'taiKhoanId' => $user->id,
                'doiTuongId' => $user->id,
                'hanhDong' => 'NOP_DANG_KY_SAN_PHAM',
                'ketQua' => 'THANH_CONG',
                'chiTiet' => "Nộp hồ sơ đăng ký sản phẩm mới [{$sanPham->tenSanPham}] - SĐK: {$sanPham->soDangKy}",
            ]);
        });

        return back()->with('thanhCong', 'Đã nộp đơn đăng ký sản phẩm thành công. Vui lòng chờ Cơ quan quản lý xét duyệt.');
    }

    public function resubmit(Request $request, SanPham $sanPham): RedirectResponse
    {
        $user = $request->user();
        abort_unless((int) $sanPham->nhaSanXuatId === (int) $user->toChucId, 403);
        abort_unless($sanPham->trangThaiDuyet === 'TU_CHOI', 409, 'Chỉ được nộp lại sản phẩm đang bị từ chối.');

        $duLieu = $request->validate([
            'tenSanPham' => ['required', 'string', 'max:255'],
            'soDangKy' => ['required', 'string', 'max:50', 'unique:sanPham,soDangKy,' . $sanPham->id],
            'thanhPhan' => ['required', 'string', 'max:255'],
            'hamLuong' => ['required', 'string', 'max:50'],
            'dangBaoChe' => ['required', 'string', 'max:100'],
            'quyCachDongGoi' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($sanPham, $duLieu, $user): void {
            $maxVersion = (int) YeuCauDangKySanPham::where('sanPhamId', $sanPham->id)->max('versionHoSo');
            $newVersion = $maxVersion + 1;

            $sanPham->update([
                'tenSanPham' => $duLieu['tenSanPham'],
                'soDangKy' => strtoupper($duLieu['soDangKy']),
                'thanhPhan' => $duLieu['thanhPhan'],
                'hamLuong' => $duLieu['hamLuong'],
                'dangBaoChe' => $duLieu['dangBaoChe'],
                'quyCachDongGoi' => $duLieu['quyCachDongGoi'] ?? null,
                'trangThaiDuyet' => 'CHO_DUYET',
            ]);

            YeuCauDangKySanPham::create([
                'sanPhamId' => $sanPham->id,
                'tenSanPham' => $duLieu['tenSanPham'],
                'soDangKy' => strtoupper($duLieu['soDangKy']),
                'thanhPhan' => $duLieu['thanhPhan'],
                'hamLuong' => $duLieu['hamLuong'],
                'dangBaoChe' => $duLieu['dangBaoChe'],
                'quyCachDongGoi' => $duLieu['quyCachDongGoi'] ?? null,
                'versionHoSo' => $newVersion,
                'ngayGui' => now(),
                'trangThai' => 'CHO_DUYET',
            ]);

            NhatKyHeThong::create([
                'taiKhoanId' => $user->id,
                'doiTuongId' => $user->id,
                'hanhDong' => 'NOP_LAI_DANG_KY_SAN_PHAM',
                'ketQua' => 'THANH_CONG',
                'chiTiet' => "Nộp lại hồ sơ sản phẩm [{$sanPham->tenSanPham}] (Phiên bản {$newVersion})",
            ]);
        });

        return back()->with('thanhCong', 'Đã cập nhật và nộp lại hồ sơ sản phẩm thành công.');
    }
}
