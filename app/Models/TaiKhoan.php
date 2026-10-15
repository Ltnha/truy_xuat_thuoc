<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\Role; // <-- Import Enum Role
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class TaiKhoan extends Authenticatable
{
    use HasFactory;

    protected $table = 'taiKhoan';

    public $timestamps = false; // Bảng dùng cột ngayTao

    protected $fillable = [
        'tenDangNhap',
        'matKhau',
        'email',
        'vaiTro',
        'trangThai',
        'lyDoKhoa',
        'nguoiKhoaId',
        'thoiGianKhoa',
        'toChucId',
        'ngayTao',
    ];

    protected $casts = [
        'vaiTro'     => Role::class,          // <-- BẮT BUỘC DÙNG Role::class (KHÔNG DÙNG OrganizationType)
        'trangThai'  => AccountStatus::class,
        'matKhau'    => 'hashed',
        'ngayTao'    => 'datetime',
        'thoiGianKhoa' => 'datetime',
    ];

    public function getAuthPassword()
    {
        return $this->matKhau;
    }

    public function toChuc(): BelongsTo
    {
        return $this->belongsTo(ToChuc::class, 'toChucId');
    }

    public function nhatKys(): HasMany
    {
        return $this->hasMany(NhatKyHeThong::class, 'taiKhoanId');
    }
}
