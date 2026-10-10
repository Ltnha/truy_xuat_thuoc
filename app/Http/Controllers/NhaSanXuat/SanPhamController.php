<?php

namespace App\Http\Controllers\NhaSanXuat;

use App\Http\Controllers\Controller;
use App\Models\SanPham;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SanPhamController extends Controller
{
    public function index(Request $request): View
    {
        $toChucId = $request->user()->toChucId;

        abort_if($toChucId === null, 403, 'Tài khoản chưa được liên kết với tổ chức.');

        return view('sanPham.danhSach', [
            'sanPhams' => SanPham::query()
                ->with(['yeuCauDangKySanPhams' => fn ($query) => $query->latest('id')])
                ->where('nhaSanXuatId', $toChucId)
                ->latest('id')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $toChucId = $request->user()->toChucId;
        abort_if($toChucId === null, 403, 'Tài khoản chưa được liên kết với tổ chức.');

        $duLieu = $request->validate([
            'tenSanPham' => ['required', 'string', 'max:255'],
            'soDangKy' => ['required', 'string', 'max:50', 'unique:sanPham,soDangKy'],
            'hoatChat' => ['nullable', 'string', 'max:255'],
            'quyCachDongGoi' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($duLieu, $toChucId): void {
            $sanPham = SanPham::query()->create([
                ...$duLieu,
                'maSanPham' => (string) Str::uuid(),
                'nhaSanXuatId' => $toChucId,
                'trangThaiDuyet' => 'CHO_DUYET',
            ]);

            $sanPham->yeuCauDangKySanPhams()->create([
                'versionHoSo' => 1,
                'ngayGui' => today(),
                'trangThai' => 'CHO_DUYET',
            ]);
        });

        return back()->with('thanhCong', 'Đã gửi hồ sơ đăng ký sản phẩm đến cơ quan quản lý.');
    }

    public function update(Request $request, SanPham $sanPham): RedirectResponse
    {
        abort_unless((int) $sanPham->nhaSanXuatId === (int) $request->user()->toChucId, 404);
        abort_unless($sanPham->trangThaiDuyet?->value === 'TU_CHOI', 409, 'Chỉ được chỉnh sửa và nộp lại sản phẩm bị từ chối.');

        $duLieu = $request->validate([
            'tenSanPham' => ['required', 'string', 'max:255'],
            'soDangKy' => ['required', 'string', 'max:50', 'unique:sanPham,soDangKy,'.$sanPham->id],
            'hoatChat' => ['nullable', 'string', 'max:255'],
            'quyCachDongGoi' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($sanPham, $duLieu): void {
            $sanPham->update([...$duLieu, 'trangThaiDuyet' => 'CHO_DUYET']);
            $sanPham->yeuCauDangKySanPhams()->create([
                'versionHoSo' => (int) $sanPham->yeuCauDangKySanPhams()->max('versionHoSo') + 1,
                'ngayGui' => today(),
                'trangThai' => 'CHO_DUYET',
            ]);
        });

        return back()->with('thanhCong', 'Đã cập nhật và nộp lại hồ sơ sản phẩm.');
    }

    public function destroy(Request $request, SanPham $sanPham): RedirectResponse
    {
        abort_unless((int) $sanPham->nhaSanXuatId === (int) $request->user()->toChucId, 404);

        if ($sanPham->loThuocs()->exists() || $sanPham->yeuCauDangKySanPhams()->exists()) {
            return back()->with('loi', 'Không thể xóa sản phẩm đã có lô thuốc hoặc lịch sử hồ sơ. Hãy giữ hồ sơ để bảo toàn truy xuất.');
        }

        $sanPham->delete();

        return back()->with('thanhCong', 'Đã xóa bản nháp sản phẩm chưa phát sinh hồ sơ hoặc lô thuốc.');
    }
}
