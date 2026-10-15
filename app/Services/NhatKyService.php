<?php

namespace App\Services;

use App\Models\NhatKyHeThong;
use Illuminate\Support\Facades\Auth;

class NhatKyService
{
    public static function ghiLog(
        string $hanhDong,
        string $ketQua,
        string $chiTiet,<?php

namespace App\Services;

use App\Models\NhatKyHeThong;
use Illuminate\Support\Facades\Auth;

class NhatKyService
{
    public static function ghiLog(
        string $hanhDong,
        string $ketQua,
        string $chiTiet,
        ?int $doiTuongId = null
    ): void {
        NhatKyHeThong::create([
            'taiKhoanId' => Auth::id(),
            'doiTuongId' => $doiTuongId,
            'hanhDong'   => $hanhDong,
            'ketQua'     => $ketQua,
            'thoiGian'   => now(),
            'chiTiet'    => $chiTiet,
        ]);
    }
}
        ?int $doiTuongId = null
    ): void {
        NhatKyHeThong::create([
            'taiKhoanId' => Auth::id(),
            'doiTuongId' => $doiTuongId,
            'hanhDong'   => $hanhDong,
            'ketQua'     => $ketQua,
            'thoiGian'   => now(),
            'chiTiet'    => $chiTiet,
        ]);
    }
}
