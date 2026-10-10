<?php

namespace App\Http\Controllers\QuanTri;

use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
use App\Models\TaiKhoan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TaiKhoanController extends Controller
{
    public function index(): View
    {
        return view('quanTri.taiKhoan', [
            'taiKhoans' => TaiKhoan::query()->with('toChuc')->latest('id')->paginate(25),
        ]);
    }

    public function updateStatus(Request $request, TaiKhoan $taiKhoan): RedirectResponse
    {
        abort_if((int) $request->user()->id === (int) $taiKhoan->id, 409, 'Bạn không thể tự khóa tài khoản đang sử dụng.');

        $duLieu = $request->validate([
            'trangThai' => ['required', 'in:ACTIVE,LOCKED'],
            'lyDo' => ['required', 'string', 'max:500'],
        ]);

        abort_unless(
            in_array($taiKhoan->trangThai, [AccountStatus::active, AccountStatus::locked], true),
            409,
            'Chỉ tài khoản đang hoạt động hoặc đang khóa mới được thay đổi trạng thái.'
        );

        DB::transaction(function () use ($request, $taiKhoan, $duLieu): void {
            $taiKhoan->update(['trangThai' => $duLieu['trangThai']]);
            DB::table('nhatKyHeThong')->insert([
                'taiKhoanId' => $request->user()->id,
                'hanhDong' => $duLieu['trangThai'] === 'LOCKED' ? 'KHOA_TAI_KHOAN' : 'MO_KHOA_TAI_KHOAN',
                'thoiGian' => now(),
                'noiDung' => "Tài khoản {$taiKhoan->tenDangNhap}: {$duLieu['lyDo']}",
                'diaChiIP' => $request->ip(),
            ]);
        });

        return back()->with('thanhCong', 'Đã cập nhật trạng thái tài khoản và ghi nhật ký kiểm toán.');
    }
}
