<?php

namespace App\Http\Controllers\LoThuoc;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoThuoc\LoThuocRequest;
use App\Services\LoThuoc\LoThuocService;

class LoThuocController extends Controller
{
    public function __construct(protected LoThuocService $loThuocService)
    {
    }

    public function index()
    {
        return view('loThuoc.danhSach');
    }

    public function store(LoThuocRequest $request)
    {
        return $this->loThuocService->tao($request->validated());
    }
}
