<?php
namespace App\Services\Concerns;

use App\Exceptions\NghiepVuException;
use App\Models\NhatKyHeThong;
use App\Models\TaiKhoan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

trait ChayNghiepVu
{
    protected function chayNghiepVu(
        string $hanhDong,
        ?TaiKhoan $taiKhoan,
        callable $callback,
        ?string $diaChiIP = null,
    ): mixed
    {
        DB::beginTransaction();

        try {
            $result = $callback();
            DB::commit();

            return $result;
        } catch (Throwable $throwable) {
            DB::rollBack();

            if ($throwable instanceof NghiepVuException) {
                if ($taiKhoan) {
                    NhatKyHeThong::create([
                        'taiKhoanId' => $taiKhoan->id,
                        'hanhDong' => $hanhDong.'_REJECTED',
                        'thoiGian' => now(),
                        'noiDung' => $throwable->getMessage(),
                        'diaChiIP' => $diaChiIP,
                    ]);
                } else {
                    Log::warning($hanhDong.'_REJECTED', ['message' => $throwable->getMessage()]);
                }
            }

            throw $throwable;
        }
    }
}