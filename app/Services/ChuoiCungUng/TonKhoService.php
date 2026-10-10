<?php

namespace App\Services\ChuoiCungUng;

use App\Exceptions\NghiepVuException;
use App\Enums\TransferStatus;
use App\Models\ChuyenGiao;
use App\Models\TonKhoLo;
use Illuminate\Support\Facades\DB;

class TonKhoService
{
    public function soLuongHienCo(int $loThuocId, int $toChucId): int
    {
        return (int) TonKhoLo::query()
            ->where('loThuocId', $loThuocId)
            ->where('toChucId', $toChucId)
            ->value('soLuong');
    }

    public function soLuongKhaDung(int $loThuocId, int $toChucId): int
    {
        $dangCho = ChuyenGiao::query()
            ->where('loThuocId', $loThuocId)
            ->where('benGuiId', $toChucId)
            ->whereIn('trangThai', [TransferStatus::pending->value, TransferStatus::inTransit->value])
            ->sum('soLuong');

        return max(0, $this->soLuongHienCo($loThuocId, $toChucId) - (int) $dangCho);
    }

    public function tru(int $loThuocId, int $toChucId, int $soLuong): void
    {
        $tonKho = TonKhoLo::query()
            ->where('loThuocId', $loThuocId)
            ->where('toChucId', $toChucId)
            ->lockForUpdate()
            ->first();

        if (! $tonKho || $tonKho->soLuong < $soLuong) {
            throw new NghiepVuException('Bạn không có đủ số lượng tồn kho để thực hiện thao tác này.');
        }

        $tonKho->decrement('soLuong', $soLuong);
    }

    public function cong(int $loThuocId, int $toChucId, int $soLuong): void
    {
        $tonKho = TonKhoLo::query()
            ->where('loThuocId', $loThuocId)
            ->where('toChucId', $toChucId)
            ->lockForUpdate()
            ->first();

        if ($tonKho) {
            $tonKho->increment('soLuong', $soLuong);

            return;
        }

        DB::table('tonKhoLo')->insert([
            'loThuocId' => $loThuocId,
            'toChucId' => $toChucId,
            'soLuong' => $soLuong,
            'ngayCapNhat' => now(),
        ]);
    }
}
