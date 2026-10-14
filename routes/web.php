<?php 
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Kiểm thử toàn bộ giao diện Blade hệ thống PharmaChain
|--------------------------------------------------------------------------
*/

// --- 1. NHÓM NGƯỜI TIÊU DÙNG & CÔNG KHAI ---
Route::get('/', function () {
    return view('nguoiTieuDung.trangChu');
});

Route::get('/tra-cuu-nguon-goc', function () {
    return view('nguoiTieuDung.traCuuNguonGoc');
});

Route::get('/lich-su-tra-cuu', function () {
    return view('nguoiTieuDung.lichSuTraCuu');
});

Route::get('/bao-cao-nghi-van', function () {
    return view('nguoiTieuDung.baoCaoNghiVan');
});

Route::get('/dang-nhap', function () {
    return view('nguoiTieuDung.dangNhap');
});

Route::get('/dang-ky-to-chuc', function () {
    return view('nguoiTieuDung.dangKyToChuc');
});


// --- 2. NHÓM NHÀ SẢN XUẤT ---
Route::get('/nha-san-xuat/quan-ly-lo-thuoc', function () {
    return view('nhaSanXuat.quanLyLoThuoc');
});

Route::get('/nha-san-xuat/quan-ly-san-pham', function () {
    return view('nhaSanXuat.quanLySanPham');
});


// --- 3. NHÓM NHÀ PHÂN PHỐI ---
Route::get('/nha-phan-phoi/quan-ly-lo-hang', function () {
    return view('nhaPhanPhoi.quanLyLoHang');
});


// --- 4. NHÓM NHÀ THUỐC ---
Route::get('/nha-thuoc/tiep-nhan-lo-hang', function () {
    return view('nhaThuoc.tiepNhanLoHang');
});

Route::get('/nha-thuoc/quan-ly-ban-hang', function () {
    return view('nhaThuoc.quanLyBanHang');
});


// --- 5. NHÓM QUẢN TRỊ VIÊN ---
Route::get('/quan-tri/quan-ly-tai-khoan', function () {
    return view('quanTriVien.quanLyTaiKhoan');
});

Route::get('/quan-tri/nhat-ky-he-thong', function () {
    return view('quanTriVien.nhatKyHeThong');
});


// --- 6. NHÓM CƠ QUAN QUẢN LÝ ---
Route::get('/co-quan/phe-duyet-tai-khoan', function () {
    return view('coQuanQuanLy.pheDuyetTaiKhoanToChuc');
});

Route::get('/co-quan/xet-duyet-san-pham', function () {
    return view('coQuanQuanLy.xetDuyetSanPham'); // Kiểm tra lại tên file nếu là xetDuyetSanPham.blade.php
});

Route::get('/co-quan/quan-ly-quyen-han', function () {
    return view('coQuanQuanLy.quanLyQuyenHan');
});

Route::get('/co-quan/quan-ly-thu-hoi', function () {
    return view('coQuanQuanLy.quanLyThuHoi');
});

Route::get('/co-quan/thong-ke-bao-cao', function () {
    return view('coQuanQuanLy.thongKeBaoCao');
});

Route::get('/co-quan/xu-ly-nghi-van', function () {
    return view('coQuanQuanLy.xuLyNghiVan');
});


// --- 7. TRANG DÙNG CHUNG ---
Route::get('/thong-tin-ca-nhan', function () {
    return view('trangDungChung.thongTinCaNhan');
});
Route::get('/quan-ly-bao-cao-nghi-van', function () {
    return view('trangDungChung.quanLyBaoCaoNghiVan');
});