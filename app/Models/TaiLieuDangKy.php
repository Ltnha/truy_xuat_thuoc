<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaiLieuDangKy extends Model
{
    protected $table = 'taiLieuDangKy';
    public $timestamps = false;
    protected $guarded = ['id'];

    public function yeuCauDangKy(): BelongsTo
    {
        return $this->belongsTo(YeuCauDangKy::class, 'yeuCauDangKyId');
    }
}