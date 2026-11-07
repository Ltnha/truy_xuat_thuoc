<?php

namespace App\Http\Controllers\NguoiTieuDung;

use App\Http\Controllers\Controller;
use App\Models\LoThuoc;
use App\Models\SanPham;
use App\Models\ToChuc;
use App\Models\DonViSanPham;
use App\Models\ThuHoiLoHang;
use App\Models\BlockchainTransaction;
use Illuminate\Http\Request;

class TrangChuController extends Controller
{
    public function index(Request $request)
    {
        // 1. Số liệu thống kê tổng hợp thực tế từ CSDL
        $tongLoDangKy = LoThuoc::count();
        $tongSanPhamDuyet = SanPham::where('trangThaiDuyet', 'DA_DUYET')->count();
        $tongToChucDuyet = ToChuc::where('trangThaiDuyet', 'DA_DUYET')->count();
        $tongDonViDaBan = DonViSanPham::where('trangThai', 'DISPENSED')
            ->orWhereNotNull('banLeId')
            ->count();
        $tongLoThuHoi = ThuHoiLoHang::count();

        // Phân loại cơ sở đã duyệt
        $thongKeToChuc = [
            'NHA_SAN_XUAT' => ToChuc::where('trangThaiDuyet', 'DA_DUYET')->where('loaiToChuc', 'NHA_SAN_XUAT')->count(),
            'NHA_PHAN_PHOI' => ToChuc::where('trangThaiDuyet', 'DA_DUYET')->where('loaiToChuc', 'NHA_PHAN_PHOI')->count(),
            'NHA_THUOC'     => ToChuc::where('trangThaiDuyet', 'DA_DUYET')->where('loaiToChuc', 'NHA_THUOC')->count(),
        ];

        // 2. Danh sách lô thuốc bị thu hồi (loThuoc.trangThai = RECALLED)
        $danhSachThuHoi = ThuHoiLoHang::with(['loThuoc.sanPham', 'loThuoc.toChuc'])
            ->latest('ngayThuHoi')
            ->get()
            ->map(function ($item) {
                $lo = $item->loThuoc;
                // Lấy txHash confirmed từ blockchainTransaction liên quan tới lô thuốc thu hồi
                $tx = BlockchainTransaction::where('loThuocId', $lo?->id)
                    ->where('trangThai', 'CONFIRMED')
                    ->latest('id')
                    ->first();

                return [
                    'id' => $item->id,
                    'maLo' => $lo?->maLoNghiepVu ?? $lo?->maLo ?? 'N/A',
                    'tenThuoc' => $lo?->sanPham?->tenSanPham ?? 'N/A',
                    'nhaSanXuat' => $lo?->toChuc?->tenToChuc ?? 'Chưa xác định',
                    'lyDo' => $item->lyDo,
                    'ngayThuHoi' => $item->ngayThuHoi,
                    'txHash' => $tx?->txHash ?? null,
                ];
            });

        // 3. Danh mục sản phẩm thuốc đã duyệt công khai (Đã sửa ->with('nhaSanXuat') chuẩn theo Model SanPham)
        $danhSachSanPham = SanPham::where('trangThaiDuyet', 'DA_DUYET')
            ->with('nhaSanXuat')
            ->latest('id')
            ->get()
            ->map(function ($sp) {
                return [
                    'id' => $sp->id,
                    'tenSanPham' => $sp->tenSanPham,
                    'soDangKy' => $sp->soDangKy,
                    'thanhPhan' => $sp->thanhPhan ?? '---',
                    'hamLuong' => $sp->hamLuong ?? '---',
                    'dangBaoChe' => $sp->dangBaoChe ?? '---',
                    'nhaSanXuat' => $sp->nhaSanXuat?->tenToChuc ?? 'Chưa xác định',
                ];
            });

        // 4. Danh sách các cơ sở tham gia đã được duyệt (Chỉ hiển thị Tên, Loại, Địa chỉ)
        $danhSachToChuc = ToChuc::where('trangThaiDuyet', 'DA_DUYET')
            ->select('id', 'tenToChuc', 'loaiToChuc', 'diaChi')
            ->orderBy('loaiToChuc')
            ->get();

        return view('nguoiTieuDung.trangChu', compact(
            'tongLoDangKy',
            'tongSanPhamDuyet',
            'tongToChucDuyet',
            'tongDonViDaBan',
            'tongLoThuHoi',
            'thongKeToChuc',
            'danhSachThuHoi',
            'danhSachSanPham',
            'danhSachToChuc'
        ));
    }
}
