<?php
// Job Thu hồi Quyền Tổ chức
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
use Exception;

class RevokeOrganizationRoleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 10;
    public $backoff = [10, 30, 60];

    public function __construct(
        public int $toChucId,
        public int $adminAccountId
    ) {}

    public function handle(SignerClient $signer): void
    {
        $toChuc = ToChuc::findOrFail($this->toChucId);
        $identity = BlockchainIdentity::where('toChucId', $toChuc->id)->first();

        if (!$identity) return;

        $roleHash = RoleMap::cho($toChuc->loaiToChuc);
        $adminKeyId = config('truyXuat.blockchain.adminKeyId', 'ADMIN_MASTER_KEY');

        // Check Idempotent: Nếu vẫn còn quyền On-chain thì thực hiện revokeRole
        if ($signer->hasRole($roleHash, $identity->diaChiVi)) {
            $signer->ensureGas($adminKeyId);

            $kq = $signer->tx($adminKeyId, 'revokeRole', [
                'account' => $identity->diaChiVi,
                'role'    => $roleHash,
            ]);

            if (!$kq['ok']) {
                throw new Exception("Lỗi tước quyền On-chain: " . $kq['reason']);
            }

            BlockchainTransaction::create([
                'txHash'           => $kq['txHash'],
                'eventName'        => 'RoleRevoked',
                'doiTuongToChucId' => $toChuc->id,
                'businessId'       => (string) \Illuminate\Support\Str::uuid(),
                'network'          => 'Ethereum Sepolia Testnet',
                'trangThai'        => 'CONFIRMED',
                'toChucId'         => $toChuc->id,
            ]);
        }

        // Cập nhật trạng thái ví trong CSDL v6 thành REVOKED
        $identity->update(['trangThai' => 'REVOKED']);

        NhatKyHeThong::create([
            'taiKhoanId' => $this->adminAccountId,
            'doiTuongId' => $toChuc->taiKhoans()->first()?->id,
            'hanhDong'   => 'REVOKE_ROLE_ONCHAIN',
            'ketQua'     => 'THANH_CONG',
            'chiTiet'    => "Đã tước quyền On-chain của tổ chức [{$toChuc->tenToChuc}].",
        ]);
    }
}
