<?php

namespace Tests\Unit;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Http\Middleware\KiemTraVaiTro;
use App\Http\Middleware\TaiKhoanHoatDong;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    public function test_role_middleware_accepts_the_role_enum_cast_from_the_account_model(): void
    {
        $request = Request::create('/nhaSanXuat/sanPham');
        $request->setUserResolver(fn () => (object) ['vaiTro' => Role::nhaSanXuat]);

        $response = (new KiemTraVaiTro())->handle($request, fn () => response('ok'), 'NHA_SAN_XUAT');

        $this->assertSame('ok', $response->getContent());
    }

    public function test_role_middleware_rejects_a_different_enum_role(): void
    {
        $request = Request::create('/quanTri/taiKhoan');
        $request->setUserResolver(fn () => (object) ['vaiTro' => Role::nhaSanXuat]);

        $this->expectException(HttpException::class);
        (new KiemTraVaiTro())->handle($request, fn () => response('ok'), 'QUAN_TRI_VIEN');
    }

    public function test_active_account_middleware_accepts_the_status_enum(): void
    {
        $request = Request::create('/');
        $request->setUserResolver(fn () => (object) ['trangThai' => AccountStatus::active]);

        $response = (new TaiKhoanHoatDong())->handle($request, fn () => response('ok'));

        $this->assertSame('ok', $response->getContent());
    }
}
