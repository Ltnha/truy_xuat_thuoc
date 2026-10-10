<?php
namespace App\Models;

use App\Enums\UnitStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DonViSanPham extends Model
{
    protected $table = 'donViSanPham';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'trangThai' => UnitStatus::class,
            'ngayTao' => 'datetime',
            'ngayBan' => 'datetime',
        ];
    }

    public function loThuoc(): BelongsTo
    {
        return $this->belongsTo(LoThuoc::class, 'loThuocId');
    }

    public function banLe(): BelongsTo
    {
        return $this->belongsTo(BanLe::class, 'banLeId');
    }

    public function qrCode(): HasOne
    {
        return $this->hasOne(QRCode::class, 'donViSanPhamId');
    }

    public function coTheBan(): bool
    {
        return $this->trangThai === UnitStatus::available && $this->loThuoc->coThePhanPhoi();
    }
}