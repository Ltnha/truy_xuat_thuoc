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
use Illuminate\Support\Carbon;
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

        // 1. Láº¥y vĂ­ má»›i nháº¥t cá»§a tá»• chá»©c hoáº·c sinh má»›i náº¿u chÆ°a cĂ³
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

        // 2. Náº¡p gas tá»± Ä‘á»™ng cho vĂ­ Admin vĂ  vĂ­ Tá»• chá»©c
        $signer->ensureGas($adminKeyId);
        if ($identity->signerKeyId) {
            $signer->ensureGas($identity->signerKeyId);
        }
        $identity->update(['trangThai' => 'DA_NAP_GAS']);

        // 3. Cáº¥p láº¡i quyá»n On-chain náº¿u chÆ°a sá»Ÿ há»¯u
        $txHash = null;
        $blockTimestamp = null;
        if (!$signer->hasRole($roleHash, $identity->diaChiVi)) {
            $kq = $signer->tx($adminKeyId, 'grantRole', [
                'account' => $identity->diaChiVi,
                'role'    => $roleHash,
            ]);

            if (!$kq['ok']) {
                throw new Exception("Lá»—i cáº¥p láº¡i quyá»n On-chain: " . ($kq['reasonVi'] ?? $kq['reason'] ?? 'Unknown error'));
            }

            $txHash = $kq['txHash'] ?? null;
            $blockTimestamp = isset($kq['blockTimestamp']) ? (int) $kq['blockTimestamp'] : null;
        } else {
            Log::info("VĂ­ [{$identity->diaChiVi}] cá»§a tá»• chá»©c [{$toChuc->tenToChuc}] Ä‘Ă£ cĂ³ sáºµn vai trĂ² On-chain.");
        }

        // 4. KĂ­ch hoáº¡t láº¡i tá»• chá»©c vĂ  tĂ i khoáº£n trong CSDL
        DB::transaction(function () use ($toChuc, $identity, $txHash, $blockTimestamp) {
            $blockTime = $blockTimestamp ? \Illuminate\Support\Carbon::createFromTimestamp($blockTimestamp) : now();
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
                    'thoiGian'         => $blockTime,
                ]);
            }

            NhatKyHeThong::create([
                'taiKhoanId' => $this->adminAccountId,
                'doiTuongId' => $toChuc->taiKhoans()->first()?->id,
                'hanhDong'   => 'RESTORE_ROLE_ONCHAIN',
                'ketQua'     => 'THANH_CONG',
                'thoiGian'   => $blockTime,
                'chiTiet'    => "KhĂ´i phá»¥c quyá»n tá»• chá»©c [{$toChuc->tenToChuc}] thĂ nh cĂ´ng. VĂ­: [{$identity->diaChiVi}]." . ($txHash ? " TxHash: {$txHash}" : " (Quyá»n On-chain Ä‘Ă£ cĂ³ sáºµn)"),
            ]);
        });
    }
}
