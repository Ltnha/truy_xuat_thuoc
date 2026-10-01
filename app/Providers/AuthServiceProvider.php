<?php

namespace App\Providers;

use App\Models\LoThuoc;
use App\Models\ToChuc;
use App\Policies\LoThuocPolicy;
use App\Policies\ToChucPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        LoThuoc::class => LoThuocPolicy::class,
        ToChuc::class => ToChucPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
