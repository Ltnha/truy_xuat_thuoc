<?php
namespace App\Services\Concerns;

use App\Exceptions\NghiepVuException;
use Illuminate\Support\Facades\DB;

trait ChayNghiepVu
{
    protected function chayNghiepVu(callable $callback): mixed
    {
        DB::beginTransaction();

        try {
            $result = $callback();
            DB::commit();

            return $result;
        } catch (\Throwable $throwable) {
            DB::rollBack();

            if ($throwable instanceof NghiepVuException) {
                throw $throwable;
            }

            throw $throwable;
        }
    }
}