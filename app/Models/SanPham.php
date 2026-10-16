<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SanPham extends Model
{
    protected $table = 'sanPham';
    public $timestamps = false; // Bảng không có cột created_at/updated_at

    protected $fillable = [
        'maSanPham',
        'tenSanPham',
        'soDangKy',
        'thanhPhan',
        'hamLuong',
        'dangBaoChe',
        'quyCachDongGoi',
        'nhaSanXuatId',
        'trangThaiDuyet'
    ];

    public function nhaSanXuat()
    {
        return $this->belongsTo(ToChuc::class, 'nhaSanXuatId');
    }
}