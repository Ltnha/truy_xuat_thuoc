<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockchainTransaction extends Model
{
    protected $table = 'blockchainTransaction';
    public $timestamps = false;

    protected $fillable = [
        'txHash',
        'blockNumber',
        'eventName',
        'doiTuongToChucId',
        'businessId',
        'thoiGian',
        'network',
        'trangThai',
        'toChucId',
        'sanPhamId',
        'loThuocId',
        'chuyenGiaoId',
        'donViSanPhamId',
        'banLeId'
    ];
}