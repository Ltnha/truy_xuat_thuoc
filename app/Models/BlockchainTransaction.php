<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockchainTransaction extends Model
{
    protected $table = 'blockchainTransaction';
    public $timestamps = false;
    protected $guarded = ['id'];

    public function chuyenGiao(): BelongsTo
    {
        return $this->belongsTo(ChuyenGiao::class, 'chuyenGiaoId');
    }

    public function loThuoc(): BelongsTo
    {
        return $this->belongsTo(LoThuoc::class, 'loThuocId');
    }
}
