<?php

namespace App\Models;

use App\Enums\TransferStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ChuyenGiao extends Model
{
    protected $table = 'chuyenGiao';
    public $timestamps = false;
    protected $guarded = ['id'];

    /**
     * Tự động sinh maChuyenGiao (UUID) khi khởi tạo bản ghi mới
     */
    protected static function booted(): void
    {
        static::creating(function ($chuyenGiao) {
            if (empty($chuyenGiao->maChuyenGiao)) {
                $chuyenGiao->maChuyenGiao = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'trangThai' => TransferStatus::class,
            'thoiGianKhoiTao' => 'datetime',
            'thoiGianXacNhan' => 'datetime',
        ];
    }

    public function loThuoc(): BelongsTo
    {
        return $this->belongsTo(LoThuoc::class, 'loThuocId');
    }

    public function benGui(): BelongsTo
    {
        return $this->belongsTo(ToChuc::class, 'benGuiId');
    }

    public function benNhan(): BelongsTo
    {
        return $this->belongsTo(ToChuc::class, 'benNhanId');
    }
}