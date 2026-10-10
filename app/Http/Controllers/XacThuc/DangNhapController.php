<?php

namespace App\Http\Controllers\XacThuc;

use App\Http\Controllers\Controller;
use App\Http\Requests\XacThuc\DangNhapRequest;
use App\Services\XacThuc\XacThucService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DangNhapController extends Controller
{
    public function __construct(protected XacThucService $xacThucService)
    {
    }

    public function show(): View
    {
        return view('xacThuc.dangNhap');
    }

    public function store(DangNhapRequest $request): RedirectResponse
    {
        if (! $this->xacThucService->dangNhap($request->validated())) {
            return back()
                ->withErrors(['tenDangNhap' => 'Tên đăng nhập hoặc mật khẩu không chính xác, hoặc tài khoản chưa được kích hoạt.'])
                ->onlyInput('tenDangNhap');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }
}
