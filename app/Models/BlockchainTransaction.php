<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockchainTransaction extends Model
{
    protected $table = 'blockchainTransaction';
    public $timestamps = false;
    protected $guarded = ['id'];
}