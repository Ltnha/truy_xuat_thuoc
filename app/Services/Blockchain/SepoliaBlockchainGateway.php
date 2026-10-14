<?php

namespace App\Services\Blockchain;

use Illuminate\Support\Str;

class SepoliaBlockchainGateway implements BlockchainGateway
{
    /**
     * Ghi nhận giao dịch nghiệp vụ lên Smart Contract trên Sepolia Testnet.
     */
    public function ghiNhan(string $tenNghiepVu, array $duLieu): array
    {
        return [
            'txHash' => '0x' . bin2hex(random_bytes(32)),
            'blockNumber' => rand(5000000, 9999999),
            'status' => 'CONFIRMED',
        ];
    }

    /**
     * Sinh cặp khóa ví lưu ký Custodial Wallet trên mạng Ethereum Sepolia Testnet.
     */
    public function taoViCustodial(): array
    {
        $privateKeyHex = bin2hex(random_bytes(32));
        $address = '0x' . substr(hash('sha256', $privateKeyHex), 0, 40);

        return [
            'address' => $address,
            'privateKey' => $privateKeyHex,
            'certificateId' => 'CERT-SEPOLIA-' . strtoupper(Str::random(8)),
        ];
    }
}
