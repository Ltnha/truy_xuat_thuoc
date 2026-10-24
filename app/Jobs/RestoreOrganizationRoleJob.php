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

class RestoreOrganizationRoleJob implements ShouldQueue
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

        // 1. Lấy ví mới nhất của tổ chức hoặc sinh mới nếu chưa có
        $identity = BlockchainIdentity::where('toChucId', $toChuc->id)
            ->whereNotNull('diaChiVi')
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

        // 2. Nạp gas tự động cho ví Admin và ví Tổ chức
        $signer->ensureGas($adminKeyId);
        if ($identity->signerKeyId) {
            $signer->ensureGas($identity->signerKeyId);
        }
        $identity->update(['trangThai' => 'DA_NAP_GAS']);

        // 3. Cấp lại quyền On-chain nếu chưa sở hữu
        $txHash = null;
        if (!$signer->hasRole($roleHash, $identity->diaChiVi)) {
            $kq = $signer->tx($adminKeyId, 'grantRole', [
                'account' => $identity->diaChiVi,
                'role'    => $roleHash,
            ]);

            if (!$kq['ok']) {
                throw new Exception("Lỗi cấp lại quyền On-chain: " . ($kq['reason'] ?? 'Unknown error'));
            }

            $txHash = $kq['txHash'];
        } else {
            Log::info("Ví [{$identity->diaChiVi}] của tổ chức [{$toChuc->tenToChuc}] đã có sẵn vai trò On-chain.");
        }

        // 4. Kích hoạt lại tổ chức và tài khoản trong CSDL
        DB::transaction(function () use ($toChuc, $identity, $txHash) {
            $identity->update(['trangThai' => 'ACTIVE']);

            $toChuc->update([
                'trangThaiDuyet' => OrganizationApprovalStatus::daDuyet ?? 'DA_DUYET',
            ]);

            $toChuc->taiKhoans()->update([
                'trangThai' => AccountStatus::active ?? 'ACTIVE',
            ]);

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

            NhatKyHeThong::create([
                'taiKhoanId' => $this->adminAccountId,
                'doiTuongId' => $toChuc->taiKhoans()->first()?->id,
                'hanhDong'   => 'RESTORE_ROLE_ONCHAIN',
                'ketQua'     => 'THANH_CONG',
                'thoiGian'   => now(),
                'chiTiet'    => "Khôi phục quyền tổ chức [{$toChuc->tenToChuc}] thành công. Ví: [{$identity->diaChiVi}]." . ($txHash ? " TxHash: {$txHash}" : " (Quyền On-chain đã có sẵn)"),
            ]);
        });
    }
}
