<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SanPham extends Model
{
    protected $table = 'sanPham';
    public $timestamps = false;
    protected $guarded = ['id'];

    /**
     * Quan hệ 1-N: Sản phẩm thuộc về một Nhà sản xuất (ToChuc)
     */
    public function nhaSanXuat(): BelongsTo
    {
        return $this->belongsTo(ToChuc::class, 'nhaSanXuatId');
    }

    /**
     * Quan hệ 1-N: Lịch sử các lần nộp hồ sơ yêu cầu (KHẮC PHỤC LỖI RelationNotFoundException)
     */
    public function yeuCauDangKySanPhams(): HasMany
    {
        return $this->hasMany(YeuCauDangKySanPham::class, 'sanPhamId');
    }

    /**
     * Quan hệ 1-N: Các lô thuốc được khởi tạo từ sản phẩm này
     */
    public function loThuocs(): HasMany
    {
        return $this->hasMany(LoThuoc::class, 'sanPhamId');
    }

    /**
     * Quan hệ 1-N: Các giao dịch xác nhận On-Chain liên quan tới sản phẩm
     */
    public function blockchainTransactions(): HasMany
    {
        return $this->hasMany(BlockchainTransaction::class, 'sanPhamId');
    }
}
