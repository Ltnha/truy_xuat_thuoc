<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BanLe extends Model
{
    protected $table = 'banLe';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['ngayBan' => 'datetime', 'tongTien' => 'decimal:2'];
    }

    public function chiTietBanLes(): HasMany
    {
        return $this->hasMany(ChiTietBanLe::class, 'banLeId');
    }

    public function blockchainTransactions(): HasMany
    {
        return $this->hasMany(BlockchainTransaction::class, 'banLeId');
    }
}
