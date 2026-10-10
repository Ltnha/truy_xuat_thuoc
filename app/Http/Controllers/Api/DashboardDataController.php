<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardDataController extends Controller
{
    public function taiKhoan(): JsonResponse
    {
        $taiKhoans = DB::table('taiKhoan as tk')
            ->leftJoin('toChuc as tc', 'tc.id', '=', 'tk.toChucId')
            ->select('tk.id', 'tk.tenDangNhap', 'tk.email', 'tk.vaiTro', 'tk.trangThai', 'tk.ngayTao', 'tc.tenToChuc')
            ->orderByDesc('tk.id')
            ->get();

        return response()->json([
            'data' => $taiKhoans->map(function ($row): array {
                return [
                    'id' => (int) $row->id,
                    'tenDangNhap' => $row->tenDangNhap,
                    'email' => $row->email ?? '',
                    'vaiTro' => $row->vaiTro,
                    'tenToChuc' => $row->tenToChuc ?? 'Hệ thống',
                    'ngayTao' => $row->ngayTao ? date('Y-m-d H:i:s', strtotime($row->ngayTao)) : null,
                    'trangThai' => $row->trangThai,
                ];
            })->all(),
        ]);
    }

    public function sanPham(Request $request): JsonResponse
    {
        abort_if($request->user()->toChucId === null, 403, 'Tài khoản chưa được liên kết với tổ chức.');

        $sanPhams = DB::table('sanPham as sp')
            ->leftJoin('toChuc as tc', 'tc.id', '=', 'sp.nhaSanXuatId')
            ->select('sp.id', 'sp.tenSanPham', 'sp.soDangKy', 'sp.hoatChat', 'sp.quyCachDongGoi', 'sp.trangThaiDuyet', 'tc.tenToChuc')
            ->where('sp.nhaSanXuatId', $request->user()->toChucId)
            ->orderByDesc('sp.id')
            ->get();

        $yeuCau = DB::table('yeuCauDangKySanPham as ycdk')
            ->join('sanPham as sp', 'sp.id', '=', 'ycdk.sanPhamId')
            ->leftJoin('taiKhoan as tk', 'tk.id', '=', 'ycdk.nguoiXuLyId')
            ->where('sp.nhaSanXuatId', $request->user()->toChucId)
            ->select('ycdk.*', 'tk.tenDangNhap as nguoiXuLy')
            ->orderByDesc('ycdk.id')
            ->get();

        $lichSuTheoSanPham = $yeuCau->groupBy('sanPhamId');

        return response()->json([
            'data' => $sanPhams->map(function ($row) use ($lichSuTheoSanPham): array {
                $lichSu = $lichSuTheoSanPham->get($row->id, collect());
                return [
                    'id' => (string) $row->id,
                    'tenSanPham' => $row->tenSanPham,
                    'soDangKy' => $row->soDangKy,
                    'thanhPhan' => $row->hoatChat ?? '',
                    'hamLuong' => $row->quyCachDongGoi ?? '',
                    'dangBaoChe' => $row->quyCachDongGoi ?? '',
                    'trangThai' => $row->trangThaiDuyet,
                    'txHash' => '',
                    'lichSuNop' => $lichSu->reverse()->values()->map(function ($lan, $index) use ($row): array {
                        return [
                            'lan' => $index + 1,
                            'ngayNop' => $lan->ngayGui ? date('d/m/Y', strtotime((string) $lan->ngayGui)) : 'Chưa có dữ liệu',
                            'thongTinKhai' => [
                                'ten' => $row->tenSanPham,
                                'soDK' => $row->soDangKy,
                                'thanhPhan' => $row->hoatChat ?? '',
                                'hamLuong' => $row->quyCachDongGoi ?? '',
                                'dangBaoChe' => $row->quyCachDongGoi ?? '',
                            ],
                            'ketQua' => $lan->trangThai,
                            'nguoiXuLy' => $lan->nguoiXuLy ?? 'Đang chờ xử lý',
                            'lyDoTuChoi' => $lan->lyDoTuChoi ?? '',
                        ];
                    })->all(),
                ];
            })->all(),
        ]);
    }

    public function loThuoc(Request $request): JsonResponse
    {
        abort_if($request->user()->toChucId === null, 403, 'Tài khoản chưa được liên kết với tổ chức.');

        $loThuocs = DB::table('loThuoc as lt')
            ->join('sanPham as sp', 'sp.id', '=', 'lt.sanPhamId')
            ->leftJoin('toChuc as tc', 'tc.id', '=', 'lt.toChucId')
            ->select(
                'lt.id',
                'lt.maLoNghiepVu as maLo',
                'lt.sanPhamId',
                'lt.soLuong',
                'lt.ngaySanXuat',
                'lt.hanSuDung',
                'lt.trangThai',
                'sp.tenSanPham',
                'tc.tenToChuc'
            )
            ->where('lt.toChucId', $request->user()->toChucId)
            ->orderByDesc('lt.id')
            ->get();

        $chuyenGiao = DB::table('chuyenGiao as cg')
            ->leftJoin('toChuc as benNhan', 'benNhan.id', '=', 'cg.benNhanId')
            ->select('cg.loThuocId', 'benNhan.tenToChuc as benNhan', 'cg.soLuong', 'cg.thoiGianKhoiTao', 'cg.thoiGianXacNhan', 'cg.trangThai', 'cg.txHash')
            ->orderByDesc('cg.id')
            ->get();

        $chuyenGiaoTheoLo = $chuyenGiao->groupBy('loThuocId');

        return response()->json([
            'data' => $loThuocs->map(function ($row) use ($chuyenGiaoTheoLo): array {
                $giaoDich = $chuyenGiaoTheoLo->get($row->id, collect());

                return [
                    'maLo' => $row->maLo,
                    'tenSanPham' => $row->tenSanPham,
                    'ngaySX' => $row->ngaySanXuat,
                    'hanDung' => $row->hanSuDung,
                    'soLuongTong' => (int) $row->soLuong,
                    'trangThai' => $row->trangThai,
                    'txHash' => '',
                    'chuyenGiao' => $giaoDich->map(function ($item): array {
                        return [
                            'benNhan' => $item->benNhan ?? 'Chưa xác định',
                            'soLuong' => (int) $item->soLuong,
                            'thoiGianKhoiTao' => $item->thoiGianKhoiTao ? date('d/m/Y H:i', strtotime((string) $item->thoiGianKhoiTao)) : null,
                            'thoiGianXacNhan' => $item->thoiGianXacNhan ? date('d/m/Y H:i', strtotime((string) $item->thoiGianXacNhan)) : null,
                            'trangThai' => $item->trangThai,
                        ];
                    })->values()->all(),
                ];
            })->all(),
        ]);
    }

    public function thongKe(): JsonResponse
    {
        $taiKhoan = DB::table('taiKhoan')->count();
        $toChuc = DB::table('toChuc')->count();
        $sanPham = DB::table('sanPham')->count();
        $loThuoc = DB::table('loThuoc')->count();
        $taiKhoanActive = DB::table('taiKhoan')->where('trangThai', 'ACTIVE')->count();
        $loDangPhanPhoi = DB::table('loThuoc')->where('trangThai', 'IN_TRANSIT')->count();

        return response()->json([
            'taiKhoan' => $taiKhoan,
            'toChuc' => $toChuc,
            'sanPham' => $sanPham,
            'loThuoc' => $loThuoc,
            'taiKhoanActive' => $taiKhoanActive,
            'loDangPhanPhoi' => $loDangPhanPhoi,
        ]);
    }
}
