<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChiTietBanLe extends Model
{
    protected $table = 'chiTietBanLe';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'soLuong' => 'integer',
            'donGia' => 'decimal:2',
            'thanhTien' => 'decimal:2',
        ];
    }

    public function banLe(): BelongsTo
    {
        return $this->belongsTo(BanLe::class, 'banLeId');
    }

    public function loThuoc(): BelongsTo
    {
        return $this->belongsTo(LoThuoc::class, 'loThuocId');
    }
}
