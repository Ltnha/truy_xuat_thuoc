<?php

namespace App\Services\Blockchain;

use App\Models\LoThuoc;
use kornrunner\Keccak;

class SepoliaBatchEvent
{
    public function inputs(LoThuoc $loThuoc): array
    {
        return [
            'batchId' => $this->bytes32($loThuoc->maLoNghiepVu),
            'businessId' => $this->bytes32($loThuoc->maLo.':BatchRegistered:v1'),
            'eventType' => $this->bytes32('BatchRegistered'),
            'payloadHash' => $this->bytes32($this->payload($loThuoc)),
        ];
    }

    public function eventTopic(): string
    {
        return '0x'.Keccak::hash(
            'BatchEventRecorded(bytes32,bytes32,address,bytes32,bytes32,uint256)',
            256
        );
    }

    public function bytes32(string $value): string
    {
        return '0x'.Keccak::hash($value, 256);
    }

    private function payload(LoThuoc $loThuoc): string
    {
        return json_encode([
            'schemaVersion' => 1,
            'eventName' => 'BatchRegistered',
            'batchCode' => $loThuoc->maLoNghiepVu,
            'productId' => (int) $loThuoc->sanPhamId,
            'quantity' => (int) $loThuoc->soLuong,
            'manufacturedAt' => $loThuoc->ngaySanXuat->format('Y-m-d'),
            'expiresAt' => $loThuoc->hanSuDung->format('Y-m-d'),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
