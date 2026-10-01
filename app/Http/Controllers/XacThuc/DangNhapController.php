<?php

namespace App\Http\Controllers\XacThuc;

use App\Http\Controllers\Controller;
use App\Http\Requests\XacThuc\DangNhapRequest;
use App\Services\XacThuc\XacThucService;

class DangNhapController extends Controller
{
    public function __construct(protected XacThucService $xacThucService)
    {
    }

    public function show()
    {
        return view('xacThuc.dangNhap');
    }

    public function store(DangNhapRequest $request)
    {
        return $this->xacThucService->dangNhap($request->validated());
    }
}
