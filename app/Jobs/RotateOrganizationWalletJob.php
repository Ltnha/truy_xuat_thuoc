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
        $revokeBlockTimestamp = null;
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

                if (empty($kqRevoke['ok']) || ! preg_match('/^0x[a-fA-F0-9]{64}$/', (string) ($kqRevoke['txHash'] ?? ''))) {
                    throw new Exception("Không thể thu hồi role ví cũ; dừng đổi ví để tránh hai ví cùng có quyền. " . ($kqRevoke['reason'] ?? 'Signer không trả về txHash hợp lệ.'));
                }

                $revokeBlockTimestamp = isset($kqRevoke['blockTimestamp']) ? (int) $kqRevoke['blockTimestamp'] : null;
                BlockchainTransaction::create([
                    'txHash'           => $kqRevoke['txHash'],
                    'eventName'        => 'RoleRevoked',
                    'doiTuongToChucId' => $toChuc->id,
                    'businessId'       => (string) \Illuminate\Support\Str::uuid(),
                    'network'          => 'Ethereum Sepolia Testnet',
                    'trangThai'        => 'CONFIRMED',
                    'toChucId'         => $toChuc->id,
                    'thoiGian'         => $revokeBlockTimestamp ? \\Illuminate\\Support\\Carbon::createFromTimestamp($revokeBlockTimestamp) : now(),
                ]);
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

        if (empty($kqGrant['ok']) || ! preg_match('/^0x[a-fA-F0-9]{64}$/', (string) ($kqGrant['txHash'] ?? ''))) {
            throw new Exception("Lỗi cấp quyền ví mới On-chain: " . ($kqGrant['reason'] ?? 'Signer không trả về txHash hợp lệ.'));
        }
        $grantBlockTimestamp = isset($kqGrant['blockTimestamp']) ? (int) $kqGrant['blockTimestamp'] : null;

        // 4. Hoàn tất kích hoạt ví mới
        DB::transaction(function () use ($viMoi, $toChuc, $kqGrant, $grantBlockTimestamp) {
            $blockTime = $grantBlockTimestamp ? \\Illuminate\\Support\\Carbon::createFromTimestamp($grantBlockTimestamp) : now();
            $viMoi->update(['trangThai' => 'ACTIVE']);

            BlockchainTransaction::create([
                'txHash'           => $kqGrant['txHash'],
                'eventName'        => 'RoleGranted',
                'doiTuongToChucId' => $toChuc->id,
                'businessId'       => (string) \Illuminate\Support\Str::uuid(),
                'network'          => 'Ethereum Sepolia Testnet',
                'trangThai'        => 'CONFIRMED',
                'toChucId'         => $toChuc->id,
                'thoiGian'         => $blockTime,
            ]);

            NhatKyHeThong::create([
                'taiKhoanId' => $this->adminAccountId,
                'doiTuongId' => $toChuc->taiKhoans()->first()?->id,
                'hanhDong'   => 'ROTATE_WALLET_ONCHAIN',
                'ketQua'     => 'THANH_CONG',
                'thoiGian'   => $blockTime,
                'chiTiet'    => "Đổi ví mới thành công [{$viMoi->diaChiVi}] cho tổ chức [{$toChuc->tenToChuc}]. TxHash: {$kqGrant['txHash']}",
            ]);
        });
    }
}
