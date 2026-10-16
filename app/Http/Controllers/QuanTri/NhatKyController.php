<?php

namespace App\Http\Controllers\QuanTri;

use App\Http\Controllers\Controller;
use App\Models\NhatKyHeThong;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NhatKyController extends Controller
{
    public function index(Request $request): View
    {
        $tuKhoa = $request->input('tuKhoa');
        $khoangThoiGian = $request->input('khoangThoiGian', 'TAT_CA'); // Đổi mặc định sang TAT_CA để không ẩn dữ liệu cũ
        $hanhDong = $request->input('hanhDong', 'TAT_CA');
        $ketQua = $request->input('ketQua', 'TAT_CA');
        $tuNgay = $request->input('tuNgay');
        $denNgay = $request->input('denNgay');

        $query = NhatKyHeThong::with(['taiKhoan', 'doiTuong']);

        if ($khoangThoiGian === 'HOM_NAY') {
            $query->whereDate('thoiGian', today());
        } elseif ($khoangThoiGian === '7_NGAY') {
            $query->where('thoiGian', '>=', now()->subDays(7));
        } elseif ($khoangThoiGian === '30_NGAY') {
            $query->where('thoiGian', '>=', now()->subDays(30));
        } elseif ($khoangThoiGian === 'TUY_CHON' && $tuNgay && $denNgay) {
            $query->whereBetween('thoiGian', ["{$tuNgay} 00:00:00", "{$denNgay} 23:59:59"]);
        }

        if ($hanhDong !== 'TAT_CA') {
            $query->where('hanhDong', $hanhDong);
        }

        if ($ketQua !== 'TAT_CA') {
            $query->where('ketQua', $ketQua);
        }

        if ($tuKhoa) {
            $query->where(function ($q) use ($tuKhoa) {
                $q->where('chiTiet', 'like', "%{$tuKhoa}%")
                    ->orWhere('hanhDong', 'like', "%{$tuKhoa}%")
                    ->orWhereHas('taiKhoan', fn($sub) => $sub->where('tenDangNhap', 'like', "%{$tuKhoa}%"))
                    ->orWhereHas('doiTuong', fn($sub) => $sub->where('tenDangNhap', 'like', "%{$tuKhoa}%"));
            });
        }

        $nhatKys = $query->latest('id')->paginate(15)->withQueryString();

        return view('quanTriVien.nhatKyHeThong', compact('nhatKys', 'tuKhoa', 'khoangThoiGian', 'hanhDong', 'ketQua', 'tuNgay', 'denNgay'));
    }
}
