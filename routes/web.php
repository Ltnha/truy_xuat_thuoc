<?php

use Illuminate\Support\Facades\Route;

// --- CONTROLLERS XÁC THỰC & HỒ SƠ ---
use App\Http\Controllers\XacThuc\DangNhapController;
use App\Http\Controllers\XacThuc\DangKyToChucController;
use App\Http\Controllers\XacThuc\HoSoController;

// --- CONTROLLERS NGHIỆP VỤ LÔ THUỐC & SẢN PHẨM ---
use App\Http\Controllers\NhaSanXuat\SanPhamController;
use App\Http\Controllers\LoThuoc\LoThuocController;
use App\Http\Controllers\LoThuoc\ChiTietLoThuocController;
use App\Http\Controllers\LoThuoc\ChuyenGiaoController;

// --- CONTROLLERS CƠ QUAN QUẢN LÝ & QUẢN TRỊ ---
use App\Http\Controllers\CoQuan\DuyetHoSoController;
use App\Http\Controllers\CoQuan\DuyetSanPhamController;
use App\Http\Controllers\QuanTri\TaiKhoanController;
use App\Http\Controllers\QuanTri\NhatKyController;
use App\Http\Controllers\NhaPhanPhoi\ChuyenGiaoController as NppChuyenGiaoController;
use App\Http\Controllers\NhaPhanPhoi\QuanLyLoHangController;
use App\Http\Controllers\NhaThuoc\TiepNhanLoHangController;

// --- CONTROLLERS BÁO CÁO & BLOCKCHAIN ---
use App\Http\Controllers\BaoCao\BaoCaoNghiVanController;
use App\Http\Controllers\Blockchain\SepoliaTransactionController;

/*
|--------------------------------------------------------------------------
| Web Routes - Hệ thống Truy xuất nguồn gốc Dược phẩm PharmaChain
|--------------------------------------------------------------------------
*/

// =========================================================================
// 1. NHÓM CÔNG KHAI (PUBLIC - NGƯỜI TIÊU DÙNG)
// =========================================================================
Route::get('/', fn() => view('nguoiTieuDung.trangChu'))->name('home');
Route::get('/tra-cuu-nguon-goc', fn() => view('nguoiTieuDung.traCuuNguonGoc'))->name('tra-cuu');
Route::get('/lich-su-tra-cuu', fn() => view('nguoiTieuDung.lichSuTraCuu'));

// Báo cáo nghi vấn công khai & API Tra cứu cho Người tiêu dùng
Route::get('/bao-cao-nghi-van', [BaoCaoNghiVanController::class, 'showConsumerForm'])->name('bao-cao.public.form');
Route::post('/api/bao-cao-nghi-van/gui-public', [BaoCaoNghiVanController::class, 'storePublic'])->name('api.bao-cao.public.store');
Route::get('/api/bao-cao-nghi-van/tra-cuu/{maBaoCao}', [BaoCaoNghiVanController::class, 'traCuuPublic'])->name('api.bao-cao.public.tra-cuu');


// =========================================================================
// 2. NHÓM XÁC THỰC (GUEST - CHƯA ĐĂNG NHẬP)
// =========================================================================
Route::middleware('guest')->group(function () {
    Route::get('/dang-nhap', [DangNhapController::class, 'show'])->name('login');
    Route::post('/dang-nhap', [DangNhapController::class, 'store'])->name('login.store');

    Route::get('/dang-ky-to-chuc', fn() => view('nguoiTieuDung.dangKyToChuc'))->name('dangKyToChuc.form');
    Route::post('/dang-ky-to-chuc', [DangKyToChucController::class, 'store'])->name('dangKyToChuc.store');

    Route::get('/dang-ky-to-chuc/nop-lai/{toChuc}', [DangKyToChucController::class, 'resubmitForm'])->name('dangKyToChuc.resubmitForm');
    Route::post('/dang-ky-to-chuc/nop-lai/{toChuc}', [DangKyToChucController::class, 'resubmitStore'])->name('dangKyToChuc.resubmitStore');
});

Route::post('/dang-xuat', [DangNhapController::class, 'logout'])->name('logout')->middleware('auth');


// =========================================================================
// 3. NHÓM ĐÃ ĐĂNG NHẬP (AUTH & TÀI KHOẢN HOẠT ĐỘNG)
// =========================================================================
Route::middleware(['auth', 'tai.khoan.hoat.dong'])->group(function () {

    // --- TRANG CÁ NHÂN & ĐỔI MẬT KHẨU ---
    Route::get('/thong-tin-ca-nhan', [HoSoController::class, 'index'])->name('ho-so.index');
    Route::put('/thong-tin-ca-nhan/mat-khau', [HoSoController::class, 'updatePassword'])->name('ho-so.updatePassword');
    Route::get('/ho-so/tai-lieu/{taiLieu}', [HoSoController::class, 'downloadDocument'])->name('ho-so.downloadDocument');

    // --- QUẢN LÝ BÁO CÁO NGHI VẤN CHUNG CHO CÁC TỔ CHỨC ---
    Route::get('/quan-ly-bao-cao-nghi-van', [BaoCaoNghiVanController::class, 'indexAuth'])->name('bao-cao.auth.index');
    Route::post('/api/quan-ly-bao-cao-nghi-van/gui', [BaoCaoNghiVanController::class, 'storeAuth'])->name('api.bao-cao.auth.store');

    // ════════════════════════════════════════════════════════════════
    // CƠ QUAN QUẢN LÝ
    // ════════════════════════════════════════════════════════════════
    Route::middleware(['kiem.tra.vai.tro:CO_QUAN_QUAN_LY'])->prefix('co-quan')->group(function () {
        // Phê duyệt tài khoản tổ chức
        Route::get('/phe-duyet-tai-khoan', [DuyetHoSoController::class, 'index'])->name('co-quan.phe-duyet-tai-khoan');
        Route::post('/phe-duyet-to-chuc/{toChuc}', [DuyetHoSoController::class, 'approveOrganization'])->name('co-quan.approve-organization');
        Route::post('/phe-duyet-san-pham/{sanPham}', [DuyetHoSoController::class, 'approveProduct'])->name('co-quan.approve-product');

        // Quản lý quyền hạn & Ví On-chain (Thu hồi, Khôi phục, Đổi ví)
        Route::get('/quan-ly-quyen-han', [DuyetHoSoController::class, 'quanLyQuyenHan'])->name('co-quan.quan-ly-quyen-han');
        Route::post('/thu-hoi-quyen/{toChuc}', [DuyetHoSoController::class, 'thuHoiQuyenToChuc'])->name('co-quan.thu-hoi-quyen');
        Route::post('/khoi-phuc-quyen/{toChuc}', [DuyetHoSoController::class, 'khoiPhucQuyenToChuc'])->name('co-quan.khoi-phuc-quyen');
        Route::post('/doi-vi-moi/{toChuc}', [DuyetHoSoController::class, 'doiViMoi'])->name('co-quan.doi-vi-moi');

        // Xét duyệt sản phẩm lưu hành
        Route::get('/xet-duyet-san-pham', [DuyetSanPhamController::class, 'index'])->name('co-quan.xet-duyet-san-pham');
        Route::post('/xet-duyet-san-pham/{yeuCau}/phe-duyet', [DuyetSanPhamController::class, 'pheDuyet'])->name('co-quan.xet-duyet-san-pham.phe-duyet');
        Route::post('/xet-duyet-san-pham/{yeuCau}/tu-choi', [DuyetSanPhamController::class, 'tuChoi'])->name('co-quan.xet-duyet-san-pham.tu-choi');

        // Các trang tính năng quản lý khác
        Route::get('/quan-ly-thu-hoi', fn() => view('coQuanQuanLy.quanLyThuHoi'));
        Route::get('/thong-ke-bao-cao', fn() => view('coQuanQuanLy.thongKeBaoCao'));
        Route::get('/xu-ly-nghi-van', fn() => view('coQuanQuanLy.xuLyNghiVan'));
    });

    // ════════════════════════════════════════════════════════════════
    // NHÀ SẢN XUẤT
    // ════════════════════════════════════════════════════════════════
    Route::middleware(['auth', 'kiem.tra.vai.tro:NHA_SAN_XUAT', 'to.chuc.da.duyet'])
        ->prefix('nha-san-xuat')
        ->name('nha-san-xuat.')
        ->group(function () {
            // Quản lý sản phẩm
            Route::get('/quan-ly-san-pham', [SanPhamController::class, 'index'])->name('sanPham.index');
            Route::post('/quan-ly-san-pham', [SanPhamController::class, 'store'])->name('sanPham.store');
            Route::put('/quan-ly-san-pham/{sanPham}/nop-lai', [SanPhamController::class, 'resubmit'])->name('sanPham.resubmit');

            // Quản lý Lô thuốc & Tạo lô
            Route::get('/quan-ly-lo-thuoc', [LoThuocController::class, 'index'])->name('lo-thuoc.index');
            Route::get('/api/danh-sach-lo-thuoc', [LoThuocController::class, 'getDanhSachAPI'])->name('lo-thuoc.api');
            Route::post('/api/tao-lo-thuoc', [LoThuocController::class, 'store'])->name('lo-thuoc.store');
            Route::post('/api/lo-thuoc/{id}/luu-txhash', [LoThuocController::class, 'saveTxHash'])->name('lo-thuoc.luu-txhash');

            // Chuyển giao lô thuốc
            Route::get('/api/nha-phan-phoi', [ChuyenGiaoController::class, 'danhSachNhaPhanPhoi'])->name('lo-thuoc.api.npp');
            Route::post('/api/chuyen-giao', [ChuyenGiaoController::class, 'store'])->name('lo-thuoc.chuyen-giao.store');

            // Chi Tiết Lô Thuốc & APIs On-chain
            Route::get('/lo-thuoc/{id}/chi-tiet', [ChiTietLoThuocController::class, 'show'])->name('lo-thuoc.chi-tiet');
            Route::get('/api/lo-thuoc/{id}/chi-tiet', [ChiTietLoThuocController::class, 'getChiTietAPI'])->name('lo-thuoc.api.chi-tiet');
            Route::post('/api/lo-thuoc/{id}/gui-blockchain', [ChiTietLoThuocController::class, 'guiBlockchain'])->name('lo-thuoc.api.gui-blockchain');

            // Đồng bộ giao dịch Sepolia
            Route::get('/lo-thuoc/{id}/dong-bo-sepolia', [SepoliaTransactionController::class, 'showSyncPage'])->name('lo-thuoc.dong-bo');
            Route::post('/lo-thuoc/{id}/dong-bo-sepolia', [SepoliaTransactionController::class, 'verifyAndSave'])->name('lo-thuoc.dong-bo.luu');

            // Báo cáo nghi vấn của Nhà sản xuất
            Route::get('/bao-cao-nghi-van', [BaoCaoNghiVanController::class, 'indexAuth'])->name('bao-cao');
        });

    // ════════════════════════════════════════════════════════════════
    // NHÀ PHÂN PHỐI
    // ════════════════════════════════════════════════════════════════
    Route::middleware(['kiem.tra.vai.tro:NHA_PHAN_PHOI', 'to.chuc.da.duyet'])
        ->prefix('nha-phan-phoi')
        ->group(function () {
            // Trang chính
            Route::get('/quan-ly-lo-hang', [QuanLyLoHangController::class, 'index'])
                ->name('nha-phan-phoi.lo-hang');

            // API dữ liệu thật
            Route::get('/api/cho-tiep-nhan', [QuanLyLoHangController::class, 'danhSachChoTiepNhan'])
                ->name('nha-phan-phoi.api.cho-tiep-nhan');
            Route::get('/api/kho', [QuanLyLoHangController::class, 'danhSachKho'])
                ->name('nha-phan-phoi.api.kho');
            Route::get('/api/nha-thuoc', [QuanLyLoHangController::class, 'danhSachNhaThuoc'])
                ->name('nha-phan-phoi.api.nha-thuoc');
            Route::post('/api/chuyen-giao', [QuanLyLoHangController::class, 'taoChuyenGiao'])
                ->name('nha-phan-phoi.api.chuyen-giao');
            Route::post('/api/tiep-nhan/{id}', [QuanLyLoHangController::class, 'tiepNhan'])
                ->name('nha-phan-phoi.api.tiep-nhan');

            Route::get('/bao-cao-nghi-van', [\App\Http\Controllers\BaoCao\BaoCaoNghiVanController::class, 'indexAuth'])
                ->name('nha-phan-phoi.bao-cao');
        });

    // ════════════════════════════════════════════════════════════════
    // NHÀ THUỐC
    // ════════════════════════════════════════════════════════════════
    Route::middleware(['kiem.tra.vai.tro:NHA_THUOC', 'to.chuc.da.duyet'])
        ->prefix('nha-thuoc')
        ->group(function () {
            // Trang chính
            Route::get('/tiep-nhan-lo-hang', [TiepNhanLoHangController::class, 'index'])
                ->name('nha-thuoc.tiep-nhan');

            // API dữ liệu thật
            Route::get('/api/cho-xac-nhan', [TiepNhanLoHangController::class, 'danhSachChoXacNhan'])
                ->name('nha-thuoc.api.cho-xac-nhan');
            Route::get('/api/da-tiep-nhan', [TiepNhanLoHangController::class, 'danhSachDaTiepNhan'])
                ->name('nha-thuoc.api.da-tiep-nhan');
            Route::post('/api/xac-nhan/{id}', [TiepNhanLoHangController::class, 'xacNhan'])
                ->name('nha-thuoc.api.xac-nhan');

            Route::get('/quan-ly-ban-hang', fn () => view('nhaThuoc.quanLyBanHang'))
                ->name('nha-thuoc.ban-hang');
            Route::get('/bao-cao-nghi-van', [\App\Http\Controllers\BaoCao\BaoCaoNghiVanController::class, 'indexAuth'])
                ->name('nha-thuoc.bao-cao');
        });


    // ════════════════════════════════════════════════════════════════
    // QUẢN TRỊ VIÊN HỆ THỐNG
    // ════════════════════════════════════════════════════════════════
    Route::middleware(['kiem.tra.vai.tro:QUAN_TRI_VIEN'])
        ->prefix('quan-tri')
        ->group(function () {
            Route::get('/quan-ly-tai-khoan', [TaiKhoanController::class, 'index'])->name('quan-tri.tai-khoan');
            Route::post('/tai-khoan/{taiKhoan}/khoa', [TaiKhoanController::class, 'lock'])->name('quan-tri.tai-khoan.khoa');
            Route::post('/tai-khoan/{taiKhoan}/mo-khoa', [TaiKhoanController::class, 'unlock'])->name('quan-tri.tai-khoan.mo-khoa');

            Route::get('/nhat-ky-he-thong', [NhatKyController::class, 'index'])->name('quan-tri.nhat-ky');
        });

    // ════════════════════════════════════════════════════════════════
    // BLOCKCHAIN UTILITY APIS
    // ════════════════════════════════════════════════════════════════
    Route::prefix('blockchain')->group(function () {
        Route::get('/generate-hashes/{id}', [SepoliaTransactionController::class, 'generateBatchHashes'])->name('blockchain.generate-hashes');
        Route::post('/save-transaction', [SepoliaTransactionController::class, 'saveTransaction'])->name('blockchain.save-transaction');
    });
});
