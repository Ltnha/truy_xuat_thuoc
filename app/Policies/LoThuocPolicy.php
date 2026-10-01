<?php

namespace App\Policies;

use App\Models\LoThuoc;
use App\Models\TaiKhoan;

class LoThuocPolicy extends Policy
{
    public function xem(TaiKhoan $taiKhoan, LoThuoc $loThuoc): bool
    {
        return $taiKhoan->toChucId === $loThuoc->toChucId || $taiKhoan->vaiTro === 'QUAN_TRI_VIEN';
    }
}
