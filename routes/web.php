<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Blockchain\SepoliaTransactionController;
use App\Http\Controllers\CoQuan\DuyetHoSoController;
use App\Http\Controllers\LoThuoc\LoThuocController;
use App\Http\Controllers\XacThuc\DangKyToChucController;
use App\Http\Controllers\XacThuc\DangNhapController;
use App\Http\Controllers\XacThuc\HoSoController;
use App\Http\Controllers\QuanTri\NhatKyController;
use App\Http\Controllers\QuanTri\TaiKhoanController;

/*
|--------------------------------------------------------------------------
| Web Routes - Hệ thống Truy xuất nguồn gốc Dược phẩm PharmaChain
|--------------------------------------------------------------------------
*/

// --- 1. NHÓM CÔNG KHAI ---
Route::get('/', fn() => view('nguoiTieuDung.trangChu'))->name('home');
Route::get('/tra-cuu-nguon-goc', fn() => view('nguoiTieuDung.traCuuNguonGoc'))->name('tra-cuu');
Route::get('/lich-su-tra-cuu', fn() => view('nguoiTieuDung.lichSuTraCuu'));
Route::get('/bao-cao-nghi-van', fn() => view('nguoiTieuDung.baoCaoNghiVan'));

// --- 2. NHÓM XÁC THỰC (GUEST) ---
Route::middleware('guest')->group(function () {
    Route::get('/dang-nhap', [DangNhapController::class, 'show'])->name('login');
    Route::post('/dang-nhap', [DangNhapController::class, 'store'])->name('login.store');

    Route::get('/dang-ky-to-chuc', fn() => view('nguoiTieuDung.dangKyToChuc'))->name('dangKyToChuc.form');
    Route::post('/dang-ky-to-chuc', [DangKyToChucController::class, 'store'])->name('dangKyToChuc.store');

    Route::get('/dang-ky-to-chuc/nop-lai/{toChuc}', [DangKyToChucController::class, 'resubmitForm'])->name('dangKyToChuc.resubmitForm');
    Route::post('/dang-ky-to-chuc/nop-lai/{toChuc}', [DangKyToChucController::class, 'resubmitStore'])->name('dangKyToChuc.resubmitStore');
});

Route::post('/dang-xuat', [DangNhapController::class, 'logout'])->name('logout')->middleware('auth');

// --- 3. NHÓM BẢO VỆ BỞI AUTH & TRẠNG THÁI TÀI KHOẢN ---
Route::middleware(['auth', 'tai.khoan.hoat.dong'])->group(function () {

    // Trang cá nhân & Đổi mật khẩu
    Route::get('/thong-tin-ca-nhan', [HoSoController::class, 'index'])->name('ho-so.index');
    Route::put('/thong-tin-ca-nhan/mat-khau', [HoSoController::class, 'updatePassword'])->name('ho-so.updatePassword');
    Route::get('/ho-so/tai-lieu/{taiLieu}', [HoSoController::class, 'downloadDocument'])->name('ho-so.downloadDocument');

    // --- CƠ QUAN QUẢN LÝ ---
    Route::middleware(['kiem.tra.vai.tro:CO_QUAN_QUAN_LY'])->prefix('co-quan')->group(function () {
        // Phê duyệt tài khoản tổ chức & sản phẩm
        Route::get('/phe-duyet-tai-khoan', [DuyetHoSoController::class, 'index'])->name('co-quan.phe-duyet-tai-khoan');
        Route::post('/phe-duyet-to-chuc/{toChuc}', [DuyetHoSoController::class, 'approveOrganization'])->name('co-quan.approve-organization');
        Route::post('/phe-duyet-san-pham/{sanPham}', [DuyetHoSoController::class, 'approveProduct'])->name('co-quan.approve-product');

        // Quản lý quyền hạn & ví On-chain (Thu hồi, Khôi phục, Đổi ví)
        Route::get('/quan-ly-quyen-han', [DuyetHoSoController::class, 'quanLyQuyenHan'])->name('co-quan.quan-ly-quyen-han');
        Route::post('/thu-hoi-quyen/{toChuc}', [DuyetHoSoController::class, 'thuHoiQuyenToChuc'])->name('co-quan.thu-hoi-quyen');
        Route::post('/khoi-phuc-quyen/{toChuc}', [DuyetHoSoController::class, 'khoiPhucQuyenToChuc'])->name('co-quan.khoi-phuc-quyen');
        Route::post('/doi-vi-moi/{toChuc}', [DuyetHoSoController::class, 'doiViMoi'])->name('co-quan.doi-vi-moi');

        // Trang tính năng khác
        Route::get('/xet-duyet-san-pham', fn() => view('coQuanQuanLy.xetDuyetSanPham'));
        Route::get('/quan-ly-thu-hoi', fn() => view('coQuanQuanLy.quanLyThuHoi'));
        Route::get('/thong-ke-bao-cao', fn() => view('coQuanQuanLy.thongKeBaoCao'));
        Route::get('/xu-ly-nghi-van', fn() => view('coQuanQuanLy.xuLyNghiVan'));
    });

    // --- NHÀ SẢN XUẤT ---
    Route::middleware(['auth'])->prefix('nha-san-xuat')->name('nha-san-xuat.')->group(function () {
        // 1. Giao diện Quản lý Lô thuốc & API danh sách
        Route::get('/quan-ly-lo-thuoc', [LoThuocController::class, 'index'])->name('lo-thuoc.index');
        Route::get('/api/danh-sach-lo-thuoc', [LoThuocController::class, 'getDanhSachAPI'])->name('lo-thuoc.api');

        // 2. API Khởi tạo lô thuốc mới & Tính toán 4 mã bytes32
        Route::post('/api/tao-lo-thuoc', [LoThuocController::class, 'store'])->name('lo-thuoc.store');

        // 3. API Tiếp nhận & Lưu mã txHash do MetaMask Pop-up gửi về sau khi xác nhận
        Route::post('/api/lo-thuoc/{id}/luu-txhash', [LoThuocController::class, 'saveTxHash'])->name('lo-thuoc.luu-txhash');

        // 4. Trang xem chi tiết & Xác minh đồng bộ Sepolia
        Route::get('/lo-thuoc/{id}/dong-bo-sepolia', [SepoliaTransactionController::class, 'showSyncPage'])->name('lo-thuoc.dong-bo');
        Route::post('/lo-thuoc/{id}/dong-bo-sepolia', [SepoliaTransactionController::class, 'verifyAndSave'])->name('lo-thuoc.dong-bo.luu');

        Route::get('/quan-ly-san-pham', fn() => view('nhaSanXuat.quanLySanPham'))->name('san-pham');
    });

    // --- NHÀ PHÂN PHỐI ---
    Route::middleware(['kiem.tra.vai.tro:NHA_PHAN_PHOI', 'to.chuc.da.duyet'])->prefix('nha-phan-phoi')->group(function () {
        Route::get('/quan-ly-lo-hang', fn() => view('nhaPhanPhoi.quanLyLoHang'))->name('nha-phan-phoi.lo-hang');
    });

    // --- NHÀ THUỐC ---
    Route::middleware(['kiem.tra.vai.tro:NHA_THUOC', 'to.chuc.da.duyet'])->prefix('nha-thuoc')->group(function () {
        Route::get('/tiep-nhan-lo-hang', fn() => view('nhaThuoc.tiepNhanLoHang'))->name('nha-thuoc.tiep-nhan');
        Route::get('/quan-ly-ban-hang', fn() => view('nhaThuoc.quanLyBanHang'))->name('nha-thuoc.ban-hang');
    });

    // --- QUẢN TRỊ VIÊN ---
    Route::middleware(['kiem.tra.vai.tro:QUAN_TRI_VIEN'])->prefix('quan-tri')->group(function () {
        Route::get('/quan-ly-tai-khoan', [TaiKhoanController::class, 'index'])->name('quan-tri.tai-khoan');
        Route::post('/tai-khoan/{taiKhoan}/khoa', [TaiKhoanController::class, 'lock'])->name('quan-tri.tai-khoan.khoa');
        Route::post('/tai-khoan/{taiKhoan}/mo-khoa', [TaiKhoanController::class, 'unlock'])->name('quan-tri.tai-khoan.mo-khoa');

        Route::get('/nhat-ky-he-thong', [NhatKyController::class, 'index'])->name('quan-tri.nhat-ky');
    });

    // --- BLOCKCHAIN API ---
    Route::prefix('blockchain')->group(function () {
        Route::get('/generate-hashes/{id}', [SepoliaTransactionController::class, 'generateBatchHashes'])->name('blockchain.generate-hashes');
        Route::post('/save-transaction', [SepoliaTransactionController::class, 'saveTransaction'])->name('blockchain.save-transaction');
    });
});
