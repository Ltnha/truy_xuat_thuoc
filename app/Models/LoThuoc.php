<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoThuoc extends Model
{
    protected $table = 'loThuoc';
    public $timestamps = false;

    protected $fillable = [
        'maLo',
        'maLoNghiepVu',
        'sanPhamId',
        'toChucId',
        'soLuong',
        'ngaySanXuat',
        'hanSuDung',
        'trangThai'
    ];

    public function sanPham()
    {
        return $this->belongsTo(SanPham::class, 'sanPhamId');
    }

    public function toChuc()
    {
        return $this->belongsTo(ToChuc::class, 'toChucId');
    }

    public function blockchainTransactions()
    {
        return $this->hasMany(BlockchainTransaction::class, 'loThuocId');
    }
}