<?php
namespace App\Models;

use App\Enums\ProductApprovalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SanPham extends Model
{
    protected $table = 'sanPham';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['trangThaiDuyet' => ProductApprovalStatus::class];
    }

    public function nhaSanXuat(): BelongsTo
    {
        return $this->belongsTo(ToChuc::class, 'nhaSanXuatId');
    }

    public function loThuocs(): HasMany
    {
        return $this->hasMany(LoThuoc::class, 'sanPhamId');
    }

    public function yeuCauDangKySanPhams(): HasMany
    {
        return $this->hasMany(YeuCauDangKySanPham::class, 'sanPhamId');
    }
}