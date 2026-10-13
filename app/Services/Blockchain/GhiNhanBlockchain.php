<?php
namespace App\Services\Blockchain;

use App\Exceptions\NghiepVuException;
use App\Models\BlockchainTransaction;
use App\Models\BlockchainOutbox;
use App\Models\DonViSanPham;
use App\Models\LoThuoc;
use App\Models\ToChuc;
use Illuminate\Support\Facades\Log;
use kornrunner\Keccak;

class GhiNhanBlockchain
{
    public function __construct(
        protected BlockchainGateway $gateway
    ) {
    }

    public function ghi(
        string $tenNghiepVu,
        string $businessId,
        ToChuc $toChuc,
        LoThuoc $loThuoc,
        array $thamChieu = [],
    ): ?string
    {
        if (config('truyXuat.blockchain.driver') === 'sepolia') {
            $this->taoOutbox($tenNghiepVu, $businessId, $toChuc, $loThuoc, $thamChieu);

            return null;
        }

        $ketQua = $this->gateway->ghiNhan($tenNghiepVu, [
            'businessId' => $businessId,
            'toChucId' => $toChuc->id,
            'loThuocId' => $loThuoc->id,
            ...$thamChieu,
        ]);
        $txHash = $ketQua['txHash'] ?? null;

        if (! is_string($txHash) || $txHash === '') {
            throw new NghiepVuException('Blockchain không trả về mã giao dịch hợp lệ.');
        }

        BlockchainTransaction::create([
            'txHash' => $txHash,
            'blockNumber' => $ketQua['blockNumber'] ?? null,
            'eventName' => $tenNghiepVu,
            'businessId' => $businessId,
            'thoiGian' => now(),
            'network' => config('truyXuat.blockchain.mang'),
            'trangThai' => $ketQua['status'] === 'submitted' ? 'PENDING' : 'CONFIRMED',
            'toChucId' => $toChuc->id,
            'loThuocId' => $loThuoc->id,
            'chuyenGiaoId' => $thamChieu['chuyenGiaoId'] ?? null,
            'donViSanPhamId' => $thamChieu['donViSanPhamId'] ?? null,
            'banLeId' => $thamChieu['banLeId'] ?? null,
        ]);

        Log::info('Blockchain submission recorded: '.$tenNghiepVu, ['businessId' => $businessId, 'txHash' => $txHash]);

        return $txHash;
    }

    public function ghiQuyetDinh(
        string $tenNghiepVu,
        string $businessId,
        string $functionName,
        array $arguments,
        ?ToChuc $toChuc = null,
        ?LoThuoc $loThuoc = null,
        array $thamChieu = [],
    ): BlockchainOutbox {
        $payloadHash = $this->hashPayload([
            'schemaVersion' => 1,
            'eventName' => $tenNghiepVu,
            'businessId' => $businessId,
            'arguments' => $arguments,
        ]);
        $callData = app(BlockchainCallEncoder::class)->encode($functionName, [...$arguments, $payloadHash]);
        $eventSignature = match ($functionName) {
            'setOrganizationStatus' => 'OrganizationStatusChanged(bytes32,uint8,bytes32,uint256)',
            'setProductStatus' => 'ProductStatusChanged(bytes32,uint8,bytes32,uint256)',
            'recordAuditEvent' => 'BatchEventRecorded(bytes32,bytes32,address,bytes32,bytes32,uint256)',
            'recallBatch' => 'BatchRecalled(bytes32,address,bytes32,uint256)',
            default => throw new \InvalidArgumentException("Unsupported blockchain function: {$functionName}."),
        };
        $indexedTopics = match ($functionName) {
            'setOrganizationStatus', 'setProductStatus' => [$arguments[0]],
            'recordAuditEvent' => [],
            'recallBatch' => [$arguments[0]],
            default => [],
        };

        return $this->taoOutboxBanGhi(
            $tenNghiepVu,
            $businessId,
            $functionName,
            $callData,
            $payloadHash,
            $toChuc,
            $loThuoc,
            $thamChieu,
            '0x'.Keccak::hash($eventSignature, 256),
            $indexedTopics,
        );
    }

    public function diaChiVi(ToChuc $toChuc): string
    {
        $diaChi = $toChuc->blockchainIdentity?->address;

        if (! is_string($diaChi) || $diaChi === '') {
            throw new NghiepVuException('Tổ chức chưa được cấp định danh blockchain.');
        }

        return $diaChi;
    }

    private function taoOutbox(
        string $tenNghiepVu,
        string $businessId,
        ToChuc $toChuc,
        LoThuoc $loThuoc,
        array $thamChieu,
    ): BlockchainOutbox {
        $batchId = $this->bytes32((string) $loThuoc->maLo);
        $payload = [
            'schemaVersion' => 1,
            'eventName' => $tenNghiepVu,
            'businessId' => $businessId,
            'organizationId' => (int) $toChuc->id,
            'toChucId' => (int) $toChuc->id,
            'lotId' => (int) $loThuoc->id,
            ...$thamChieu,
        ];
        $payloadHash = $this->hashPayload($payload);

        [$functionName, $arguments, $indexedTopics] = match ($tenNghiepVu) {
            'BatchRegistered' => [
                'registerBatch',
                [
                    $batchId,
                    $this->bytes32((string) ($loThuoc->sanPham?->maSanPham ?? $loThuoc->sanPhamId)),
                    $loThuoc->hanSuDung->copy()->endOfDay()->timestamp,
                    $payloadHash,
                ],
                [$batchId, $this->bytes32((string) ($loThuoc->sanPham?->maSanPham ?? $loThuoc->sanPhamId))],
            ],
            'TransferSubmitted', 'TransferReceived' => $this->transferArguments(
                $tenNghiepVu,
                $batchId,
                $businessId,
                $thamChieu,
                $payloadHash,
            ),
            'UnitDispensed' => $this->unitArguments($batchId, $thamChieu, $payloadHash),
            'BatchRecalled' => [
                'recallBatch',
                [$batchId, $payloadHash],
                [$batchId],
            ],
            default => throw new \InvalidArgumentException("Unsupported blockchain event: {$tenNghiepVu}."),
        };

        $callData = app(BlockchainCallEncoder::class)->encode($functionName, $arguments);
        $eventSignature = match ($functionName) {
            'registerBatch' => 'BatchRegistered(bytes32,bytes32,address,bytes32,uint256)',
            'recordTransfer' => 'TransferStatusChanged(bytes32,bytes32,bytes32,bytes32,uint256,uint8,bytes32,uint256)',
            'dispenseUnit' => 'UnitDispensed(bytes32,bytes32,address,bytes32,uint256)',
            'recallBatch' => 'BatchRecalled(bytes32,address,bytes32,uint256)',
        };

        return $this->taoOutboxBanGhi(
            $tenNghiepVu,
            $businessId,
            $functionName,
            $callData,
            $payloadHash,
            $toChuc,
            $loThuoc,
            $thamChieu,
            '0x'.Keccak::hash($eventSignature, 256),
            $indexedTopics,
        );
    }

    private function transferArguments(
        string $eventName,
        string $batchId,
        string $businessId,
        array $references,
        string $payloadHash,
    ): array {
        $status = $eventName === 'TransferSubmitted' ? 1 : 2;
        $senderId = (int) ($references[$status === 1 ? 'toChucId' : 'benGuiId'] ?? $references['benGuiId'] ?? 0);
        $receiverId = (int) ($references[$status === 1 ? 'benNhanId' : 'toChucId'] ?? $references['benNhanId'] ?? 0);
        $transferId = $this->bytes32($businessId);
        $senderHash = $this->bytes32((string) $senderId);
        $receiverHash = $this->bytes32((string) $receiverId);
        $quantity = (int) ($references['soLuong'] ?? 0);

        return [
            'recordTransfer',
            [$batchId, $transferId, $senderHash, $receiverHash, $quantity, $status, $payloadHash],
            [$batchId, $transferId],
        ];
    }

    private function unitArguments(string $batchId, array $references, string $payloadHash): array
    {
        $donVi = DonViSanPham::query()->findOrFail((int) ($references['donViSanPhamId'] ?? 0));
        $unitId = $this->bytes32((string) $donVi->maDonVi);

        return ['dispenseUnit', [$unitId, $batchId, $payloadHash], [$unitId, $batchId]];
    }

    private function taoOutboxBanGhi(
        string $eventName,
        string $businessId,
        string $functionName,
        string $callData,
        string $payloadHash,
        ?ToChuc $toChuc,
        ?LoThuoc $loThuoc,
        array $references,
        ?string $eventTopic = null,
        array $indexedTopics = [],
    ): BlockchainOutbox {
        $idempotencyKey = hash('sha256', config('truyXuat.blockchain.chainId').':'.$eventName.':'.$businessId);

        return BlockchainOutbox::query()->firstOrCreate(
            ['idempotencyKey' => $idempotencyKey],
            [
                'eventName' => $eventName,
                'businessId' => $businessId,
                'functionName' => $functionName,
                'payload' => [
                    'data' => $callData,
                    'payloadHash' => $payloadHash,
                    'eventTopic' => $eventTopic,
                    'indexedTopics' => $indexedTopics,
                    'references' => $references,
                ],
                'network' => config('truyXuat.blockchain.mang'),
                'trangThai' => 'QUEUED',
                'toChucId' => $toChuc?->id,
                'loThuocId' => $loThuoc?->id,
                'chuyenGiaoId' => $references['chuyenGiaoId'] ?? null,
                'donViSanPhamId' => $references['donViSanPhamId'] ?? null,
                'banLeId' => $references['banLeId'] ?? null,
            ],
        );
    }

    private function hashPayload(array $payload): string
    {
        return $this->bytes32(json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ));
    }

    private function bytes32(string $value): string
    {
        return '0x'.Keccak::hash($value, 256);
    }
}