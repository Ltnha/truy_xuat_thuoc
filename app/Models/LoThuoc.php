<?php
namespace App\Models;

use App\Enums\BatchStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoThuoc extends Model
{
    protected $table = 'loThuoc';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'trangThai' => BatchStatus::class,
            'ngaySanXuat' => 'date',
            'hanSuDung' => 'date',
        ];
    }

    public function sanPham(): BelongsTo
    {
        return $this->belongsTo(SanPham::class, 'sanPhamId');
    }

    public function toChuc(): BelongsTo
    {
        return $this->belongsTo(ToChuc::class, 'toChucId');
    }

    public function tonKhoLos(): HasMany
    {
        return $this->hasMany(TonKhoLo::class, 'loThuocId');
    }

    public function donViSanPhams(): HasMany
    {
        return $this->hasMany(DonViSanPham::class, 'loThuocId');
    }

    public function qrCodes(): HasMany
    {
        return $this->hasMany(QRCode::class, 'loThuocId');
    }

    public function chuyenGiao(): HasMany
    {
        return $this->hasMany(ChuyenGiao::class, 'loThuocId');
    }

    public function lichSus(): HasMany
    {
        return $this->hasMany(LichSuLoThuoc::class, 'loThuocId');
    }

    public function baoCaoNghiVans(): HasMany
    {
        return $this->hasMany(BaoCaoNghiVan::class, 'loThuocId');
    }

    public function daHetHan(): bool
    {
        return $this->hanSuDung->isBefore(today());
    }

    public function coThePhanPhoi(): bool
    {
        return $this->trangThai !== BatchStatus::recalled && ! $this->daHetHan();
    }
}