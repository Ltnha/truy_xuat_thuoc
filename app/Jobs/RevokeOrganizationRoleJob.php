<?php

namespace App\Jobs;

use App\Models\ToChuc;
use App\Models\BlockchainIdentity;
use App\Models\BlockchainTransaction;
use App\Models\NhatKyHeThong;
use App\Services\Blockchain\SignerClient;
use App\Services\Blockchain\RoleMap;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;

class RevokeOrganizationRoleJob implements ShouldQueue
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

        // Láº¥y vĂ­ má»›i nháº¥t cĂ³ Ä‘á»‹a chá»‰ vĂ­ cá»§a tá»• chá»©c
        $identity = BlockchainIdentity::where('toChucId', $toChuc->id)
            ->whereNotNull('diaChiVi')
            ->latest('id')
            ->first();

        if (!$identity || empty($identity->diaChiVi)) {
            \Illuminate\Support\Facades\Log::warning("KhĂ´ng tĂ¬m tháº¥y thĂ´ng tin vĂ­ cá»§a tá»• chá»©c [{$toChuc->tenToChuc}] Ä‘á»ƒ thu há»“i.");
            return;
        }

        $roleHash = RoleMap::cho($toChuc->loaiToChuc);
        $adminKeyId = config('truyXuat.blockchain.adminKeyId', 'ADMIN_MASTER_KEY');
        $txHash = null;
        $blockTimestamp = null;

        // Chá»‰ gá»i revokeRole On-chain náº¿u trĂªn há»£p Ä‘á»“ng vĂ­ váº«n Ä‘ang sá»Ÿ há»¯u vai trĂ²
        if ($signer->hasRole($roleHash, $identity->diaChiVi)) {
            $signer->ensureGas($adminKeyId);

            $kq = $signer->tx($adminKeyId, 'revokeRole', [
                'account' => $identity->diaChiVi,
                'role'    => $roleHash,
            ]);

            if (!$kq['ok']) {
                throw new Exception("Lá»—i tÆ°á»›c quyá»n On-chain: " . ($kq['reasonVi'] ?? $kq['reason'] ?? 'Unknown error'));
            }

            $txHash = $kq['txHash'] ?? null;
            $blockTimestamp = isset($kq['blockTimestamp']) ? (int) $kq['blockTimestamp'] : null;
        } else {
            \Illuminate\Support\Facades\Log::info("VĂ­ [{$identity->diaChiVi}] cá»§a tá»• chá»©c [{$toChuc->tenToChuc}] Ä‘Ă£ khĂ´ng cĂ²n quyá»n On-chain tá»« trÆ°á»›c.");
        }

        // Cáº­p nháº­t CSDL
        DB::transaction(function () use ($identity, $toChuc, $txHash, $blockTimestamp) {
            $blockTime = $blockTimestamp ? \Illuminate\Support\Carbon::createFromTimestamp($blockTimestamp) : now();
            $identity->update(['trangThai' => 'REVOKED']);

            if ($txHash) {
                BlockchainTransaction::create([
                    'txHash'           => $txHash,
                    'eventName'        => 'RoleRevoked',
                    'doiTuongToChucId' => $toChuc->id,
                    'businessId'       => (string) \Illuminate\Support\Str::uuid(),
                    'network'          => 'Ethereum Sepolia Testnet',
                    'trangThai'        => 'CONFIRMED',
                    'toChucId'         => $toChuc->id,
                    'thoiGian'         => $blockTime,
                ]);
            }

            NhatKyHeThong::create([
                'taiKhoanId' => $this->adminAccountId,
                'doiTuongId' => $toChuc->taiKhoans()->first()?->id,
                'hanhDong'   => 'REVOKE_ROLE_ONCHAIN',
                'ketQua'     => 'THANH_CONG',
                'thoiGian'   => $blockTime,
                'chiTiet'    => "ÄĂ£ tÆ°á»›c quyá»n On-chain thĂ nh cĂ´ng cá»§a tá»• chá»©c [{$toChuc->tenToChuc}]." . ($txHash ? " TxHash: {$txHash}" : " (VĂ­ Ä‘Ă£ khĂ´ng cĂ²n quyá»n On-chain tá»« trÆ°á»›c)"),
            ]);
        });
    }
}
