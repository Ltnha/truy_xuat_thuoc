<?php

namespace App\Services\LoThuoc;

use App\Enums\QRType;
use App\Models\LoThuoc;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QrService
{
    public function sinhChoLo(LoThuoc $loThuoc): void
    {
        DB::table('qrCode')->insert([
            'giaTriQR' => Str::random(32),
            'loaiQR' => QRType::batch->value,
            'loThuocId' => $loThuoc->id,
            'donViSanPhamId' => null,
            'ngayTao' => now(),
            'trangThai' => 'ACTIVE',
        ]);

        DB::table('donViSanPham')
            ->where('loThuocId', $loThuoc->id)
            ->orderBy('id')
            ->chunkById(max(1, (int) config('truyXuat.kichThuocChunk', 1000)), function ($donViSanPhams): void {
                $maQRs = $donViSanPhams->map(fn ($donVi) => [
                    'giaTriQR' => Str::random(32),
                    'loaiQR' => QRType::unit->value,
                    'loThuocId' => null,
                    'donViSanPhamId' => $donVi->id,
                    'ngayTao' => now(),
                    'trangThai' => 'ACTIVE',
                ])->all();

                DB::table('qrCode')->insert($maQRs);
            });
    }
}
