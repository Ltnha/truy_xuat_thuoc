<?php

namespace App\Providers;

use App\Services\Blockchain\BlockchainGateway;
use App\Services\Blockchain\FakeBlockchainGateway;
use App\Services\Blockchain\SignerBlockchainGateway;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $driver = (string) config('truyXuat.blockchain.driver', 'sepolia');

        if ($driver === 'fake') {
            $this->app->bind(BlockchainGateway::class, FakeBlockchainGateway::class);
            return;
        }

        if ($driver === 'sepolia') {
            $this->app->bind(BlockchainGateway::class, SignerBlockchainGateway::class);
            return;
        }

        throw new InvalidArgumentException("BLOCKCHAIN_DRIVER không được hỗ trợ: {$driver}. Chỉ chấp nhận sepolia hoặc fake.");
    }

    public function boot(): void
    {
        //
    }
}
