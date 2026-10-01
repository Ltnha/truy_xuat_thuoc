<?php
namespace App\Services\Blockchain;

use kornrunner\Keccak;

class ChainId
{
    public static function tu(string $giaTriBusiness): string
    {
        $hash = Keccak::hash($giaTriBusiness, 256);

        return '0x'.substr($hash, 0, 16);
    }
}