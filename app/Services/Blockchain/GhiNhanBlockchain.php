<?php
namespace App\Services\Blockchain;

use Illuminate\Support\Facades\Log;

class GhiNhanBlockchain
{
    public function __construct(
        protected BlockchainGateway $gateway
    ) {
    }

    public function ghi(string $tenNghiepVu, array $duLieu): array
    {
        Log::info('Blockchain submission: '.$tenNghiepVu, $duLieu);

        return $this->gateway->ghiNhan($tenNghiepVu, $duLieu);
    }
}