<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class YeuCauDangKy extends Model
{
    protected $table = 'yeuCauDangKy';
    public $timestamps = false;
    protected $guarded = ['id'];

    public function taiLieus(): HasMany
    {
        return $this->hasMany(TaiLieuDangKy::class, 'yeuCauDangKyId');
    }
}