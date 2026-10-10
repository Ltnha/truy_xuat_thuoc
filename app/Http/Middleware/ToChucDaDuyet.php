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
            return redirect()->route('dangNhap');
        }

        if ($request->user()->toChuc && $request->user()->toChuc->trangThaiDuyet !== OrganizationApprovalStatus::daDuyet) {
            abort(403, 'Tổ chức của bạn chưa được duyệt.');
        }

        return $next($request);
    }
}