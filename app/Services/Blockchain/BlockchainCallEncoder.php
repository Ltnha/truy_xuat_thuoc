<?php

namespace App\Services\Blockchain;

use InvalidArgumentException;
use kornrunner\Keccak;

class BlockchainCallEncoder
{
    public function encode(string $function, array $arguments): string
    {
        $signature = match ($function) {
            'setOrganizationStatus' => 'setOrganizationStatus(bytes32,uint8,bytes32)',
            'setProductStatus' => 'setProductStatus(bytes32,uint8,bytes32)',
            'recordAuditEvent' => 'recordAuditEvent(bytes32,bytes32,bytes32)',
            'registerBatch' => 'registerBatch(bytes32,bytes32,uint256,bytes32)',
            'recordTransfer' => 'recordTransfer(bytes32,bytes32,bytes32,bytes32,uint256,uint8,bytes32)',
            'dispenseUnit' => 'dispenseUnit(bytes32,bytes32,bytes32)',
            'recallBatch' => 'recallBatch(bytes32,bytes32)',
            default => throw new InvalidArgumentException('Unsupported blockchain function.'),
        };

        return '0x'.substr(Keccak::hash($signature, 256), 0, 8).implode('', array_map(
            fn (string|int $argument): string => $this->word($argument),
            $arguments,
        ));
    }

    private function word(string|int $value): string
    {
        if (is_int($value)) {
            if ($value < 0) {
                throw new InvalidArgumentException('ABI integer arguments must be non-negative.');
            }

            return str_pad(dechex($value), 64, '0', STR_PAD_LEFT);
        }

        if (! preg_match('/^0x[a-fA-F0-9]{64}$/', $value)) {
            throw new InvalidArgumentException('ABI bytes32 arguments must be 32-byte hex values.');
        }

        return strtolower(substr($value, 2));
    }
}
