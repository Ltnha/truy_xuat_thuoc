<?php
// Job Khởi tạo Ví & Phê duyệt
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

        // 1. Sinh Ví Custodial ở Signer Service (Nếu chưa có)
        $identity = BlockchainIdentity::where('toChucId', $toChuc->id)->first();
        if (!$identity) {
            $walletInfo = $signer->taoVi($toChuc->id);

            $identity = BlockchainIdentity::create([
                'toChucId'     => $toChuc->id,
                'signerKeyId'  => $walletInfo['keyId'],
                'diaChiVi'     => $walletInfo['diaChiVi'],
                'network'      => 'Ethereum Sepolia Testnet',
                'trangThai'    => 'KHOI_TAO',
            ]);
        }

        // 2. Nạp Gas tự động cho Ví Admin và Ví Tổ chức
        $adminKeyId = config('truyXuat.blockchain.adminKeyId', 'ADMIN_MASTER_KEY');
        $signer->ensureGas($adminKeyId);
        $signer->ensureGas($identity->signerKeyId);

        $identity->update(['trangThai' => 'DA_NAP_GAS']);

        // 3. Cấp vai trò On-chain bằng Ví Admin nếu chưa có
        if (!$signer->hasRole($roleHash, $identity->diaChiVi)) {
            $kq = $signer->tx($adminKeyId, 'grantRole', [
                'account'  => $identity->diaChiVi,
                'role'     => $roleHash,
            ]);

            if (!$kq['ok']) {
                throw new Exception("Lỗi grantRole On-chain: " . ($kq['reason'] ?? 'Unknown'));
            }

            BlockchainTransaction::create([
                'txHash'           => $kq['txHash'],
                'eventName'        => 'RoleGranted',
                'doiTuongToChucId' => $toChuc->id,
                'businessId'       => (string) \Illuminate\Support\Str::uuid(),
                'network'          => 'Ethereum Sepolia Testnet',
                'trangThai'        => 'CONFIRMED',
                'toChucId'         => $toChuc->id,
            ]);
        }

        // 4. Hoàn tất kích hoạt trạng thái On-chain & Off-chain
        DB::transaction(function () use ($identity, $toChuc) {
            $identity->update(['trangThai' => 'ACTIVE']);
            $toChuc->update(['trangThaiDuyet' => 'DA_DUYET']);

            NhatKyHeThong::create([
                'taiKhoanId' => $this->adminAccountId,
                'doiTuongId' => $toChuc->taiKhoans()->first()?->id,
                'hanhDong'   => 'PHE_DUYET_TO_CHUC_ONCHAIN',
                'ketQua'     => 'THANH_CONG',
                'chiTiet'    => "Hoàn tất sinh ví Custodial và cấp quyền On-chain cho [{$toChuc->tenToChuc}].",
            ]);
        });
    }
}
