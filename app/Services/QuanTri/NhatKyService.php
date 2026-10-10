<?php
namespace App\Services\QuanTri;

use App\Models\NhatKyHeThong;

class NhatKyService
{
    public function ghi(int $taiKhoanId, string $hanhDong, ?string $noiDung = null, ?string $diaChiIP = null): void
    {
        NhatKyHeThong::create([
            'taiKhoanId' => $taiKhoanId,
            'hanhDong' => $hanhDong,
            'noiDung' => $noiDung,
            'thoiGian' => now(),
            'diaChiIP' => $diaChiIP,
        ]);
    }
}