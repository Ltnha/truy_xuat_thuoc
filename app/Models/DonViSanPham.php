<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DonViSanPham extends Model
{
    protected $table = 'donViSanPham';
    public $timestamps = false;
    protected $guarded = ['id'];
}