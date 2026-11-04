<?php

namespace App\Models;

use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BaoCaoNghiVan extends Model
{
    protected $table = 'baoCaoNghiVan';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'trangThai' => ReportStatus::class,
            'ngayGui' => 'datetime',
        ];
    }

    public function loThuoc(): BelongsTo
    {
        return $this->belongsTo(LoThuoc::class, 'loThuocId');
    }

    public function taiKhoan(): BelongsTo
    {
        return $this->belongsTo(TaiKhoan::class, 'taiKhoanId');
    }

    /**
     * Alias quan hệ tới tài khoản người gửi (hỗ trợ eager loading with('nguoiGui'))
     */
    public function nguoiGui(): BelongsTo
    {
        return $this->belongsTo(TaiKhoan::class, 'taiKhoanId');
    }

    public function minhChungs(): HasMany
    {
        return $this->hasMany(MinhChungBaoCao::class, 'baoCaoId');
    }

    public function ketQuaXuLy(): HasOne
    {
        return $this->hasOne(KetQuaXuLyBaoCao::class, 'baoCaoId');
    }
}
