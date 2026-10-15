<?php

use App\Http\Middleware\KiemTraVaiTro;
use App\Http\Middleware\TaiKhoanHoatDong;
use App\Http\Middleware\ToChucDaDuyet;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Đăng ký Alias Middleware cho hệ thống
        $middleware->alias([
            'tai.khoan.hoat.dong' => TaiKhoanHoatDong::class,
            'kiem.tra.vai.tro'    => KiemTraVaiTro::class,
            'to.chuc.da.duyet'    => ToChucDaDuyet::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
