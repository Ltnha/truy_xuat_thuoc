<?php

namespace App\Http\Controllers\ToChuc;

use App\Http\Controllers\Controller;
use App\Http\Requests\ToChuc\DuyetToChucRequest;
use App\Services\ToChuc\ToChucService;

class DuyetToChucController extends Controller
{
    public function __construct(protected ToChucService $toChucService)
    {
    }

    public function update(DuyetToChucRequest $request)
    {
        return $this->toChucService->duyet($request->validated());
    }
}
