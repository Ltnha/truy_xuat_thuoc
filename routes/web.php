<?php

use Illuminate\Support\Facades\Route;

Route::view('/dangNhap', 'xacThuc.dangNhap')->name('dangNhap');
Route::view('/dangKyToChuc', 'xacThuc.dangKyToChuc')->name('dangKyToChuc');
Route::view('/dangKyToChuc/trangThai', 'xacThuc.trangThaiDangKy')->name('dangKyToChuc.trangThai');

Route::middleware(['auth', 'taiKhoanHoatDong'])->group(function () {
    Route::get('/', fn () => redirect()->route('coQuan.thongKe'))->name('home');

    Route::middleware(['toChucDaDuyet'])->group(function () {
        Route::get('/coQuan/thongKe', fn () => view('quanTri.thongKe'))->name('coQuan.thongKe');
    });

    Route::get('/hoSo', fn () => view('xacThuc.hoSo'))->name('hoSo');
});