<?php

namespace App\Http\Controllers\NguoiTieuDung;

use App\Http\Controllers\Controller;
use App\Models\BlockchainTransaction;
use App\Models\SanPham;
use App\Models\ThuHoiLoHang;
use App\Models\ToChuc;
use Illuminate\Http\Request;

class TrangChuController extends Controller
{
    public function index(Request $request)
    {
        $danhSachThuHoi = ThuHoiLoHang::with(['loThuoc.sanPham', 'loThuoc.toChuc'])
            ->latest('ngayThuHoi')->get()->map(function ($item) {
                $lo = $item->loThuoc;
                $tx = BlockchainTransaction::where('loThuocId', $lo?->id)
                    ->where('trangThai', 'CONFIRMED')->latest('id')->first();

                return [
                    'id' => $item->id,
                    'maLo' => $lo?->maLoNghiepVu ?? $lo?->maLo ?? 'N/A',
                    'tenThuoc' => $lo?->sanPham?->tenSanPham ?? 'N/A',
                    'nhaSanXuat' => $lo?->toChuc?->tenToChuc ?? 'Chưa xác định',
                    'lyDo' => $item->lyDo,
                    'ngayThuHoi' => $item->ngayThuHoi,
                    'txHash' => $tx?->txHash,
                ];
            });

        return view('nguoiTieuDung.trangChu', compact('danhSachThuHoi'));
    }

    public function mangLuoi(Request $request)
    {
        $tongSanPhamDuyet = SanPham::where('trangThaiDuyet', 'DA_DUYET')->count();
        $tongToChucDuyet = ToChuc::where('trangThaiDuyet', 'DA_DUYET')->count();

        $danhSachSanPham = SanPham::where('trangThaiDuyet', 'DA_DUYET')
            ->with('nhaSanXuat')->latest('id')->get()->map(fn ($sp) => [
                'id' => $sp->id,
                'tenSanPham' => $sp->tenSanPham,
                'soDangKy' => $sp->soDangKy,
                'thanhPhan' => $sp->thanhPhan ?? '---',
                'hamLuong' => $sp->hamLuong ?? '---',
                'dangBaoChe' => $sp->dangBaoChe ?? '---',
                'nhaSanXuat' => $sp->nhaSanXuat?->tenToChuc ?? 'Chưa xác định',
            ]);

        $danhSachToChuc = ToChuc::where('trangThaiDuyet', 'DA_DUYET')
            ->select('id', 'tenToChuc', 'loaiToChuc', 'diaChi')
            ->orderBy('loaiToChuc')->get();

        return view('nguoiTieuDung.mangLuoi', compact(
            'tongSanPhamDuyet',
            'tongToChucDuyet',
            'danhSachSanPham',
            'danhSachToChuc'
        ));
    }
}
