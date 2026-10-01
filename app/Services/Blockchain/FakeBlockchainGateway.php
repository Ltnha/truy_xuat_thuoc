<?php
namespace App\Services\Blockchain;

class FakeBlockchainGateway implements BlockchainGateway
{
    public function ghiNhan(string $tenNghiepVu, array $duLieu): array
    {
        logger()->warning('Using fake blockchain gateway for '.$tenNghiepVu, ['duLieu' => $duLieu]);

        return [
            'txHash' => '0xfake'.bin2hex(random_bytes(8)),
            'chainId' => ChainId::tu($tenNghiepVu),
            'status' => 'submitted',
        ];
    }
}