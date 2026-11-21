<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockchainTransaction extends Model
{
    protected $table = 'blockchainTransaction';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'thoiGian' => 'datetime',
            'lanThuCuoi' => 'datetime',
            'blockNumber' => 'integer',
            'soLanThu' => 'integer',
        ];
    }

    public function chuyenGiao(): BelongsTo
    {
        return $this->belongsTo(ChuyenGiao::class, 'chuyenGiaoId');
    }

    public function loThuoc(): BelongsTo
    {
        return $this->belongsTo(LoThuoc::class, 'loThuocId');
    }

    public function banLe(): BelongsTo
    {
        return $this->belongsTo(BanLe::class, 'banLeId');
    }

    public function chiTietBanLe(): BelongsTo
    {
        return $this->belongsTo(ChiTietBanLe::class, 'chiTietBanLeId');
    }
}
