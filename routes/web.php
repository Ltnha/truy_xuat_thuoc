<?php

use Illuminate\Support\Facades\Route;

Route::get('/dangNhap', [\App\Http\Controllers\XacThuc\DangNhapController::class, 'show'])->name('dangNhap');
Route::post('/dangNhap', [\App\Http\Controllers\XacThuc\DangNhapController::class, 'store'])->name('dangNhap.store');
Route::post('/dangXuat', function (\Illuminate\Http\Request $request) {
    \Illuminate\Support\Facades\Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('dangNhap');
})->middleware('auth')->name('xacThuc.dangXuat');
Route::view('/dangKyToChuc', 'xacThuc.dangKyToChuc')->name('dangKyToChuc');
Route::post('/dangKyToChuc', [\App\Http\Controllers\XacThuc\DangKyToChucController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('dangKyToChuc.store');
Route::get('/dangKyToChuc/trangThai', [\App\Http\Controllers\XacThuc\DangKyToChucController::class, 'status'])->name('dangKyToChuc.trangThai');
Route::post('/dangKyToChuc/trangThai', [\App\Http\Controllers\XacThuc\DangKyToChucController::class, 'checkStatus'])
    ->middleware('throttle:5,1')
    ->name('dangKyToChuc.kiemTraTrangThai');

Route::prefix('trangChu')->name('cong.')->group(function () {
    Route::get('/', [\App\Http\Controllers\TraCuu\TraCuuController::class, 'home'])->name('truyXuat.trangChu');
});

Route::prefix('truyXuat')->name('cong.truyXuat.')->group(function () {
    Route::get('/xem/{ma?}', [\App\Http\Controllers\TraCuu\TraCuuController::class, 'show'])
        ->middleware('throttle:60,1')
        ->name('xem');
    Route::view('/lichSu', 'truyXuat.lichSu')->name('lichSu');
});
Route::get('/api/truy-xuat/{ma}', [\App\Http\Controllers\TraCuu\TraCuuController::class, 'api'])
    ->middleware('throttle:60,1')
    ->name('api.truyXuat');

Route::prefix('baoCao')->name('cong.baoCao.')->group(function () {
    Route::get('/gui', fn () => view('baoCao.anDanhGui'))->name('gui');
    Route::post('/gui', [\App\Http\Controllers\BaoCao\BaoCaoNghiVanController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('store');
    Route::get('/trangThai', [\App\Http\Controllers\BaoCao\BaoCaoNghiVanController::class, 'showStatus'])
        ->middleware('throttle:30,1')
        ->name('trangThai');
});

Route::middleware(['auth', 'taiKhoanHoatDong'])->group(function () {
    Route::get('/', function (\Illuminate\Http\Request $request) {
        return match ($request->user()->vaiTro) {
            \App\Enums\Role::nhaSanXuat => redirect()->route('nsx.sanPham.index'),
            \App\Enums\Role::nhaPhanPhoi => redirect()->route('chung.chuyenGiao.den'),
            \App\Enums\Role::nhaThuoc => redirect()->route('nt.tiepNhanLoHang'),
            \App\Enums\Role::coQuanQuanLy => redirect()->route('coQuan.thongKe'),
            \App\Enums\Role::quanTriVien => redirect()->route('admin.taiKhoan.danhSach'),
        };
    })->name('home');

    Route::view('/hoSo', 'xacThuc.hoSo')->name('hoSo');
    Route::patch('/hoSo/thongTinLienHe', [\App\Http\Controllers\XacThuc\HoSoController::class, 'updateContact'])->name('hoSo.capNhat');
    Route::patch('/hoSo/matKhau', [\App\Http\Controllers\XacThuc\HoSoController::class, 'updatePassword'])->name('hoSo.matKhau');
    Route::get('/hoSo/taiLieu/{taiLieu}', [\App\Http\Controllers\XacThuc\HoSoController::class, 'downloadDocument'])->name('hoSo.taiLieu');
    Route::get('/baoCaoNghiVan/minhChung/{minhChung}', [\App\Http\Controllers\BaoCao\BaoCaoNghiVanController::class, 'downloadEvidence'])->name('baoCao.minhChung');

    Route::prefix('nhaSanXuat')->name('nsx.')->middleware(['vaiTro:NHA_SAN_XUAT', 'toChucDaDuyet'])->group(function () {
        Route::get('/sanPham', [\App\Http\Controllers\NhaSanXuat\SanPhamController::class, 'index'])->name('sanPham.index');
        Route::post('/sanPham', [\App\Http\Controllers\NhaSanXuat\SanPhamController::class, 'store'])->name('sanPham.store');
        Route::put('/sanPham/{sanPham}', [\App\Http\Controllers\NhaSanXuat\SanPhamController::class, 'update'])->name('sanPham.update');
        Route::delete('/sanPham/{sanPham}', [\App\Http\Controllers\NhaSanXuat\SanPhamController::class, 'destroy'])->name('sanPham.destroy');
        Route::get('/loThuoc', [\App\Http\Controllers\LoThuoc\LoThuocController::class, 'index'])->name('loThuoc.index');
        Route::post('/loThuoc', [\App\Http\Controllers\LoThuoc\LoThuocController::class, 'store'])->name('loThuoc.store');
    });

    Route::prefix('chuoiCungUng')->name('chung.')->middleware('toChucDaDuyet')->group(function () {
        Route::get('/tonKho', [\App\Http\Controllers\ChuoiCungUng\ChuyenGiaoController::class, 'inventory'])
            ->middleware('vaiTro:NHA_SAN_XUAT,NHA_PHAN_PHOI,NHA_THUOC')
            ->name('tonKho');

        Route::prefix('chuyenGiao')->name('chuyenGiao.')->group(function () {
            Route::get('/di', [\App\Http\Controllers\ChuoiCungUng\ChuyenGiaoController::class, 'index'])
                ->defaults('huong', 'di')
                ->middleware('vaiTro:NHA_SAN_XUAT,NHA_PHAN_PHOI')
                ->name('di');
            Route::post('/di', [\App\Http\Controllers\ChuoiCungUng\ChuyenGiaoController::class, 'store'])
                ->middleware('vaiTro:NHA_SAN_XUAT,NHA_PHAN_PHOI');
            Route::get('/den', [\App\Http\Controllers\ChuoiCungUng\ChuyenGiaoController::class, 'index'])
                ->defaults('huong', 'den')
                ->middleware('vaiTro:NHA_PHAN_PHOI,NHA_THUOC')
                ->name('den');
            Route::patch('/{chuyenGiao}/nhan', [\App\Http\Controllers\ChuoiCungUng\ChuyenGiaoController::class, 'receive'])
                ->middleware('vaiTro:NHA_PHAN_PHOI,NHA_THUOC')
                ->name('nhan');
        });
    });

    Route::prefix('nhaThuoc')->name('nt.')->middleware(['vaiTro:NHA_THUOC', 'toChucDaDuyet'])->group(function () {
        Route::get('/tiepNhanLoHang', [\App\Http\Controllers\ChuoiCungUng\ChuyenGiaoController::class, 'index'])
            ->defaults('huong', 'den')
            ->name('tiepNhanLoHang');
        Route::get('/banLe', [\App\Http\Controllers\ChuoiCungUng\BanLeController::class, 'index'])->name('banLe');
        Route::post('/banLe', [\App\Http\Controllers\ChuoiCungUng\BanLeController::class, 'store'])->name('banLe.store');
    });

    Route::prefix('baoCaoNghiVan')->name('chung.baoCao.')->middleware([
        'vaiTro:NHA_PHAN_PHOI,NHA_THUOC',
        'toChucDaDuyet',
    ])->group(function () {
        Route::get('/', [\App\Http\Controllers\BaoCao\BaoCaoNghiVanController::class, 'index'])->name('danhSach');
    });

    Route::prefix('quanTri')->name('admin.')->middleware('vaiTro:QUAN_TRI_VIEN')->group(function () {
        Route::get('/taiKhoan', [\App\Http\Controllers\QuanTri\TaiKhoanController::class, 'index'])->name('taiKhoan.danhSach');
        Route::patch('/taiKhoan/{taiKhoan}/trangThai', [\App\Http\Controllers\QuanTri\TaiKhoanController::class, 'updateStatus'])->name('taiKhoan.updateStatus');
        Route::get('/nhatKy', [\App\Http\Controllers\QuanTri\NhatKyController::class, 'index'])->name('nhatKy.danhSach');
    });

    Route::get('/coQuan/thongKe', fn () => view('quanTri.thongKe'))
        ->middleware('vaiTro:CO_QUAN_QUAN_LY')
        ->name('coQuan.thongKe');
    Route::get('/coQuan/baoCaoNghiVan', [\App\Http\Controllers\BaoCao\BaoCaoNghiVanController::class, 'index'])
        ->middleware('vaiTro:CO_QUAN_QUAN_LY')
        ->name('cq.baoCao.danhSach');
    Route::patch('/coQuan/baoCaoNghiVan/{baoCao}/xuLy', [\App\Http\Controllers\BaoCao\BaoCaoNghiVanController::class, 'process'])
        ->middleware('vaiTro:CO_QUAN_QUAN_LY')
        ->name('cq.baoCao.xuLy');
    Route::get('/coQuan/duyetHoSo', [\App\Http\Controllers\CoQuan\DuyetHoSoController::class, 'index'])
        ->middleware('vaiTro:CO_QUAN_QUAN_LY')
        ->name('cq.duyetHoSo');
    Route::patch('/coQuan/toChuc/{toChuc}/duyet', [\App\Http\Controllers\CoQuan\DuyetHoSoController::class, 'approveOrganization'])
        ->middleware('vaiTro:CO_QUAN_QUAN_LY')
        ->name('cq.toChuc.approve');
    Route::patch('/coQuan/sanPham/{sanPham}/duyet', [\App\Http\Controllers\CoQuan\DuyetHoSoController::class, 'approveProduct'])
        ->middleware('vaiTro:CO_QUAN_QUAN_LY')
        ->name('cq.sanPham.approve');

    Route::middleware('vaiTro:QUAN_TRI_VIEN')->prefix('api/quan-tri')->group(function () {
        Route::get('/tai-khoan', [\App\Http\Controllers\Api\DashboardDataController::class, 'taiKhoan']);
    });
    Route::middleware('vaiTro:NHA_SAN_XUAT')->prefix('api/nha-san-xuat')->group(function () {
        Route::get('/san-pham', [\App\Http\Controllers\Api\DashboardDataController::class, 'sanPham']);
        Route::get('/lo-thuoc', [\App\Http\Controllers\Api\DashboardDataController::class, 'loThuoc']);
    });
    Route::get('/api/thong-ke', [\App\Http\Controllers\Api\DashboardDataController::class, 'thongKe'])
        ->middleware('vaiTro:CO_QUAN_QUAN_LY');
});