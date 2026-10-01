<?php

use App\Http\Middleware\KiemTraVaiTro;
use App\Http\Middleware\TaiKhoanHoatDong;
use App\Http\Middleware\ToChucDaDuyet;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'vaiTro' => KiemTraVaiTro::class,
            'taiKhoanHoatDong' => TaiKhoanHoatDong::class,
            'toChucDaDuyet' => ToChucDaDuyet::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('dangNhap'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Error handling scaffold intentionally left minimal.
    })
    ->create();