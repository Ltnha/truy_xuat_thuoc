<?php

namespace App\Http\Controllers\QuanTri;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\TaiKhoan;
use App\Services\NhatKyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TaiKhoanController extends Controller
{
    public function index(Request $request): View
    {
        $tuKhoa = $request->input('tuKhoa');
        $vaiTro = $request->input('vaiTro', 'TAT_CA');
        $trangThai = $request->input('trangThai', 'TAT_CA');

        $query = TaiKhoan::with(['toChuc', 'nhatKys' => fn($q) => $q->latest('id')->limit(10)]);

        if ($tuKhoa) {
            $query->where(function ($q) use ($tuKhoa) {
                $q->where('tenDangNhap', 'like', "%{$tuKhoa}%")
                    ->orWhere('email', 'like', "%{$tuKhoa}%")
                    ->orWhereHas('toChuc', fn($sub) => $sub->where('tenToChuc', 'like', "%{$tuKhoa}%"));
            });
        }

        if ($vaiTro !== 'TAT_CA') {
            $query->where('vaiTro', $vaiTro);
        }

        if ($trangThai !== 'TAT_CA') {
            $query->where('trangThai', $trangThai);
        }

        $taiKhoans = $query->latest('id')->paginate(10)->withQueryString();

        return view('quanTriVien.quanLyTaiKhoan', compact('taiKhoans', 'tuKhoa', 'vaiTro', 'trangThai'));
    }

    public function lock(Request $request, TaiKhoan $taiKhoan): RedirectResponse
    {
        if ($taiKhoan->id === Auth::id()) {
            return back()->with('loi', 'Bạn không thể tự khóa tài khoản của chính mình.');
        }

        if ($taiKhoan->vaiTro === Role::coQuanQuanLy) {
            $soCQQLActive = TaiKhoan::where('vaiTro', Role::coQuanQuanLy)
                ->where('trangThai', AccountStatus::active)
                ->count();
            if ($soCQQLActive <= 1) {
                return back()->with('loi', 'CHẶN KHÓA: Đây là tài khoản Cơ quan quản lý duy nhất đang hoạt động!');
            }
        }

        $request->validate(['lyDo' => 'required|string|max:500']);
        $lyDo = $request->input('lyDo');

        $taiKhoan->update([
            'trangThai' => AccountStatus::locked,
            'lyDoKhoa' => $lyDo,
            'nguoiKhoaId' => Auth::id(),
            'thoiGianKhoa' => now(),
        ]);

        NhatKyService::ghiLog(
            hanhDong: 'KHOA_TAI_KHOAN',
            ketQua: 'THANH_CONG',
            chiTiet: "Khóa tài khoản [{$taiKhoan->tenDangNhap}]. Lý do: {$lyDo}",
            doiTuongId: $taiKhoan->id
        );

        return back()->with('thanhCong', "Đã khóa tài khoản {$taiKhoan->tenDangNhap} thành công.");
    }

    public function unlock(Request $request, TaiKhoan $taiKhoan): RedirectResponse
    {
        $request->validate(['lyDo' => 'required|string|max:500']);
        $lyDo = $request->input('lyDo');

        $taiKhoan->update([
            'trangThai' => AccountStatus::active,
            'lyDoKhoa' => null,
            'nguoiKhoaId' => null,
            'thoiGianKhoa' => null,
        ]);

        NhatKyService::ghiLog(
            hanhDong: 'MO_KHOA_TAI_KHOAN',
            ketQua: 'THANH_CONG',
            chiTiet: "Mở khóa tài khoản [{$taiKhoan->tenDangNhap}]. Lý do: {$lyDo}",
            doiTuongId: $taiKhoan->id
        );

        return back()->with('thanhCong', "Đã mở khóa tài khoản {$taiKhoan->tenDangNhap} thành công.");
    }
}
