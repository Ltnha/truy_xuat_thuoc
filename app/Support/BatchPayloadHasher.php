<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class BatchPayloadHasher
{
    public static function hash(object|array $lo): string
    {
        $data = is_array($lo) ? (object) $lo : $lo;

        $payload = [
            'maLo' => (string) $data->maLo,
            'maLoNghiepVu' => (string) $data->maLoNghiepVu,
            'sanPhamId' => (int) $data->sanPhamId,
            'toChucId' => (int) $data->toChucId,
            'donViTinh' => (string) ($data->donViTinh ?: 'Hộp'),
            'ngaySanXuat' => Carbon::parse($data->ngaySanXuat)->toDateString(),
            'hanSuDung' => Carbon::parse($data->hanSuDung)->toDateString(),
        ];

        $json = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION
        );

        return '0x' . hash('sha256', (string) $json);
    }
}
