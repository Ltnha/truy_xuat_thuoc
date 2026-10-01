<?php

namespace App\Providers;

use App\Services\Blockchain\BlockchainGateway;
use App\Services\Blockchain\FakeBlockchainGateway;
use App\Services\Blockchain\PolygonBlockchainGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $driver = config('truyXuat.blockchain.driver', 'fake');

        $this->app->bind(BlockchainGateway::class, match ($driver) {
            'polygon' => PolygonBlockchainGateway::class,
            default => FakeBlockchainGateway::class,
        });
    }

    public function boot(): void
    {
        // No-op scaffold placeholder; business services remain intentionally thin.
    }
}