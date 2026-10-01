<?php

namespace App\Policies;

use App\Models\TaiKhoan;
use App\Models\ToChuc;

class ToChucPolicy extends Policy
{
    public function xem(TaiKhoan $taiKhoan, ToChuc $toChuc): bool
    {
        return $taiKhoan->toChucId === $toChuc->id || $taiKhoan->vaiTro === 'QUAN_TRI_VIEN';
    }
}
