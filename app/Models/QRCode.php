<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QRCode extends Model
{
    protected $table = 'qrCode';
    public $timestamps = false;
    protected $guarded = ['id'];
}