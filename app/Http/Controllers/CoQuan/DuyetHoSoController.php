<?php

namespace App\Http\Controllers\CoQuan;

use App\Enums\AccountStatus;
use App\Enums\OrganizationApprovalStatus;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessOrganizationApprovalJob;
use App\Jobs\RevokeOrganizationRoleJob;
use App\Models\NhatKyHeThong;
use App\Models\TaiKhoan;
use App\Models\ToChuc;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DuyetHoSoController extends Controller
{
    /**
     * Hiển thị danh sách hồ sơ đăng ký tổ chức
     */
    public function index(Request $request): View
    {
        $tuKhoa = trim($request->input('tuKhoa', ''));
        $loaiToChuc = $request->input('loaiToChuc', 'TAT_CA');
        $trangThaiDuyet = $request->input('trangThaiDuyet', 'CHO_DUYET');

        $query = ToChuc::query()
            ->with([
                'yeuCauDangKys' => fn($q) => $q->latest('id'),
                'yeuCauDangKys.taiLieus',
                'taiKhoans',
                'blockchainIdentity',
            ]);

        if ($tuKhoa !== '') {
            $query->where(function ($q) use ($tuKhoa) {
                $q->where('tenToChuc', 'like', "%{$tuKhoa}%")
                    ->orWhere('maSoThue', 'like', "%{$tuKhoa}%")
                    ->orWhere('soGiayPhepDuoc', 'like', "%{$tuKhoa}%");
            });
        }

        if ($loaiToChuc !== 'TAT_CA' && in_array($loaiToChuc, ['NHA_SAN_XUAT', 'NHA_PHAN_PHOI', 'NHA_THUOC'], true)) {
            $query->where('loaiToChuc', $loaiToChuc);
        } else {
            $query->where('loaiToChuc', '<>', 'CO_QUAN_QUAN_LY');
        }

        if ($trangThaiDuyet !== 'TAT_CA') {
            $query->where('trangThaiDuyet', $trangThaiDuyet);
        }

        $toChucs = $query->latest('id')->paginate(15)->withQueryString();

        return view('coQuanQuanLy.pheDuyetTaiKhoanToChuc', compact('toChucs', 'tuKhoa', 'loaiToChuc', 'trangThaiDuyet'));
    }

    /**
     * Phê duyệt hoặc Từ chối hồ sơ tổ chức
     */
    public function approveOrganization(Request $request, ToChuc $toChuc): RedirectResponse
    {
        $duLieu = $request->validate([
            'trangThaiDuyet' => ['required', 'in:DA_DUYET,TU_CHOI'],
            'lyDo'           => ['nullable', 'required_if:trangThaiDuyet,TU_CHOI', 'string', 'max:500'],
        ]);

        // 1. TRƯỜNG HỢP PHÊ DUYỆT:
        if ($duLieu['trangThaiDuyet'] === 'DA_DUYET') {
            // Đẩy Job ngầm vào hàng đợi xử lý On-chain
            // Job sẽ tự sinh ví, nạp gas, gọi grantRole và chỉ chuyển toChuc.trangThaiDuyet = 'DA_DUYET' khi On-chain thành công 100%
            ProcessOrganizationApprovalJob::dispatch($toChuc->id, $request->user()->id);

            return back()->with('thanhCong', "Đã tiếp nhận yêu cầu phê duyệt tổ chức [{$toChuc->tenToChuc}]. Hệ thống đang khởi tạo Ví Custodial và cấp quyền On-chain ngầm.");
        }

        // 2. TRƯỜNG HỢP TỪ CHỐI:
        DB::transaction(function () use ($toChuc, $duLieu, $request): void {
            $status = OrganizationApprovalStatus::tuChoi;
            $toChuc->update(['trangThaiDuyet' => $status]);

            $yeuCau = $toChuc->yeuCauDangKys()->latest('id')->first();
            if ($yeuCau) {
                $yeuCau->update([
                    'trangThai'    => 'TU_CHOI',
                    'nguoiDuyetId' => $request->user()->id,
                    'ngayXuLy'     => now(),
                    'lyDoTuChoi'   => $duLieu['lyDo'] ?? null,
                ]);
            }

            $taiKhoanDaiDien = $toChuc->taiKhoans()->first();

            NhatKyHeThong::create([
                'taiKhoanId' => $request->user()->id,
                'doiTuongId' => $taiKhoanDaiDien?->id,
                'hanhDong'   => 'TU_CHOI_TO_CHUC',
                'ketQua'     => 'THANH_CONG',
                'thoiGian'   => now(),
                'chiTiet'    => "Từ chối hồ sơ đăng ký của tổ chức [{$toChuc->tenToChuc}]. Lý do: " . ($duLieu['lyDo'] ?? 'Không có'),
            ]);
        });

        return back()->with('thanhCong', "Đã từ chối hồ sơ đăng ký của tổ chức [{$toChuc->tenToChuc}].");
    }

    /**
     * Thu hồi quyền Tổ chức (Revoke Role On-chain)
     */
    public function thuHoiQuyenToChuc(Request $request, ToChuc $toChuc): RedirectResponse
    {
        DB::transaction(function () use ($request, $toChuc): void {
            // 1. Khóa Off-chain ngay lập tức
            $toChuc->update([
                'trangThaiDuyet' => OrganizationApprovalStatus::biThuHoi ?? 'BI_THU_HOI',
            ]);

            // Vô hiệu hóa tài khoản đăng nhập của doanh nghiệp
            $toChuc->taiKhoans()->update([
                'trangThai' => AccountStatus::locked,
            ]);

            // 2. Đẩy Job ngầm tước quyền On-chain (revokeRole)
            RevokeOrganizationRoleJob::dispatch($toChuc->id, $request->user()->id);

            NhatKyHeThong::create([
                'taiKhoanId' => $request->user()->id,
                'doiTuongId' => $toChuc->taiKhoans()->first()?->id,
                'hanhDong'   => 'THU_HOI_QUYEN_TO_CHUC',
                'ketQua'     => 'THANH_CONG',
                'thoiGian'   => now(),
                'chiTiet'    => "Đã đình chỉ tổ chức [{$toChuc->tenToChuc}]. Đang tước quyền On-chain ngầm.",
            ]);
        });

        return back()->with('thanhCong', "Đã đình chỉ tổ chức [{$toChuc->tenToChuc}]. Hệ thống đang gửi lệnh tước quyền On-chain lên Sepolia.");
    }
}
