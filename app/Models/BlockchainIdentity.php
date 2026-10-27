<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockchainIdentity extends Model
{
    protected $table = 'blockchainIdentity';

    public $timestamps = false;

    protected $guarded = ['id'];

    /**
     * Mối quan hệ: Ví Blockchain thuộc về một Tổ chức
     */
    public function toChuc(): BelongsTo
    {
        return $this->belongsTo(ToChuc::class, 'toChucId', 'id');
    }
}
