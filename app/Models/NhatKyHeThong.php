<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NhatKyHeThong extends Model
{
    protected $table = 'nhatKyHeThong';
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $casts = ['thoiGian' => 'datetime'];
}