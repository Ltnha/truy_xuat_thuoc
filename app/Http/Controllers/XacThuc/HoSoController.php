<?php

namespace App\Http\Controllers\XacThuc;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\TaiLieuDangKy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HoSoController extends Controller
{
    public function updateContact(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'email' => ['required', 'email', 'max:100', Rule::unique('taiKhoan', 'email')->ignore($user->id)],
            'soDienThoai' => ['nullable', 'string', 'max:20'],
        ]);

        $user->email = $data['email'];
        // Cập nhật số điện thoại vào bảng toChuc nếu tài khoản thuộc về một tổ chức
        if ($user->toChuc) {
            $user->toChuc->soDienThoai = $data['soDienThoai'] ?? null;
            $user->toChuc->save();
        }
        $user->save();

        return back()->with('thanhCong', 'Đã cập nhật thông tin liên hệ thành công.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'matKhauHienTai' => ['required', 'current_password'],
            'matKhauMoi' => ['required', 'string', 'min:8', 'confirmed', 'different:matKhauHienTai'],
        ]);

        $user = $request->user();
        // Gán trực tiếp text thô vì Model TaiKhoan đã khai báo cast 'matKhau' => 'hashed'
        $user->matKhau = $data['matKhauMoi'];
        $user->save();
        $request->session()->regenerate();

        return back()->with('thanhCong', 'Đã đổi mật khẩu thành công.');
    }

    public function downloadDocument(Request $request, TaiLieuDangKy $taiLieu): StreamedResponse
    {
        $user = $request->user();
        $vaiTro = $user->vaiTro instanceof \BackedEnum ? $user->vaiTro->value : $user->vaiTro;

        // Cho phép Cơ quan quản lý hoặc chính doanh nghiệp sở hữu hồ sơ tải tệp
        if ($vaiTro !== Role::coQuanQuanLy->value) {
            $organizationId = $user->toChucId;
            abort_if($organizationId === null, 403, 'Bạn không có quyền truy cập tệp này.');
            abort_unless(
                $taiLieu->yeuCauDangKy()->where('toChucId', $organizationId)->exists(),
                403,
                'Tệp không thuộc sở hữu của tổ chức bạn.'
            );
        }

        // Kiểm tra tệp trên đĩa 'public' đồng bộ với DangKyToChucController
        abort_unless(Storage::disk('public')->exists($taiLieu->duongDan), 404, 'Tệp đính kèm không tồn tại trên máy chủ.');

        return Storage::disk('public')->download($taiLieu->duongDan, $taiLieu->tenTaiLieu);
    }
}
