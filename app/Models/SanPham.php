<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SanPham extends Model
{
    protected $table = 'sanPham';
    public $timestamps = false;
    protected $guarded = ['id'];
}