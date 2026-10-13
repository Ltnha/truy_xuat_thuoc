<?php 
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (Định tuyến giao diện)
|--------------------------------------------------------------------------
*/

// --- 1. NHÓM NGƯỜI TIÊU DÙNG ---
Route::get('/', function () {
    return view('nguoiTieuDung.trangChu'); // Trỏ tới resources/views/nguoiTieuDung/trangChu.blade.php
});

Route::get('/tra-cuu-nguon-goc', function () {
    return view('nguoiTieuDung.traCuuNguonGoc'); // Trỏ tới resources/views/nguoiTieuDung/traCuuNguonGoc.blade.php
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
    return view('nhaSanXuat.quanLyLoThuoc'); // Trỏ tới resources/views/nhaSanXuat/quanLyLoThuoc.blade.php
});

Route::get('/nha-san-xuat/quan-ly-san-pham', function () {
    return view('nhaSanXuat.quanLySanPham');
});


// --- 3. NHÓM CƠ QUAN QUẢN LÝ ---
Route::get('/co-quan/phe-duyet-tai-khoan', function () {
    return view('coQuanQuanLy.pheDuyetTaiKhoanToChuc'); // Trỏ tới resources/views/coQuanQuanLy/pheDuyetTaiKhoanToChuc.blade.php
});

Route::get('/co-quan/thong-ke-bao-cao', function () {
    return view('coQuanQuanLy.thongKeBaoCao');
});


// --- 4. TRANG DÙNG CHUNG ---
Route::get('/thong-tin-ca-nhan', function () {
    return view('trangDungChung.thongTinCaNhan');
});