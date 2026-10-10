<?php

namespace App\Http\Controllers\CoQuan;

use App\Enums\AccountStatus;
use App\Enums\OrganizationApprovalStatus;
use App\Enums\ProductApprovalStatus;
use App\Http\Controllers\Controller;
use App\Models\NhatKyHeThong;
use App\Models\SanPham;
use App\Models\TaiKhoan;
use App\Models\ToChuc;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DuyetHoSoController extends Controller
{
    public function index(): View
    {
        return view('coQuan.duyetHoSo', [
            'toChucs' => ToChuc::query()
                ->with(['yeuCauDangKys' => fn ($query) => $query->latest('id')])
                ->where('trangThaiDuyet', OrganizationApprovalStatus::choDuyet)
                ->latest('id')
                ->paginate(20, ['*'], 'toChucPage'),
            'sanPhams' => SanPham::query()
                ->with(['nhaSanXuat', 'yeuCauDangKySanPhams' => fn ($query) => $query->latest('id')])
                ->where('trangThaiDuyet', ProductApprovalStatus::choDuyet)
                ->latest('id')
                ->paginate(20, ['*'], 'sanPhamPage'),
        ]);
    }

    public function approveOrganization(Request $request, ToChuc $toChuc): RedirectResponse
    {
        $duLieu = $request->validate([
            'trangThaiDuyet' => ['required', 'in:DA_DUYET,TU_CHOI'],
            'lyDo' => ['nullable', 'required_if:trangThaiDuyet,TU_CHOI', 'string', 'max:500'],
        ]);

        abort_unless($toChuc->trangThaiDuyet === OrganizationApprovalStatus::choDuyet, 409, 'Tổ chức không còn ở trạng thái chờ duyệt.');

        DB::transaction(function () use ($request, $toChuc, $duLieu): void {
            $status = OrganizationApprovalStatus::from($duLieu['trangThaiDuyet']);
            $toChuc->trangThaiDuyet = $status;
            $toChuc->save();

            $yeuCau = $toChuc->yeuCauDangKys()->latest('id')->first();
            if ($yeuCau) {
                $yeuCau->trangThai = $status->value;
                $yeuCau->nguoiDuyetId = $request->user()->id;
                $yeuCau->ngayXuLy = today();
                $yeuCau->lyDoTuChoi = $duLieu['lyDo'] ?? null;
                $yeuCau->save();
            }

            if ($status === OrganizationApprovalStatus::daDuyet) {
                TaiKhoan::query()
                    ->where('toChucId', $toChuc->id)
                    ->where('trangThai', AccountStatus::pending)
                    ->update(['trangThai' => AccountStatus::active]);
            }

            NhatKyHeThong::query()->create([
                'taiKhoanId' => $request->user()->id,
                'hanhDong' => $status === OrganizationApprovalStatus::daDuyet ? 'DUYET_TO_CHUC' : 'TU_CHOI_TO_CHUC',
                'thoiGian' => now(),
                'noiDung' => "Xử lý tổ chức {$toChuc->tenToChuc}".(! empty($duLieu['lyDo']) ? ': '.$duLieu['lyDo'] : '.'),
                'diaChiIP' => $request->ip(),
            ]);
        });

        return back()->with('thanhCong', 'Đã cập nhật kết quả xét duyệt tổ chức.');
    }

    public function approveProduct(Request $request, SanPham $sanPham): RedirectResponse
    {
        $duLieu = $request->validate([
            'trangThaiDuyet' => ['required', 'in:DA_DUYET,TU_CHOI'],
            'lyDo' => ['nullable', 'required_if:trangThaiDuyet,TU_CHOI', 'string', 'max:500'],
        ]);

        abort_unless($sanPham->trangThaiDuyet === ProductApprovalStatus::choDuyet, 409, 'Sản phẩm không còn ở trạng thái chờ duyệt.');

        DB::transaction(function () use ($request, $sanPham, $duLieu): void {
            $status = ProductApprovalStatus::from($duLieu['trangThaiDuyet']);
            $sanPham->trangThaiDuyet = $status;
            $sanPham->save();

            $yeuCau = $sanPham->yeuCauDangKySanPhams()->latest('id')->first();
            if ($yeuCau) {
                $yeuCau->trangThai = $status->value;
                $yeuCau->nguoiXuLyId = $request->user()->id;
                $yeuCau->ngayXuLy = today();
                $yeuCau->lyDoTuChoi = $duLieu['lyDo'] ?? null;
                $yeuCau->save();
            }

            NhatKyHeThong::query()->create([
                'taiKhoanId' => $request->user()->id,
                'hanhDong' => $status === ProductApprovalStatus::daDuyet ? 'DUYET_SAN_PHAM' : 'TU_CHOI_SAN_PHAM',
                'thoiGian' => now(),
                'noiDung' => "Xử lý sản phẩm {$sanPham->tenSanPham}".(! empty($duLieu['lyDo']) ? ': '.$duLieu['lyDo'] : '.'),
                'diaChiIP' => $request->ip(),
            ]);
        });

        return back()->with('thanhCong', 'Đã cập nhật kết quả xét duyệt sản phẩm.');
    }
}
