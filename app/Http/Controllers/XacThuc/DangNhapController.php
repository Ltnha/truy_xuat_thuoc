<?php

namespace App\Http\Controllers\XacThuc;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\XacThuc\DangNhapRequest;
use App\Models\TaiKhoan;
use App\Services\XacThuc\XacThucService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DangNhapController extends Controller
{
    public function __construct(protected XacThucService $xacThucService) {}

    public function show(): View
    {
        return view('nguoiTieuDung.dangNhap');
    }

    public function store(DangNhapRequest $request): RedirectResponse
    {
        $ketQua = $this->xacThucService->dangNhap($request->validated());

        if (! $ketQua['thanhCong']) {
            return back()
                ->withErrors(['tenDangNhap' => $ketQua['thongBao']])
                ->withInput($request->only('tenDangNhap'));
        }

        $request->session()->regenerate();

        /** @var TaiKhoan $taiKhoan */
        $taiKhoan = Auth::user();

        // Chuyển hướng theo đúng vai trò thực tế
        $redirectUrl = match ($taiKhoan->vaiTro) {
            Role::nhaSanXuat => route('nha-san-xuat.lo-thuoc'),
            Role::nhaPhanPhoi => route('nha-phan-phoi.lo-hang'),
            Role::nhaThuoc => route('nha-thuoc.tiep-nhan'),
            Role::coQuanQuanLy => route('co-quan.phe-duyet-tai-khoan'),
            Role::quanTriVien => route('quan-tri.tai-khoan'),
            default => route('ho-so.index'),
        };

        return redirect($redirectUrl);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
