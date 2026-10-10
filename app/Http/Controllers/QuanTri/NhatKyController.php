<?php

namespace App\Http\Controllers\QuanTri;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class NhatKyController extends Controller
{
    public function index(): View
    {
        $nhatKys = DB::table('nhatKyHeThong as nk')
            ->join('taiKhoan as tk', 'tk.id', '=', 'nk.taiKhoanId')
            ->select('nk.id', 'nk.hanhDong', 'nk.thoiGian', 'nk.noiDung', 'nk.diaChiIP', 'tk.tenDangNhap')
            ->latest('nk.id')
            ->paginate(30);

        return view('quanTri.nhatKy', compact('nhatKys'));
    }
}
