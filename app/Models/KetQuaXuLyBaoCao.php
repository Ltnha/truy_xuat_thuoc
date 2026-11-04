<?php

namespace App\Models;

use App\Enums\DecisionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class KetQuaXuLyBaoCao extends Model
{
    protected $table = 'ketQuaXuLyBaoCao';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'quyetDinh' => DecisionType::class,
            'ngayXuLy' => 'datetime',
        ];
    }

    public function baoCao(): BelongsTo
    {
        return $this->belongsTo(BaoCaoNghiVan::class, 'baoCaoId');
    }

    public function coQuanXuLy(): BelongsTo
    {
        return $this->belongsTo(TaiKhoan::class, 'coQuanXuLyId');
    }

    public function thuHoiLoHang(): HasOne
    {
        return $this->hasOne(ThuHoiLoHang::class, 'ketQuaXuLyBaoCaoId');
    }
}
