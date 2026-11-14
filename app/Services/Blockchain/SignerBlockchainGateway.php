<?php

namespace App\Services\Blockchain;

use App\Exceptions\NghiepVuException;
use App\Models\BlockchainIdentity;
use App\Models\ToChuc;
use App\Services\Blockchain\SignerClient;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SignerBlockchainGateway implements BlockchainGateway
{
    public function __construct(private SignerClient $signer) {}

    public function ghiNhan(string $tenNghiepVu, array $duLieu): array
    {
        $toChucId = (int) ($duLieu['toChucId'] ?? 0);
        $identity = BlockchainIdentity::query()
            ->where('toChucId', $toChucId)
            ->where('trangThai', 'ACTIVE')
            ->whereNotNull('signerKeyId')
            ->whereNotNull('diaChiVi')
            ->first();

        if (! $identity || ! $identity->signerKeyId || ! $identity->diaChiVi) {
            throw new NghiepVuException("Tổ chức #{$toChucId} chưa có ví Blockchain ACTIVE để ghi sự kiện.");
        }

        $batchMaLo = (string) ($duLieu['maLoId'] ?? '');
        if ($batchMaLo === '') {
            throw new NghiepVuException('Thiếu mã định danh gốc của lô để tạo batchId.');
        }
        $batchId = '0x' . hash('sha256', 'BATCH_' . $batchMaLo);

        if ($tenNghiepVu === 'BatchRegistered') {
            $productCode = (string) ($duLieu['maSanPham'] ?? '');
            if ($productCode === '') {
                throw new NghiepVuException('Thiếu mã sản phẩm để kiểm tra approveProduct trên blockchain.');
            }

            $productId = '0x' . hash('sha256', 'PRODUCT_' . $productCode);
            $approval = $this->signer->isProductApproved($productId);
            if (empty($approval['isApproved'])) {
                throw new NghiepVuException('Sản phẩm chưa được approveProduct xác nhận trên blockchain.');
            }

            $batchState = $this->signer->getBatchState($batchId);
            if (! empty($batchState['batch']['exists'])) {
                $existingHash = $batchState['registerTxHash'] ?? null;
                if (! is_string($existingHash) || ! preg_match('/^0x[a-fA-F0-9]{64}$/', $existingHash)) {
                    throw new NghiepVuException('Batch đã tồn tại on-chain nhưng không tìm thấy txHash BatchRegistered thật.');
                }
                return [
                    'txHash' => $existingHash,
                    'blockNumber' => $batchState['registerBlockNumber'] ?? null,
                    'status' => 'confirmed',
                ];
            }

            $this->signer->ensureGas((string) $identity->diaChiVi);
            $result = $this->signer->tx((string) $identity->signerKeyId, 'registerBatch', [
                'batchId' => $batchId,
            ]);
        } else {
            $businessSource = (string) ($duLieu['businessId'] ?? $duLieu['maGiaoDich'] ?? '');
            if ($businessSource === '') {
                throw new NghiepVuException('Thiếu businessId để ghi sự kiện lô.');
            }

            $businessId = '0x' . hash('sha256', $businessSource);
            $eventType = '0x' . \kornrunner\Keccak::hash(strtoupper($tenNghiepVu), 256);
            $payload = $duLieu;
            $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
            if ($payloadJson === false) {
                throw new RuntimeException('Không thể chuẩn hóa payload sự kiện.');
            }
            $payloadHash = '0x' . hash('sha256', $payloadJson);

            $existingEvent = $this->signer->findBatchEvent($batchId, $businessId, $eventType);
            if (! empty($existingEvent['isRecorded'])) {
                $existingHash = $existingEvent['txHash'] ?? null;
                if (! is_string($existingHash) || ! preg_match('/^0x[a-fA-F0-9]{64}$/', $existingHash)) {
                    throw new NghiepVuException('Event đã tồn tại on-chain nhưng không truy xuất được txHash thật.');
                }
                return [
                    'txHash' => $existingHash,
                    'blockNumber' => $existingEvent['blockNumber'] ?? null,
                    'status' => 'confirmed',
                ];
            }

            $this->signer->ensureGas((string) $identity->diaChiVi);
            $result = $this->signer->tx((string) $identity->signerKeyId, 'recordBatchEvent', [
                'batchId' => $batchId,
                'businessId' => $businessId,
                'eventType' => $eventType,
                'payloadHash' => $payloadHash,
            ]);
        }

        if (empty($result['ok']) || empty($result['txHash']) || ! preg_match('/^0x[a-fA-F0-9]{64}$/', (string) $result['txHash'])) {
            throw new NghiepVuException('Signer không trả về txHash hợp lệ: ' . ($result['reason'] ?? 'Giao dịch thất bại.'));
        }

        return [
            'txHash' => (string) $result['txHash'],
            'blockNumber' => isset($result['blockNumber']) ? (int) $result['blockNumber'] : null,
            'status' => 'confirmed',
        ];
    }

    public function grantRole(string $accountAddress, string $roleHash): string
    {
        $adminKeyId = config('truyXuat.blockchain.adminKeyId', env('ADMIN_MASTER_KEY_ID', 'ADMIN_MASTER_KEY'));
        $this->signer->ensureGas($adminKeyId);
        $result = $this->signer->tx($adminKeyId, 'grantRole', [
            'account' => $accountAddress,
            'role' => $roleHash,
        ]);
        if (empty($result['ok']) || empty($result['txHash'])) {
            throw new NghiepVuException('grantRole thất bại: ' . ($result['reason'] ?? 'Signer không trả txHash.'));
        }
        return (string) $result['txHash'];
    }

    public function taoViCustodial(): array
    {
        throw new RuntimeException('Signer custodial wallet yêu cầu toChucId; hãy gọi SignerClient::taoVi($toChucId).');
    }
}
