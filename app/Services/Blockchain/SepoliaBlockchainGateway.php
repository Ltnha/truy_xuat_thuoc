<?php

namespace App\Services\Blockchain;

/**
 * @deprecated Kept for compatibility; delegates to the Signer-backed v2 gateway.
 */
class SepoliaBlockchainGateway implements BlockchainGateway
{
    public function __construct(private SignerBlockchainGateway $gateway) {}

    public function ghiNhan(string $tenNghiepVu, array $duLieu): array
    {
        return $this->gateway->ghiNhan($tenNghiepVu, $duLieu);
    }

    public function grantRole(string $accountAddress, string $roleHash): string
    {
        return $this->gateway->grantRole($accountAddress, $roleHash);
    }

    public function taoViCustodial(): array
    {
        return $this->gateway->taoViCustodial();
    }
}
