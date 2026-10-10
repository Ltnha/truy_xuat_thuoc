<?php

namespace App\Services\LoThuoc;

use App\Enums\UnitStatus;
use App\Models\LoThuoc;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DonViSanPhamService
{
    public function sinhHangLoat(LoThuoc $loThuoc): void
    {
        $kichThuocChunk = max(1, (int) config('truyXuat.kichThuocChunk', 1000));

        for ($batDau = 1; $batDau <= $loThuoc->soLuong; $batDau += $kichThuocChunk) {
            $cacDonVi = [];
            $ketThuc = min($loThuoc->soLuong, $batDau + $kichThuocChunk - 1);

            for ($soThuTu = $batDau; $soThuTu <= $ketThuc; $soThuTu++) {
                $cacDonVi[] = [
                    'maDonVi' => $loThuoc->maLoNghiepVu.'-'.str_pad((string) $soThuTu, 6, '0', STR_PAD_LEFT),
                    'serialNumber' => strtoupper(Str::random(16)),
                    'loThuocId' => $loThuoc->id,
                    'trangThai' => UnitStatus::available->value,
                    'ngayTao' => now(),
                ];
            }

            DB::table('donViSanPham')->insert($cacDonVi);
        }
    }
}
