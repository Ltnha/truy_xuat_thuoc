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
}
