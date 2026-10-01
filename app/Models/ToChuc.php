<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ToChuc extends Model
{
    protected $table = 'toChuc';
    public $timestamps = false;
    protected $guarded = ['id'];

    public function taiKhoans()
    {
        return $this->hasMany(TaiKhoan::class, 'toChucId');
    }
}