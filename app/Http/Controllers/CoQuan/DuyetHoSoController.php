<?php

namespace App\Http\Controllers\CoQuan;

use App\Enums\AccountStatus;
use App\Enums\BlockchainIdentityStatus;
use App\Enums\OrganizationApprovalStatus;
use App\Enums\ProductApprovalStatus;
use App\Http\Controllers\Controller;
use App\Models\BlockchainIdentity;
use App\Models\NhatKyHeThong;
use App\Models\SanPham;
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

    public function index(): View
    {
        return view('coQuanQuanLy.pheDuyetTaiKhoanToChuc', [
            // Tối ưu Eager Loading thêm 'yeuCauDangKys.taiLieus' để tránh N+1 Query
            'toChucs' => ToChuc::query()
                ->with([
                    'yeuCauDangKys' => fn($query) => $query->latest('id'),
                    'yeuCauDangKys.taiLieus'
                ])
                ->where('trangThaiDuyet', OrganizationApprovalStatus::choDuyet)
                ->latest('id')
                ->paginate(15, ['*'], 'toChucPage'),

            'sanPhams' => SanPham::query()
                ->with([
                    'nhaSanXuat',
                    'yeuCauDangKySanPhams' => fn($query) => $query->latest('id')
                ])
                ->where('trangThaiDuyet', ProductApprovalStatus::choDuyet)
                ->latest('id')
                ->paginate(15, ['*'], 'sanPhamPage'),
        ]);
    }

    public function approveOrganization(Request $request, ToChuc $toChuc): RedirectResponse
    {
        $duLieu = $request->validate([
            'trangThaiDuyet' => ['required', 'in:DA_DUYET,TU_CHOI'],
            'lyDo' => ['nullable', 'required_if:trangThaiDuyet,TU_CHOI', 'string', 'max:500'],
        ]);

        abort_unless($toChuc->trangThaiDuyet === OrganizationApprovalStatus::choDuyet, 409, 'Hồ sơ tổ chức không còn ở trạng thái chờ duyệt.');

        DB::transaction(function () use ($request, $toChuc, $duLieu): void {
            $status = OrganizationApprovalStatus::from($duLieu['trangThaiDuyet']);
            $toChuc->trangThaiDuyet = $status;
            $toChuc->save();

            // 1. Cập nhật Yêu cầu đăng ký
            $yeuCau = $toChuc->yeuCauDangKys()->latest('id')->first();
            if ($yeuCau) {
                $yeuCau->trangThai = $status->value;
                $yeuCau->nguoiDuyetId = $request->user()->id;
                $yeuCau->ngayXuLy = now();
                $yeuCau->lyDoTuChoi = $duLieu['lyDo'] ?? null;
                $yeuCau->save();
            }

            $taiKhoanDaiDien = null;

            if ($status === OrganizationApprovalStatus::daDuyet) {
                // 2. Kích hoạt tài khoản đại diện
                $taiKhoanDaiDien = TaiKhoan::query()
                    ->where('toChucId', $toChuc->id)
                    ->where('trangThai', AccountStatus::pending)
                    ->first();

                if ($taiKhoanDaiDien) {
                    $taiKhoanDaiDien->update(['trangThai' => AccountStatus::active]);
                }

                // 3. Khởi tạo Định danh mật mã / Ví Blockchain Custodial
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
            } else {
                $taiKhoanDaiDien = TaiKhoan::query()->where('toChucId', $toChuc->id)->first();
            }

            // 4. Ghi vết nhật ký hệ thống (Đã sửa doiTuongId tham chiếu chính xác taiKhoan.id)
            NhatKyHeThong::query()->create([
                'taiKhoanId' => $request->user()->id,
                'doiTuongId' => $taiKhoanDaiDien?->id, // Sửa từ $toChuc->id thành $taiKhoanDaiDien->id
                'hanhDong' => $status === OrganizationApprovalStatus::daDuyet ? 'DUYET_TO_CHUC' : 'TU_CHOI_TO_CHUC',
                'ketQua' => 'THANH_CONG',
                'thoiGian' => now(),
                'chiTiet' => "Xử lý phê duyệt tổ chức: {$toChuc->tenToChuc}. Trạng thái: {$status->value}." . (! empty($duLieu['lyDo']) ? ' Lý do: ' . $duLieu['lyDo'] : ''),
            ]);
        });

        $thongBao = $duLieu['trangThaiDuyet'] === 'DA_DUYET'
            ? "Đã phê duyệt tổ chức [{$toChuc->tenToChuc}] và cấp ví Blockchain thành công."
            : "Đã từ chối hồ sơ đăng ký của tổ chức [{$toChuc->tenToChuc}]. Lý do từ chối đã được gửi đến doanh nghiệp.";

        return back()->with('thanhCong', $thongBao);
    }
}
