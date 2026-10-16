<?php

namespace App\Http\Controllers\XacThuc;

use App\Enums\AccountStatus;
use App\Enums\OrganizationApprovalStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\TaiKhoan;
use App\Models\ToChuc;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class DangNhapController extends Controller
{
    public function show(): View
    {
        return view('nguoiTieuDung.dangNhap');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tenDangNhap' => ['required', 'string'],
            'matKhau' => ['required', 'string'],
        ]);

        $taiKhoan = TaiKhoan::query()
            ->with('toChuc')
            ->where('tenDangNhap', $data['tenDangNhap'])
            ->first();

        if (! $taiKhoan || ! Hash::check($data['matKhau'], $taiKhoan->matKhau)) {
            return back()->withErrors(['tenDangNhap' => 'Tên đăng nhập hoặc mật khẩu không chính xác.'])->withInput();
        }

        // Kiểm tra xem tổ chức có bị TỪ CHỐI phê duyệt hay không (chỉ truy vấn YeuCauDangKy khi cần)
        if ($taiKhoan->toChuc && $taiKhoan->toChuc->trangThaiDuyet === OrganizationApprovalStatus::tuChoi) {
            $yeuCauCu = $taiKhoan->toChuc->yeuCauDangKys()->latest('id')->first();
            $lyDo = $yeuCauCu?->lyDoTuChoi ?? 'Hồ sơ pháp lý chưa đạt yêu cầu thẩm định.';

            return back()
                ->withErrors(['tenDangNhap' => "Hồ sơ đăng ký tài khoản tổ chức của bạn đã bị TỪ CHỐI xét duyệt. Lý do: {$lyDo}"])
                ->with('toChucTuChoiId', $taiKhoan->toChuc->id)
                ->withInput();
        }

        // Kiểm tra tài khoản PENDING
        if ($taiKhoan->trangThai === AccountStatus::pending) {
            return back()
                ->withErrors(['tenDangNhap' => 'Tài khoản của bạn đang trong quá trình chờ Cơ quan quản lý xét duyệt. Vui lòng quay lại sau.'])
                ->withInput();
        }

        // Kiểm tra tài khoản LOCKED
        if ($taiKhoan->trangThai === AccountStatus::locked) {
            $lyDo = $taiKhoan->lyDoKhoa ? " Lý do: {$taiKhoan->lyDoKhoa}" : '';
            return back()
                ->withErrors(['tenDangNhap' => "Tài khoản của bạn đã bị khóa.{$lyDo}"])
                ->withInput();
        }

        // Đăng nhập thành công
        Auth::login($taiKhoan);
        $request->session()->regenerate();

        $redirectUrl = match ($taiKhoan->vaiTro) {
            Role::nhaSanXuat => route('nha-san-xuat.lo-thuoc.index'),
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
