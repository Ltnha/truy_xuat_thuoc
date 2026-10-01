<?php
namespace App\Services\Blockchain;

use LogicException;

class PolygonBlockchainGateway implements BlockchainGateway
{
    public function ghiNhan(string $tenNghiepVu, array $duLieu): array
    {
        throw new LogicException('Polygon blockchain integration is not implemented yet.');
    }
}