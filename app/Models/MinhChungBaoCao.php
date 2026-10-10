<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MinhChungBaoCao extends Model
{
    protected $table = 'minhChungBaoCao';
    public $timestamps = false;
    protected $guarded = ['id'];

    public function baoCao(): BelongsTo
    {
        return $this->belongsTo(BaoCaoNghiVan::class, 'baoCaoId');
    }
}