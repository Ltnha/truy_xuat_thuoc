<?php

namespace App\Http\Controllers\ChuoiCungUng;

use App\Exceptions\NghiepVuException;
use App\Http\Controllers\Controller;
use App\Services\ChuoiCungUng\BanLeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BanLeController extends Controller
{
    public function __construct(private BanLeService $banLeService)
    {
    }

    public function index(Request $request): View
    {
        $organizationId = $request->user()->toChucId;
        abort_if($organizationId === null, 403, 'Tài khoản chưa được liên kết với tổ chức.');

        $tonKho = DB::table('tonKhoLo as tk')
            ->join('loThuoc as lt', 'lt.id', '=', 'tk.loThuocId')
            ->join('sanPham as sp', 'sp.id', '=', 'lt.sanPhamId')
            ->where('tk.toChucId', $organizationId)
            ->where('tk.soLuong', '>', 0)
            ->where('lt.trangThai', '!=', 'RECALLED')
            ->whereDate('lt.hanSuDung', '>=', today())
            ->select('lt.id', 'lt.maLoNghiepVu', 'sp.tenSanPham', 'tk.soLuong', 'lt.hanSuDung')
            ->orderBy('lt.hanSuDung')
            ->get();
        $banLes = DB::table('banLe as bl')
            ->join('donViSanPham as dv', 'dv.banLeId', '=', 'bl.id')
            ->join('loThuoc as lt', 'lt.id', '=', 'dv.loThuocId')
            ->join('sanPham as sp', 'sp.id', '=', 'lt.sanPhamId')
            ->where('bl.toChucId', $organizationId)
            ->select('bl.maBanLe', 'bl.ngayBan', 'bl.txHash', 'dv.maDonVi', 'lt.maLoNghiepVu', 'sp.tenSanPham')
            ->orderByDesc('bl.id')
            ->limit(50)
            ->get();

        return view('chuoiCungUng.banLe', compact('tonKho', 'banLes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'maDinhDanh' => ['required', 'string', 'max:100'],
        ]);
        $organization = $request->user()->toChuc;
        abort_if($organization === null, 403, 'Tài khoản chưa được liên kết với tổ chức.');

        try {
            $this->banLeService->banLe($data['maDinhDanh'], $organization, $request->user(), $request->ip());
        } catch (NghiepVuException $exception) {
            return back()->withInput()->with('loi', $exception->getMessage());
        }

        return back()->with('thanhCong', 'Đã ghi nhận bán lẻ và cập nhật tồn kho.');
    }
}
