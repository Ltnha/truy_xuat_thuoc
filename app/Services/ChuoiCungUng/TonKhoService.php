<?php

namespace App\Services\ChuoiCungUng;

use App\Enums\TransferStatus;
use App\Exceptions\NghiepVuException;
use App\Models\ChuyenGiao;
use App\Models\TonKhoLo;
use Illuminate\Support\Facades\DB;

class TonKhoService
{
    /**
     * Số lượng hiện có trong bảng tonKhoLo (chưa trừ giữ chỗ)
     */
    public function soLuongHienCo(int $loThuocId, int $toChucId): int
    {
        return (int) TonKhoLo::query()
            ->where('loThuocId', $loThuocId)
            ->where('toChucId', $toChucId)
            ->value('soLuong');
    }

    /**
     * Số lượng khả dụng = hiện có - số lượng đang giữ chỗ (PENDING)
     */
    public function soLuongKhaDung(int $loThuocId, int $toChucId): int
    {
        $dangCho = ChuyenGiao::query()
            ->where('loThuocId', $loThuocId)
            ->where('benGuiId', $toChucId)
            ->where('trangThai', TransferStatus::pending->value)
            ->sum('soLuong');

        return max(0, $this->soLuongHienCo($loThuocId, $toChucId) - (int) $dangCho);
    }

    /**
     * Trừ tồn kho (dùng khi bên nhận xác nhận)
     */
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
        $tonKho->update(['ngayCapNhat' => now()]);
    }

    /**
     * Cộng tồn kho (dùng khi bên nhận xác nhận)
     */
    public function cong(int $loThuocId, int $toChucId, int $soLuong): void
    {
        $tonKho = TonKhoLo::query()
            ->where('loThuocId', $loThuocId)
            ->where('toChucId', $toChucId)
            ->lockForUpdate()
            ->first();

        if ($tonKho) {
            $tonKho->increment('soLuong', $soLuong);
            $tonKho->update(['ngayCapNhat' => now()]);

            return;
        }

        DB::table('tonKhoLo')->insert([
            'loThuocId'   => $loThuocId,
            'toChucId'    => $toChucId,
            'soLuong'     => $soLuong,
            'ngayCapNhat' => now(),
        ]);
    }
}
