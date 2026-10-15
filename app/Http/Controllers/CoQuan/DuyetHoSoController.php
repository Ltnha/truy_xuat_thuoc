<?php

namespace App\Http\Controllers\CoQuan;

use App\Enums\AccountStatus;
use App\Enums\BlockchainIdentityStatus;
use App\Enums\OrganizationApprovalStatus;
use App\Http\Controllers\Controller;
use App\Models\BlockchainIdentity;
use App\Models\NhatKyHeThong;
use App\Models\TaiKhoan;
use App\Models\ToChuc;
use App\Services\Blockchain\SepoliaBlockchainGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DuyetHoSoController extends Controller
{
    public function __construct(protected SepoliaBlockchainGateway $blockchainGateway) {}

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

        // 1. Tìm kiếm theo từ khóa (Tên tổ chức, Mã số thuế, Số GPKD)
        if ($tuKhoa !== '') {
            $query->where(function ($q) use ($tuKhoa) {
                $q->where('tenToChuc', 'like', "%{$tuKhoa}%")
                    ->orWhere('maSoThue', 'like', "%{$tuKhoa}%")
                    ->orWhere('soGiayPhepDuoc', 'like', "%{$tuKhoa}%");
            });
        }

        // 2. Lọc theo loại hình tổ chức (Loại trừ Cơ quan quản lý)
        if ($loaiToChuc !== 'TAT_CA' && in_array($loaiToChuc, ['NHA_SAN_XUAT', 'NHA_PHAN_PHOI', 'NHA_THUOC'], true)) {
            $query->where('loaiToChuc', $loaiToChuc);
        } else {
            $query->where('loaiToChuc', '<>', 'CO_QUAN_QUAN_LY');
        }

        // 3. Lọc theo trạng thái phê duyệt
        if ($trangThaiDuyet !== 'TAT_CA') {
            $query->where('trangThaiDuyet', $trangThaiDuyet);
        }

        $toChucs = $query->latest('id')->paginate(15)->withQueryString();

        return view('coQuanQuanLy.pheDuyetTaiKhoanToChuc', compact('toChucs', 'tuKhoa', 'loaiToChuc', 'trangThaiDuyet'));
    }

    public function approveOrganization(Request $request, ToChuc $toChuc): RedirectResponse
    {
        $duLieu = $request->validate([
            'trangThaiDuyet' => ['required', 'in:DA_DUYET,TU_CHOI'],
            'lyDo' => ['nullable', 'required_if:trangThaiDuyet,TU_CHOI', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($request, $toChuc, $duLieu): void {
            $status = OrganizationApprovalStatus::from($duLieu['trangThaiDuyet']);
            $toChuc->trangThaiDuyet = $status;
            $toChuc->save();

            // Cập nhật Yêu cầu đăng ký mới nhất
            $yeuCau = $toChuc->yeuCauDangKys()->latest('id')->first();
            if ($yeuCau) {
                $yeuCau->trangThai = $status->value;
                $yeuCau->nguoiDuyetId = $request->user()->id;
                $yeuCau->ngayXuLy = now();
                $yeuCau->lyDoTuChoi = $duLieu['lyDo'] ?? null;
                $yeuCau->save();
            }

            $taiKhoanDaiDien = TaiKhoan::query()->where('toChucId', $toChuc->id)->first();

            if ($status === OrganizationApprovalStatus::daDuyet) {
                // Kích hoạt tài khoản đại diện
                if ($taiKhoanDaiDien) {
                    $taiKhoanDaiDien->update(['trangThai' => AccountStatus::active]);
                }

                // Cấp ví Custodial Wallet trên Sepolia Testnet cho Doanh nghiệp mới
                if (! $toChuc->blockchainIdentity) {
                    $walletInfo = $this->blockchainGateway->taoViCustodial();

                    BlockchainIdentity::create([
                        'toChucId' => $toChuc->id,
                        'khoaRiengMaHoa' => $walletInfo['privateKey'],
                        'network' => 'Sepolia Testnet',
                        'address' => $walletInfo['address'],
                        'certificateId' => $walletInfo['certificateId'],
                        'trangThai' => BlockchainIdentityStatus::active,
                    ]);
                }
            }

            // Ghi vết nhật ký hệ thống
            NhatKyHeThong::query()->create([
                'taiKhoanId' => $request->user()->id,
                'doiTuongId' => $taiKhoanDaiDien?->id,
                'hanhDong' => $status === OrganizationApprovalStatus::daDuyet ? 'DUYET_TO_CHUC' : 'TU_CHOI_TO_CHUC',
                'ketQua' => 'THANH_CONG',
                'thoiGian' => now(),
                'chiTiet' => "Xử lý phê duyệt tổ chức [{$toChuc->tenToChuc}]. Trạng thái: {$status->value}." . (! empty($duLieu['lyDo']) ? " Lý do từ chối: {$duLieu['lyDo']}" : ''),
            ]);
        });

        $thongBao = $duLieu['trangThaiDuyet'] === 'DA_DUYET'
            ? "Đã phê duyệt tổ chức [{$toChuc->tenToChuc}] và cấp ví Blockchain Sepolia thành công."
            : "Đã từ chối hồ sơ đăng ký của tổ chức [{$toChuc->tenToChuc}].";

        return back()->with('thanhCong', $thongBao);
    }
}
