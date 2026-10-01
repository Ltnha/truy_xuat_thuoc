<?php
namespace App\Services\Blockchain;

interface BlockchainGateway
{
    public function ghiNhan(string $tenNghiepVu, array $duLieu): array;
}