<?php

namespace App\Console\Commands;

use App\Models\BlockchainIdentity;
use App\Models\BlockchainTransaction;
use App\Models\ToChuc;
use App\Services\Blockchain\RoleMap;
use App\Services\Blockchain\SignerClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Thay các identity demo/seed bằng ví custodial thật do Signer quản lý.
 *
 * Không đụng tới identity của Cơ quan quản lý/Admin (KEY-DEMO-CQ*),
 * vì các alias đó được Signer ánh xạ về ví Admin Master.
 */
class RepairDemoBlockchainIdentities extends Command
{
    protected $signature = 'blockchain:repair-demo-identities
                            {--to-chuc= : Chỉ sửa identity của một tổ chức}
                            {--dry-run : Chỉ liệt kê, không tạo ví hoặc gửi giao dịch}';

    protected $description = 'Thay identity KEY-DEMO của tổ chức bằng ví custodial thật và cấp role on-chain';

    public function handle(SignerClient $signer): int
    {
        $query = BlockchainIdentity::query()
            ->where('trangThai', 'ACTIVE')
            ->where('signerKeyId', 'like', 'KEY-DEMO-%')
            ->where('signerKeyId', 'not like', 'KEY-DEMO-CQ%');

        if ($toChucId = $this->option('to-chuc')) {
            $query->where('toChucId', (int) $toChucId);
        }

        $identities = $query->orderBy('toChucId')->orderByDesc('id')->get()
            ->unique('toChucId')
            ->values();

        if ($identities->isEmpty()) {
            $this->info('Không tìm thấy identity KEY-DEMO của tổ chức cần sửa.');
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            foreach ($identities as $identity) {
                $this->line("Sẽ sửa tổ chức #{$identity->toChucId}: {$identity->signerKeyId}");
            }
            $this->warn('Dry-run: chưa tạo ví, chưa nạp gas, chưa gửi giao dịch.');
            return self::SUCCESS;
        }

        $adminKeyId = (string) config('truyXuat.blockchain.adminKeyId', 'ADMIN_MASTER_KEY');
        $failed = 0;

        foreach ($identities as $oldIdentity) {
            try {
                $organization = ToChuc::query()->findOrFail($oldIdentity->toChucId);
                $wallet = $signer->taoVi((int) $organization->id);
                $keyId = (string) ($wallet['keyId'] ?? '');
                $address = (string) ($wallet['diaChiVi'] ?? '');

                if ($keyId === '' || str_starts_with($keyId, 'KEY-DEMO-')
                    || ! preg_match('/^0x[a-fA-F0-9]{40}$/', $address)) {
                    throw new \\RuntimeException("Signer không trả về keyId/địa chỉ ví thật hợp lệ cho tổ chức #{$organization->id}.");
                }

                // Cấp gas cho đúng địa chỉ ví, không truyền signerKeyId vào endpoint này.
                $signer->ensureGas($address);

                $roleHash = RoleMap::cho($organization->loaiToChuc);
                $txHash = null;
                $blockNumber = null;

                if (! $signer->hasRole($roleHash, $address)) {
                    $result = $signer->tx($adminKeyId, 'grantRole', [
                        'account' => $address,
                        'role' => $roleHash,
                    ]);

                    if (empty($result['ok']) || ! preg_match('/^0x[a-fA-F0-9]{64}$/', (string) ($result['txHash'] ?? ''))) {
                        throw new \\RuntimeException('Không thể cấp role on-chain: ' . ($result['reason'] ?? 'Signer không trả txHash hợp lệ.'));
                    }

                    $txHash = $result['txHash'];
                    $blockNumber = $result['blockNumber'] ?? null;
                }

                DB::transaction(function () use ($oldIdentity, $organization, $keyId, $address, $txHash, $blockNumber): void {
                    // Nếu command được chạy lại sau lỗi DB, tái sử dụng identity đã tạo thay vì tạo bản ghi trùng.
                    $newIdentity = BlockchainIdentity::query()->where('signerKeyId', $keyId)->first();

                    if ($newIdentity) {
                        $newIdentity->update([
                            'diaChiVi' => $address,
                            'network' => $oldIdentity->network ?: 'Ethereum Sepolia Testnet',
                            'trangThai' => 'ACTIVE',
                        ]);
                    } else {
                        $newIdentity = BlockchainIdentity::query()->create([
                            'toChucId' => $organization->id,
                            'signerKeyId' => $keyId,
                            'diaChiVi' => $address,
                            'network' => $oldIdentity->network ?: 'Ethereum Sepolia Testnet',
                            'trangThai' => 'ACTIVE',
                        ]);
                    }

                    if ($oldIdentity->id !== $newIdentity->id) {
                        $oldIdentity->update(['trangThai' => 'REVOKED']);
                    }

                    if ($txHash) {
                        BlockchainTransaction::query()->create([
                            'txHash' => $txHash,
                            'blockNumber' => $blockNumber,
                            'eventName' => 'RoleGranted',
                            'doiTuongToChucId' => $organization->id,
                            'businessId' => (string) Str::uuid(),
                            'network' => 'Ethereum Sepolia Testnet',
                            'trangThai' => 'CONFIRMED',
                            'toChucId' => $organization->id,
                        ]);
                    }
                });

                $this->info("Đã sửa tổ chức #{$organization->id}: {$keyId} / {$address}");
            } catch (Throwable $e) {
                $failed++;
                $this->error("Tổ chức #{$oldIdentity->toChucId}: {$e->getMessage()}");
            }
        }

        if ($failed > 0) {
            $this->warn("Hoàn tất nhưng có {$failed} tổ chức chưa sửa được. Có thể chạy lại sau khi xử lý nguyên nhân.");
            return self::FAILURE;
        }

        $this->info('Đã xử lý xong các identity demo.');
        return self::SUCCESS;
    }
}
