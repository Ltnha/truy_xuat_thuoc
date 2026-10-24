<?php

namespace App\Jobs;

use App\Enums\AccountStatus;
use App\Enums\OrganizationApprovalStatus;
use App\Models\BlockchainIdentity;
use App\Models\BlockchainTransaction;
use App\Models\NhatKyHeThong;
use App\Models\ToChuc;
use App\Services\Blockchain\RoleMap;
use App\Services\Blockchain\SignerClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class ProcessOrganizationApprovalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $backoff = [10, 30, 60];

    public function __construct(
        public int $toChucId,
        public int $adminAccountId
    ) {}

    public function handle(SignerClient $signer): void
    {
        $toChuc = ToChuc::findOrFail($this->toChucId);
        $roleHash = RoleMap::cho($toChuc->loaiToChuc);
        $adminKeyId = config('truyXuat.blockchain.adminKeyId', 'ADMIN_MASTER_KEY');

        // 1. Kiểm tra hoặc khởi tạo Ví Custodial trong CSDL
        $identity = BlockchainIdentity::where('toChucId', $toChuc->id)
            ->where('trangThai', '!=', 'REVOKED')
            ->latest('id')
            ->first();

        if (!$identity) {
            $walletData = $signer->taoVi($toChuc->id);

            $identity = BlockchainIdentity::create([
                'toChucId'    => $toChuc->id,
                'signerKeyId' => $walletData['keyId'],
                'diaChiVi'    => $walletData['diaChiVi'],
                'network'     => 'Ethereum Sepolia Testnet',
                'trangThai'   => 'KHOI_TAO',
            ]);
        }

        // 2. Nạp Gas tự động cho Ví Admin và Ví Tổ chức
        $signer->ensureGas($adminKeyId);
        $signer->ensureGas($identity->signerKeyId);
        $identity->update(['trangThai' => 'DA_NAP_GAS']);

        // 3. Kiểm tra Idempotent: Nếu CHƯA có quyền On-chain mới gọi grantRole
        $txHash = null;

        if (!$signer->hasRole($roleHash, $identity->diaChiVi)) {
            $kq = $signer->tx($adminKeyId, 'grantRole', [
                'account' => $identity->diaChiVi,
                'role'    => $roleHash,
            ]);

            if (!$kq['ok']) {
                throw new Exception("Lỗi cấp quyền On-chain: " . ($kq['reason'] ?? 'Unknown error'));
            }

            $txHash = $kq['txHash'];
        } else {
            Log::info("Ví [{$identity->diaChiVi}] của tổ chức [{$toChuc->tenToChuc}] đã sở hữu quyền On-chain từ trước. Bỏ qua grantRole.");
        }

        // 4. Cập nhật đồng bộ CSDL Off-chain
        DB::transaction(function () use ($toChuc, $identity, $txHash) {
            // Cập nhật trạng thái ví
            $identity->update(['trangThai' => 'ACTIVE']);

            // Cập nhật trạng thái phê duyệt tổ chức
            $toChuc->update([
                'trangThaiDuyet' => OrganizationApprovalStatus::daDuyet ?? 'DA_DUYET',
            ]);

            // Mở khóa tài khoản đăng nhập của doanh nghiệp
            $toChuc->taiKhoans()->update([
                'trangThai' => AccountStatus::active ?? 'ACTIVE',
            ]);

            // Cập nhật trạng thái yêu cầu đăng ký
            $yeuCau = $toChuc->yeuCauDangKys()->latest('id')->first();
            if ($yeuCau) {
                $yeuCau->update([
                    'trangThai'    => 'DA_DUYET',
                    'nguoiDuyetId' => $this->adminAccountId,
                    'ngayXuLy'     => now(),
                ]);
            }

            // Lưu lịch sử giao dịch Blockchain nếu có cấp quyền mới
            if ($txHash) {
                BlockchainTransaction::create([
                    'txHash'           => $txHash,
                    'eventName'        => 'RoleGranted',
                    'doiTuongToChucId' => $toChuc->id,
                    'businessId'       => (string) Str::uuid(),
                    'network'          => 'Ethereum Sepolia Testnet',
                    'trangThai'        => 'CONFIRMED',
                    'toChucId'         => $toChuc->id,
                ]);
            }

            // Ghi Nhật ký hệ thống
            NhatKyHeThong::create([
                'taiKhoanId' => $this->adminAccountId,
                'doiTuongId' => $toChuc->taiKhoans()->first()?->id,
                'hanhDong'   => 'PHE_DUYET_TO_CHUC_ONCHAIN',
                'ketQua'     => 'THANH_CONG',
                'thoiGian'   => now(),
                'chiTiet'    => "Phê duyệt tổ chức [{$toChuc->tenToChuc}] thành công. Ví: [{$identity->diaChiVi}]." . ($txHash ? " TxHash: {$txHash}" : " (Quyền On-chain đã có sẵn)"),
            ]);
        });
    }
}
