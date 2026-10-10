<?php

namespace App\Http\Controllers\XacThuc;

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
        $user->soDienThoai = $data['soDienThoai'] ?? null;
        $user->save();

        return back()->with('thanhCong', 'Đã cập nhật thông tin liên hệ.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'matKhauHienTai' => ['required', 'current_password'],
            'matKhauMoi' => ['required', 'string', 'min:8', 'confirmed', 'different:matKhauHienTai'],
        ]);

        $user = $request->user();
        $user->matKhau = $data['matKhauMoi'];
        $user->save();
        $request->session()->regenerate();

        return back()->with('thanhCong', 'Đã đổi mật khẩu.');
    }

    public function downloadDocument(Request $request, TaiLieuDangKy $taiLieu): StreamedResponse
    {
        $organizationId = $request->user()->toChucId;
        abort_if($organizationId === null, 404);
        abort_unless(
            $taiLieu->yeuCauDangKy()->where('toChucId', $organizationId)->exists(),
            404
        );
        abort_unless(Storage::disk('local')->exists($taiLieu->duongDan), 404);

        return Storage::disk('local')->download($taiLieu->duongDan, $taiLieu->tenTaiLieu);
    }
}
