<?php
namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\Role;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
            'vaiTro' => Role::class,
            'trangThai' => AccountStatus::class,
        ];
    }

    public function getAuthPasswordName(): string
    {
        return 'matKhau';
    }

    public function toChuc(): BelongsTo
    {
        return $this->belongsTo(ToChuc::class, 'toChucId');
    }

    public function quyenHans(): BelongsToMany
    {
        return $this->belongsToMany(QuyenHan::class, 'taiKhoan_quyenHan', 'taiKhoanId', 'quyenHanId');
    }

    public function nhatKys(): HasMany
    {
        return $this->hasMany(NhatKyHeThong::class, 'taiKhoanId');
    }

    public function coQuyen(string $tenQuyen): bool
    {
        return $this->quyenHans()->where('tenQuyen', $tenQuyen)->exists();
    }
}