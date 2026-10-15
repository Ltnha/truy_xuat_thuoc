<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NhatKyHeThong extends Model
{
    use HasFactory, Prunable;

    protected $table = 'nhatKyHeThong';

    // Bảng sử dụng cột thoiGian nên tắt tự động created_at / updated_at
    public $timestamps = false;

    protected $fillable = [
        'taiKhoanId',
        'doiTuongId',
        'hanhDong',
        'ketQua',
        'thoiGian',
        'chiTiet',
    ];

    protected $casts = [
        'thoiGian' => 'datetime',
    ];

    // Người thực hiện hành động
    public function taiKhoan(): BelongsTo
    {
        return $this->belongsTo(TaiKhoan::class, 'taiKhoanId');
    }

    // Tài khoản bị tác động (ví dụ: tài khoản bị khóa/mở khóa)
    public function doiTuong(): BelongsTo
    {
        return $this->belongsTo(TaiKhoan::class, 'doiTuongId');
    }

    /**
     * CƠ CHẾ TỰ ĐỘNG XÓA (PRUNING):
     * Quét và xóa các bản ghi có `thoiGian` cũ hơn 90 ngày
     */
    public function prunable()
    {
        return static::where('thoiGian', '<=', now()->subDays(90));
    }
}
