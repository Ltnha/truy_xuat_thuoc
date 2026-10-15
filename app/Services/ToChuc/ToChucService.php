<?php

namespace App\Services\ToChuc;

use App\Enums\AccountStatus;
use App\Enums\BlockchainIdentityStatus;
use App\Enums\OrganizationApprovalStatus;
use App\Models\BlockchainIdentity;
use App\Models\TaiKhoan;
use App\Models\ToChuc;
use App\Services\Blockchain\SepoliaBlockchainGateway;
use App\Services\Service;

class ToChucService extends Service
{
    public function __construct(protected SepoliaBlockchainGateway $blockchainGateway) {}

    /**
     * Xử lý Phê duyệt hoặc Từ chối hồ sơ đăng ký tổ chức trong Transaction.
     */
    public function duyet(ToChuc $toChuc, array $duLieu, int $nguoiXuLyId): bool
    {
        return $this->chayNghiepVu(function () use ($toChuc, $duLieu, $nguoiXuLyId) {
            $status = OrganizationApprovalStatus::from($duLieu['trangThaiDuyet']);
            $toChuc->trangThaiDuyet = $status;
            $toChuc->save();

            // 1. Cập nhật phiếu Yêu cầu đăng ký
            $yeuCau = $toChuc->yeuCauDangKys()->latest('id')->first();
            if ($yeuCau) {
                $yeuCau->trangThai = $status->value;
                $yeuCau->nguoiDuyetId = $nguoiXuLyId;
                $yeuCau->ngayXuLy = now();
                $yeuCau->lyDoTuChoi = $duLieu['lyDo'] ?? null;
                $yeuCau->save();
            }

            $taiKhoanDaiDien = TaiKhoan::query()->where('toChucId', $toChuc->id)->first();

            if ($status === OrganizationApprovalStatus::daDuyet) {
                // 2. Kích hoạt tài khoản đại diện
                if ($taiKhoanDaiDien && $taiKhoanDaiDien->trangThai === AccountStatus::pending) {
                    $taiKhoanDaiDien->update(['trangThai' => AccountStatus::active]);
                }

                // 3. Cấp ví Custodial Wallet trên Sepolia Testnet
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

            // 4. Ghi nhật ký hệ thống (doiTuongId chỉ tới taiKhoan.id chuẩn CSDL v5)
            $this->ghiNhatKy(
                hanhDong: $status === OrganizationApprovalStatus::daDuyet ? 'DUYET_TO_CHUC' : 'TU_CHOI_TO_CHUC',
                doiTuongId: $taiKhoanDaiDien?->id,
                ketQua: 'THANH_CONG',
                chiTiet: "Phê duyệt tổ chức: {$toChuc->tenToChuc} -> {$status->value}. " . (! empty($duLieu['lyDo']) ? "Lý do: {$duLieu['lyDo']}" : '')
            );

            return true;
        });
    }
}
