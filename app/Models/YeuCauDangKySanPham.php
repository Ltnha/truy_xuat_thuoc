<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YeuCauDangKySanPham extends Model
{
    protected $table = 'yeuCauDangKySanPham';
    public $timestamps = false;
    protected $guarded = ['id'];

    /**
     * Ép kiểu cột thời gian để dùng được Carbon format trong Blade
     */
    protected $casts = [
        'ngayGui' => 'datetime',
        'ngayXuLy' => 'datetime',
    ];

    /**
     * Quan hệ N-1: Hồ sơ thuộc về 1 Sản phẩm
     */
    public function sanPham(): BelongsTo
    {
        return $this->belongsTo(SanPham::class, 'sanPhamId');
    }

    /**
     * Quan hệ N-1: Hồ sơ được xử lý bởi 1 Tài khoản (Cơ quan quản lý)
     */
    public function nguoiXuLy(): BelongsTo
    {
        return $this->belongsTo(TaiKhoan::class, 'nguoiXuLyId');
    }
}
