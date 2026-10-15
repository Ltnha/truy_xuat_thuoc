<?php

namespace App\Http\Middleware;

use App\Enums\OrganizationApprovalStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ToChucDaDuyet
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->route('login'); // Sửa 'dangNhap' -> 'login'
        }

        $user = $request->user();

        // Kiểm tra an toàn: Nếu không có tổ chức HOẶC tổ chức chưa được duyệt -> Chặn ngay
        if (! $user->toChuc || $user->toChuc->trangThaiDuyet !== OrganizationApprovalStatus::daDuyet) {
            abort(403, 'Tổ chức của bạn chưa được Cơ quan quản lý phê duyệt.');
        }

        return $next($request);
    }
}
