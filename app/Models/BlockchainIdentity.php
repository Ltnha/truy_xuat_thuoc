<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockchainIdentity extends Model
{
    protected $table = 'blockchainIdentity';
    public $timestamps = false;
    protected $guarded = ['id'];
}