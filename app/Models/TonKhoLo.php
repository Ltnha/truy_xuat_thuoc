<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TonKhoLo extends Model
{
    protected $table = 'tonKhoLo';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'ngayCapNhat' => 'datetime',
        ];
    }

    public function loThuoc(): BelongsTo
    {
        return $this->belongsTo(LoThuoc::class, 'loThuocId');
    }

    public function toChuc(): BelongsTo
    {
        return $this->belongsTo(ToChuc::class, 'toChucId');
    }
}
