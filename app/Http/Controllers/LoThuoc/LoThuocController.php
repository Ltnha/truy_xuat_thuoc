<?php

namespace App\Http\Controllers\LoThuoc;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoThuoc\LoThuocRequest;
use App\Exceptions\NghiepVuException;
use App\Models\SanPham;
use App\Services\LoThuoc\LoThuocService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoThuocController extends Controller
{
    public function __construct(protected LoThuocService $loThuocService)
    {
    }

    public function index(Request $request): View
    {
        $toChuc = $request->user()->toChuc;
        abort_if($toChuc === null, 403, 'Tài khoản chưa được liên kết với tổ chức.');

        return view('loThuoc.danhSach', [
            'loThuocs' => $this->loThuocService->danhSachChoNhaSanXuat($toChuc),
            'sanPhams' => SanPham::query()
                ->where('nhaSanXuatId', $toChuc->id)
                ->where('trangThaiDuyet', \App\Enums\ProductApprovalStatus::daDuyet)
                ->orderBy('tenSanPham')
                ->get(),
        ]);
    }

    public function store(LoThuocRequest $request): RedirectResponse
    {
        $toChuc = $request->user()->toChuc;
        abort_if($toChuc === null, 403, 'Tài khoản chưa được liên kết với tổ chức.');

        try {
            $this->loThuocService->dangKy($toChuc, $request->validated(), $request->user(), $request->ip());
        } catch (NghiepVuException $exception) {
            return back()->withInput()->with('loi', $exception->getMessage());
        }

        return back()->with('thanhCong', 'Đã đăng ký lô thuốc, sinh mã truy xuất và ghi nhận giao dịch.');
    }
}
