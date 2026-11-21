<?php

namespace App\Services\ChuoiCungUng;

use App\Exceptions\NghiepVuException;
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
        return $this->soLuongHienCo($loThuocId, $toChucId);
    }

    public function tru(int $loThuocId, int $toChucId, int $soLuong): void
    {
        $tonKho = TonKhoLo::query()->where('loThuocId', $loThuocId)
            ->where('toChucId', $toChucId)->lockForUpdate()->first();

        if (! $tonKho || $soLuong < 1 || (int) $tonKho->soLuong < $soLuong) {
            throw new NghiepVuException('Tồn kho không đủ hoặc tổ chức không còn là chủ sở hữu lô.');
        }

        $tonKho->decrement('soLuong', $soLuong);
        $tonKho->update(['ngayCapNhat' => now()]);
    }

    /** Chỉ dùng khởi tạo tồn kho lần đầu khi registerBatch đã xác nhận. */
    public function cong(int $loThuocId, int $toChucId, int $soLuong): void
    {
        $tonKho = TonKhoLo::query()->where('loThuocId', $loThuocId)->lockForUpdate()->first();
        if (! $tonKho) {
            TonKhoLo::query()->create([
                'loThuocId' => $loThuocId,
                'toChucId' => $toChucId,
                'soLuong' => $soLuong,
                'ngayCapNhat' => now(),
            ]);
            return;
        }

        if ((int) $tonKho->toChucId !== $toChucId) {
            throw new NghiepVuException('Lô đã có chủ sở hữu khác trong kho; không thể tạo thêm dòng tồn kho.');
        }

        $tonKho->increment('soLuong', $soLuong);
        $tonKho->update(['ngayCapNhat' => now()]);
    }

    /** Chuyển quyền sở hữu toàn lô bằng cách cập nhật một dòng tồn kho duy nhất. */
    public function chuyenChuSoHuu(int $loThuocId, int $benGuiId, int $benNhanId, int $soLuong): void
    {
        $tonKho = TonKhoLo::query()->where('loThuocId', $loThuocId)->lockForUpdate()->first();
        if (! $tonKho || (int) $tonKho->toChucId !== $benGuiId) {
            throw new NghiepVuException('Dòng tồn kho không thuộc bên gửi; không thể chuyển chủ sở hữu.');
        }
        if ($soLuong < 1 || (int) $tonKho->soLuong !== $soLuong) {
            throw new NghiepVuException('Chuyển giao phải bằng toàn bộ số lượng tồn kho hiện tại của lô.');
        }

        $tonKho->update([
            'toChucId' => $benNhanId,
            'ngayCapNhat' => now(),
        ]);
    }
}
