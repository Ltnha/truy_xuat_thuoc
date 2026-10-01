<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

abstract class Service
{
    protected function chayNghiepVu(callable $callback): mixed
    {
        return DB::transaction(fn () => $callback());
    }

    protected function ghiNhatKy(string $hanhDong, array $duLieu = []): void
    {
        // Placeholder hook for future module activity logging.
        // Real services should record business events here or through a dedicated service.
    }
}
