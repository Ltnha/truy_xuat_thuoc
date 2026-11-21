<?php

namespace App\Services\LoThuoc;

use App\Models\LoThuoc;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QrService
{
    /** Mỗi lô chỉ có một QR đại diện cho toàn bộ lô thuốc. */
    public function sinhChoLo(LoThuoc $loThuoc): void
    {
        DB::table('qrCode')->updateOrInsert(
            ['loThuocId' => $loThuoc->id],
            [
                'giaTriQR' => (string) (DB::table('qrCode')->where('loThuocId', $loThuoc->id)->value('giaTriQR') ?: Str::random(40)),
                'ngayTao' => now(),
                'trangThai' => 'ACTIVE',
            ]
        );
    }
}
