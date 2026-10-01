<?php
namespace App\Services\QuanTri;

use App\Models\NhatKyHeThong;

class NhatKyService
{
    public function ghi(string $hanhDong, ?int $taiKhoanId = null, ?string $noiDung = null): void
    {
        NhatKyHeThong::create([
            'taiKhoanId' => $taiKhoanId,
            'hanhDong' => $hanhDong,
            'noiDung' => $noiDung,
            'thoiGian' => now(),
        ]);
    }
}