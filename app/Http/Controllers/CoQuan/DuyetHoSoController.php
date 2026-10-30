<?php

namespace App\Http\Controllers\CoQuan;

use App\Enums\AccountStatus;
use App\Enums\OrganizationApprovalStatus;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessOrganizationApprovalJob;
use App\Jobs\RevokeOrganizationRoleJob;
use App\Jobs\RestoreOrganizationRoleJob;
use App\Jobs\RotateOrganizationWalletJob;
use App\Models\BlockchainTransaction;
use App\Models\NhatKyHeThong;
use App\Models\ToChuc;
use Illuminate\Http\JsonResponse;
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
    public function approveOrganization(Request $request, ToChuc $toChuc)
    {
        $duLieu = $request->validate([
            'trangThaiDuyet' => ['required', 'in:DA_DUYET,TU_CHOI'],
            'lyDo'           => ['nullable', 'required_if:trangThaiDuyet,TU_CHOI', 'string', 'max:500'],
        ]);

        if ($duLieu['trangThaiDuyet'] === 'DA_DUYET') {
            ProcessOrganizationApprovalJob::dispatch($toChuc->id, $request->user()->id);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success'  => true,
                    'message'  => "Đã tiếp nhận yêu cầu phê duyệt tổ chức [{$toChuc->tenToChuc}].",
                    'toChucId' => $toChuc->id,
                ]);
            }

            return back()->with('thanhCong', "Đã tiếp nhận yêu cầu phê duyệt tổ chức [{$toChuc->tenToChuc}].");
        }

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

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Đã từ chối hồ sơ đăng ký của tổ chức [{$toChuc->tenToChuc}]."]);
        }

        return back()->with('thanhCong', "Đã từ chối hồ sơ đăng ký của tổ chức [{$toChuc->tenToChuc}].");
    }

    /**
     * API Polling kiểm tra tiến trình xử lý On-chain
     */
    public function checkApprovalStatus(ToChuc $toChuc): JsonResponse
    {
        $identity = $toChuc->blockchainIdentity;
        $lastTx = BlockchainTransaction::where('toChucId', $toChuc->id)
            ->latest('id')
            ->first();

        return response()->json([
            'trangThaiDuyet' => $toChuc->trangThaiDuyet->value ?? (string)$toChuc->trangThaiDuyet,
            'diaChiVi'       => $identity?->diaChiVi,
            'trangThaiVi'    => $identity?->trangThai,
            'txHash'         => $lastTx?->txHash,
        ]);
    }

    /**
     * Thu hồi quyền Tổ chức (Revoke Role On-chain)
     */
    public function thuHoiQuyenToChuc(Request $request, ToChuc $toChuc)
    {
        DB::transaction(function () use ($request, $toChuc): void {
            $toChuc->update([
                'trangThaiDuyet' => OrganizationApprovalStatus::biThuHoi ?? 'BI_THU_HOI',
            ]);

            $toChuc->taiKhoans()->update([
                'trangThai' => AccountStatus::locked ?? 'LOCKED',
            ]);

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

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'  => true,
                'message'  => "Đã tiếp nhận yêu cầu thu hồi quyền tổ chức [{$toChuc->tenToChuc}].",
                'toChucId' => $toChuc->id,
                'action'   => 'THU_HOI',
            ]);
        }

        return back()->with('thanhCong', "Đã đình chỉ tổ chức [{$toChuc->tenToChuc}]. Hệ thống đang gửi lệnh tước quyền On-chain lên Sepolia.");
    }

    /**
     * Hiển thị trang Quản lý quyền hạn tổ chức
     */
    public function quanLyQuyenHan(Request $request): View
    {
        $tuKhoa = trim($request->input('tuKhoa', ''));
        $loaiToChuc = $request->input('loaiToChuc', 'TAT_CA');
        $trangThaiPheDuyet = $request->input('trangThaiPheDuyet', 'TAT_CA');

        $query = ToChuc::query()
            ->with(['blockchainIdentity', 'taiKhoans', 'yeuCauDangKys.taiLieus'])
            ->where('loaiToChuc', '<>', 'CO_QUAN_QUAN_LY');

        if ($tuKhoa !== '') {
            $query->where(function ($q) use ($tuKhoa) {
                $q->where('tenToChuc', 'like', "%{$tuKhoa}%")
                    ->orWhere('maSoThue', 'like', "%{$tuKhoa}%")
                    ->orWhere('soGiayPhepDuoc', 'like', "%{$tuKhoa}%");
            });
        }

        if ($loaiToChuc !== 'TAT_CA') {
            $query->where('loaiToChuc', $loaiToChuc);
        }

        if ($trangThaiPheDuyet !== 'TAT_CA') {
            $query->where('trangThaiDuyet', $trangThaiPheDuyet);
        }

        $toChucs = $query->latest('id')->paginate(15)->withQueryString();

        return view('coQuanQuanLy.quanLyQuyenHan', compact('toChucs', 'tuKhoa', 'loaiToChuc', 'trangThaiPheDuyet'));
    }

    /**
     * Khôi phục quyền Tổ chức (Restore Role On-chain)
     */
    public function khoiPhucQuyenToChuc(Request $request, ToChuc $toChuc)
    {
        RestoreOrganizationRoleJob::dispatch($toChuc->id, $request->user()->id);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'  => true,
                'message'  => "Đã tiếp nhận yêu cầu khôi phục quyền tổ chức [{$toChuc->tenToChuc}].",
                'toChucId' => $toChuc->id,
                'action'   => 'KHOI_PHUC',
            ]);
        }

        return back()->with('thanhCong', "Đã gửi yêu cầu khôi phục quyền cho tổ chức [{$toChuc->tenToChuc}].");
    }

    /**
     * Đổi ví mới (Rotate Wallet) cho Tổ chức
     */
    public function doiViMoi(Request $request, ToChuc $toChuc)
    {
        RotateOrganizationWalletJob::dispatch($toChuc->id, $request->user()->id);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'  => true,
                'message'  => "Đã tiếp nhận yêu cầu cấp ví mới cho tổ chức [{$toChuc->tenToChuc}].",
                'toChucId' => $toChuc->id,
                'action'   => 'DOI_VI',
            ]);
        }

        return back()->with('thanhCong', "Đã gửi yêu cầu cấp ví mới cho tổ chức [{$toChuc->tenToChuc}].");
    }
}
