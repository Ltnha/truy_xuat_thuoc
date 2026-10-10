<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class KiemTraVaiTro
{
    public function handle(Request $request, Closure $next, string ...$vaiTro): Response
    {
        if (! $request->user()) {
            return redirect()->route('dangNhap');
        }

        $taiKhoan = $request->user();

        $vaiTroTaiKhoan = $taiKhoan->vaiTro instanceof \BackedEnum
            ? $taiKhoan->vaiTro->value
            : $taiKhoan->vaiTro;

        if ($vaiTro !== [] && ! in_array($vaiTroTaiKhoan, $vaiTro, true)) {
            abort(403, 'Bạn không có quyền truy cập chức năng này.');
        }

        return $next($request);
    }
}