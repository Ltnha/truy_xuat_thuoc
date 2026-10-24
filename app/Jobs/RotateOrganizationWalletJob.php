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
use Illuminate\Support\Facades\DB;
use Exception;

class RotateOrganizationWalletJob implements ShouldQueue
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

        // 1. Tước quyền On-chain ví cũ (nếu có ví và ví đang nắm quyền On-chain)
        $viCu = BlockchainIdentity::where('toChucId', $toChuc->id)
            ->whereNotNull('diaChiVi')
            ->latest('id')
            ->first();

        if ($viCu && !empty($viCu->diaChiVi)) {
            if ($signer->hasRole($roleHash, $viCu->diaChiVi)) {
                $signer->ensureGas($adminKeyId);
                $kqRevoke = $signer->tx($adminKeyId, 'revokeRole', [
                    'account' => $viCu->diaChiVi,
                    'role'    => $roleHash,
                ]);

                if ($kqRevoke['ok'] && !empty($kqRevoke['txHash'])) {
                    BlockchainTransaction::create([
                        'txHash'           => $kqRevoke['txHash'],
                        'eventName'        => 'RoleRevoked',
                        'doiTuongToChucId' => $toChuc->id,
                        'businessId'       => (string) \Illuminate\Support\Str::uuid(),
                        'network'          => 'Ethereum Sepolia Testnet',
                        'trangThai'        => 'CONFIRMED',
                        'toChucId'         => $toChuc->id,
                    ]);
                }
            }
            $viCu->update(['trangThai' => 'REVOKED']);
        }

        // 2. Yêu cầu Signer Service sinh ví mới
        $walletMoi = $signer->doiViMoi($toChuc->id);

        $viMoi = BlockchainIdentity::create([
            'toChucId'    => $toChuc->id,
            'signerKeyId' => $walletMoi['keyId'],
            'diaChiVi'    => $walletMoi['diaChiVi'],
            'network'     => 'Ethereum Sepolia Testnet',
            'trangThai'   => 'KHOI_TAO',
        ]);

        // 3. Nạp Gas và cấp quyền On-chain cho ví mới
        $signer->ensureGas($adminKeyId);
        $signer->ensureGas($viMoi->signerKeyId);
        $viMoi->update(['trangThai' => 'DA_NAP_GAS']);

        $kqGrant = $signer->tx($adminKeyId, 'grantRole', [
            'account' => $viMoi->diaChiVi,
            'role'    => $roleHash,
        ]);

        if (!$kqGrant['ok']) {
            throw new Exception("Lỗi cấp quyền ví mới On-chain: " . ($kqGrant['reason'] ?? 'Unknown error'));
        }

        // 4. Hoàn tất kích hoạt ví mới
        DB::transaction(function () use ($viMoi, $toChuc, $kqGrant) {
            $viMoi->update(['trangThai' => 'ACTIVE']);

            BlockchainTransaction::create([
                'txHash'           => $kqGrant['txHash'],
                'eventName'        => 'RoleGranted',
                'doiTuongToChucId' => $toChuc->id,
                'businessId'       => (string) \Illuminate\Support\Str::uuid(),
                'network'          => 'Ethereum Sepolia Testnet',
                'trangThai'        => 'CONFIRMED',
                'toChucId'         => $toChuc->id,
            ]);

            NhatKyHeThong::create([
                'taiKhoanId' => $this->adminAccountId,
                'doiTuongId' => $toChuc->taiKhoans()->first()?->id,
                'hanhDong'   => 'ROTATE_WALLET_ONCHAIN',
                'ketQua'     => 'THANH_CONG',
                'thoiGian'   => now(),
                'chiTiet'    => "Đổi ví mới thành công [{$viMoi->diaChiVi}] cho tổ chức [{$toChuc->tenToChuc}]. TxHash: {$kqGrant['txHash']}",
            ]);
        });
    }
}
