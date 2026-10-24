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

        // Lấy ví mới nhất có địa chỉ ví của tổ chức
        $identity = BlockchainIdentity::where('toChucId', $toChuc->id)
            ->whereNotNull('diaChiVi')
            ->latest('id')
            ->first();

        if (!$identity || empty($identity->diaChiVi)) {
            \Illuminate\Support\Facades\Log::warning("Không tìm thấy thông tin ví của tổ chức [{$toChuc->tenToChuc}] để thu hồi.");
            return;
        }

        $roleHash = RoleMap::cho($toChuc->loaiToChuc);
        $adminKeyId = config('truyXuat.blockchain.adminKeyId', 'ADMIN_MASTER_KEY');
        $txHash = null;

        // Chỉ gọi revokeRole On-chain nếu trên hợp đồng ví vẫn đang sở hữu vai trò
        if ($signer->hasRole($roleHash, $identity->diaChiVi)) {
            $signer->ensureGas($adminKeyId);

            $kq = $signer->tx($adminKeyId, 'revokeRole', [
                'account' => $identity->diaChiVi,
                'role'    => $roleHash,
            ]);

            if (!$kq['ok']) {
                throw new Exception("Lỗi tước quyền On-chain: " . ($kq['reason'] ?? 'Unknown error'));
            }

            $txHash = $kq['txHash'];
        } else {
            \Illuminate\Support\Facades\Log::info("Ví [{$identity->diaChiVi}] của tổ chức [{$toChuc->tenToChuc}] đã không còn quyền On-chain từ trước.");
        }

        // Cập nhật CSDL
        DB::transaction(function () use ($identity, $toChuc, $txHash) {
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
                ]);
            }

            NhatKyHeThong::create([
                'taiKhoanId' => $this->adminAccountId,
                'doiTuongId' => $toChuc->taiKhoans()->first()?->id,
                'hanhDong'   => 'REVOKE_ROLE_ONCHAIN',
                'ketQua'     => 'THANH_CONG',
                'thoiGian'   => now(),
                'chiTiet'    => "Đã tước quyền On-chain thành công của tổ chức [{$toChuc->tenToChuc}]." . ($txHash ? " TxHash: {$txHash}" : " (Ví đã không còn quyền On-chain từ trước)"),
            ]);
        });
    }
}
