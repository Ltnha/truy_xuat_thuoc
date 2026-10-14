<?php

use App\Http\Controllers\CoQuan\DuyetHoSoController;
use App\Http\Controllers\XacThuc\DangKyToChucController;
use App\Http\Controllers\XacThuc\DangNhapController;
use App\Http\Controllers\XacThuc\HoSoController;
use Illuminate\Support\Facades\Route;

// --- 1. KHU VỰC CÔNG KHAI ---
Route::get('/', fn() => view('nguoiTieuDung.trangChu'))->name('home');
Route::get('/tra-cuu-nguon-goc', fn() => view('nguoiTieuDung.traCuuNguonGoc'));
Route::get('/lich-su-tra-cuu', fn() => view('nguoiTieuDung.lichSuTraCuu'));
Route::get('/bao-cao-nghi-van', fn() => view('nguoiTieuDung.baoCaoNghiVan'));

// --- 2. XÁC THỰC (GUEST) ---
Route::middleware('guest')->group(function () {
    Route::get('/dang-nhap', [DangNhapController::class, 'show'])->name('login');
    Route::post('/dang-nhap', [DangNhapController::class, 'store'])->name('login.store');

    Route::get('/dang-ky-to-chuc', fn() => view('nguoiTieuDung.dangKyToChuc'))->name('dangKyToChuc.form');
    Route::post('/dang-ky-to-chuc', [DangKyToChucController::class, 'store'])->name('dangKyToChuc.store');
});

Route::post('/dang-xuat', [DangNhapController::class, 'logout'])->name('logout')->middleware('auth');

// --- 3. KHU VỰC QUẢN LÝ (CẦN ĐĂNG NHẬP) ---
Route::middleware(['auth', 'tai.khoan.hoat.dong'])->group(function () {

    Route::get('/thong-tin-ca-nhan', fn() => view('trangDungChung.thongTinCaNhan'))->name('ho-so.index');
    Route::put('/thong-tin-ca-nhan/lien-he', [HoSoController::class, 'updateContact'])->name('ho-so.updateContact');
    Route::put('/thong-tin-ca-nhan/mat-khau', [HoSoController::class, 'updatePassword'])->name('ho-so.updatePassword');

    // Phê duyệt Cơ quan quản lý
    Route::middleware(['kiem.tra.vai.tro:CO_QUAN_QUAN_LY'])->prefix('co-quan')->group(function () {
        Route::get('/phe-duyet-tai-khoan', [DuyetHoSoController::class, 'index'])->name('co-quan.phe-duyet-tai-khoan');
        Route::post('/phe-duyet-to-chuc/{toChuc}', [DuyetHoSoController::class, 'approveOrganization'])->name('co-quan.approve-organization');
        Route::get('/xet-duyet-san-pham', fn() => view('coQuanQuanLy.xetDuyetSanPham'));
        Route::get('/quan-ly-quyen-han', fn() => view('coQuanQuanLy.quanLyQuyenHan'));
        Route::get('/quan-ly-thu-hoi', fn() => view('coQuanQuanLy.quanLyThuHoi'));
        Route::get('/thong-ke-bao-cao', fn() => view('coQuanQuanLy.thongKeBaoCao'));
        Route::get('/xu-ly-nghi-van', fn() => view('coQuanQuanLy.xuLyNghiVan'));
    });

    // Nhà sản xuất (Cần tổ chức đã duyệt)
    Route::middleware(['kiem.tra.vai.tro:NHA_SAN_XUAT', 'to.chuc.da.duyet'])->prefix('nha-san-xuat')->group(function () {
        Route::get('/quan-ly-lo-thuoc', fn() => view('nhaSanXuat.quanLyLoThuoc'))->name('nha-san-xuat.lo-thuoc');
        Route::get('/quan-ly-san-pham', fn() => view('nhaSanXuat.quanLySanPham'))->name('nha-san-xuat.san-pham');
    });

    // Nhà phân phối
    Route::middleware(['kiem.tra.vai.tro:NHA_PHAN_PHOI', 'to.chuc.da.duyet'])->prefix('nha-phan-phoi')->group(function () {
        Route::get('/quan-ly-lo-hang', fn() => view('nhaPhanPhoi.quanLyLoHang'))->name('nha-phan-phoi.lo-hang');
    });

    // Nhà thuốc
    Route::middleware(['kiem.tra.vai.tro:NHA_THUOC', 'to.chuc.da.duyet'])->prefix('nha-thuoc')->group(function () {
        Route::get('/tiep-nhan-lo-hang', fn() => view('nhaThuoc.tiepNhanLoHang'))->name('nha-thuoc.tiep-nhan');
        Route::get('/quan-ly-ban-hang', fn() => view('nhaThuoc.quanLyBanHang'))->name('nha-thuoc.ban-hang');
    });

    // Quản trị viên
    Route::middleware(['kiem.tra.vai.tro:QUAN_TRI_VIEN'])->prefix('quan-tri')->group(function () {
        Route::get('/quan-ly-tai-khoan', fn() => view('quanTriVien.quanLyTaiKhoan'))->name('quan-tri.tai-khoan');
        Route::get('/nhat-ky-he-thong', fn() => view('quanTriVien.nhatKyHeThong'))->name('quan-tri.nhat-ky');
    });
});
