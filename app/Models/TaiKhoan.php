<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class TaiKhoan extends Authenticatable
{
    use Notifiable;

    protected $table = 'taiKhoan';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected $hidden = ['matKhau'];

    protected function casts(): array
    {
        return [
            'matKhau' => 'hashed',
            'ngayTao' => 'datetime',
        ];
    }

    public function getAuthPasswordName(): string
    {
        return 'matKhau';
    }

    public function toChuc()
    {
        return $this->belongsTo(ToChuc::class, 'toChucId');
    }
}