<?php

namespace App\Services;

use App\Models\NhatKyHeThong;
use Illuminate\Support\Facades\DB;

abstract class Service
{
    /**
     * Tự động thực thi đoạn code nghiệp vụ trong 1 CSDL Transaction
     */
    protected function chayNghiepVu(callable $callback): mixed
    {
        return DB::transaction(fn() => $callback());
    }

    /**
     * Ghi vết nhật ký hoạt động hệ thống vào bảng nhatKyHeThong
     */
    protected function ghiNhatKy(string $hanhDong, ?int $doiTuongId = null, string $ketQua = 'THANH_CONG', ?string $chiTiet = null): void
    {
        try {
            NhatKyHeThong::create([
                'taiKhoanId' => auth()->id(),
                'doiTuongId' => $doiTuongId,
                'hanhDong' => $hanhDong,
                'ketQua' => $ketQua,
                'thoiGian' => now(),
                'chiTiet' => $chiTiet,
            ]);
        } catch (\Throwable $e) {
            // Tránh việc lỗi ghi log làm gián đoạn luồng nghiệp vụ chính
            logger()->error('Lỗi ghi nhật ký hệ thống: ' . $e->getMessage());
        }
    }
}
