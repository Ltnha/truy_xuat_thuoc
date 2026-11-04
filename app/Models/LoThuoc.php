<?php

namespace App\Models;

use App\Enums\BatchStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LoThuoc extends Model
{
    protected $table = 'loThuoc';
    public $timestamps = false;

    protected $fillable = [
        'maLo',
        'maLoNghiepVu',
        'sanPhamId',
        'toChucId',
        'soLuong',
        'ngaySanXuat',
        'hanSuDung',
        'trangThai'
    ];

    protected function casts(): array
    {
        return [
            'trangThai' => BatchStatus::class,
            'ngaySanXuat' => 'date',
            'hanSuDung' => 'date',
        ];
    }

    /**
     * Kiểm tra lô thuốc đã hết hạn hay chưa
     */
    public function daHetHan(): bool
    {
        if (!$this->hanSuDung) {
            return false;
        }

        $hsd = $this->hanSuDung instanceof Carbon ? $this->hanSuDung : Carbon::parse($this->hanSuDung);
        return $hsd->isPast();
    }

    public function sanPham(): BelongsTo
    {
        return $this->belongsTo(SanPham::class, 'sanPhamId');
    }

    public function toChuc(): BelongsTo
    {
        return $this->belongsTo(ToChuc::class, 'toChucId');
    }

    public function nhaSanXuat(): BelongsTo
    {
        return $this->belongsTo(ToChuc::class, 'toChucId');
    }

    public function blockchainTransactions(): HasMany
    {
        return $this->hasMany(BlockchainTransaction::class, 'loThuocId');
    }

    // Dùng để hiển thị timeline chuyển giao ở trang chi tiết.
    public function chuyenGiao(): HasMany
    {
        return $this->hasMany(ChuyenGiao::class, 'loThuocId');
    }

    public function thuHoiLoHang(): HasOne
    {
        return $this->hasOne(ThuHoiLoHang::class, 'loThuocId');
    }

    public function tonKhoLo(): HasMany
    {
        return $this->hasMany(TonKhoLo::class, 'loThuocId');
    }

    public function tonKhoLos(): HasMany
    {
        return $this->hasMany(TonKhoLo::class, 'loThuocId');
    }

    public function baoCaoNghiVans(): HasMany
    {
        return $this->hasMany(BaoCaoNghiVan::class, 'loThuocId');
    }
}
