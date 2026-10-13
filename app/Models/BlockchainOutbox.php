<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockchainOutbox extends Model
{
    protected $table = 'blockchainOutbox';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'nextAttemptAt' => 'datetime',
            'lastAttemptAt' => 'datetime',
            'confirmedAt' => 'datetime',
        ];
    }
}
