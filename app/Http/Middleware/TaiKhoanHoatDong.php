<?php
namespace App\Http\Middleware;

use App\Enums\AccountStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TaiKhoanHoatDong
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->route('dangNhap');
        }

        if ($request->user()->trangThai !== AccountStatus::active) {
            abort(403, 'Tài khoản hiện đang không hoạt động.');
        }

        return $next($request);
    }
}