<?php

namespace App\Services\Blockchain;

class FakeBlockchainGateway implements BlockchainGateway
{
    public function ghiNhan(string $tenNghiepVu, array $duLieu): array
    {
        logger()->warning('Using fake blockchain gateway for ' . $tenNghiepVu, ['duLieu' => $duLieu]);

        return [
            'txHash' => '0x' . bin2hex(random_bytes(32)),
            'chainId' => ChainId::tu($tenNghiepVu),
            'status' => 'submitted',
        ];
    }

    /**
     * Giả lập cấp vai trò On-chain khi dùng Fake Driver
     */
    public function grantRole(string $accountAddress, string $roleHash): string
    {
        return '0x' . bin2hex(random_bytes(32));
    }

    /**
     * Giả lập sinh ví custodial (đủ chữ ký interface BlockchainGateway)
     */
    public function taoViCustodial(): array
    {
        $privateKeyHex = bin2hex(random_bytes(32));
        $address = '0x' . substr(hash('sha256', hex2bin($privateKeyHex)), 24, 40);

        return [
            'address' => $address,
            'privateKey' => $privateKeyHex,
            'certificateId' => 'CERT-FAKE-' . strtoupper(bin2hex(random_bytes(4))),
        ];
    }
}