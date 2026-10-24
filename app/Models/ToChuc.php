<?php

namespace App\Models;

use App\Enums\OrganizationApprovalStatus;
use App\Enums\OrganizationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ToChuc extends Model
{
    protected $table = 'toChuc';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'loaiToChuc' => OrganizationType::class,
            'trangThaiDuyet' => OrganizationApprovalStatus::class,
            'ngayCapGiayPhep' => 'date',
            'ngayHetHanGiayPhep' => 'date',
        ];
    }

    public function taiKhoans(): HasMany
    {
        return $this->hasMany(TaiKhoan::class, 'toChucId');
    }

    public function yeuCauDangKys(): HasMany
    {
        return $this->hasMany(YeuCauDangKy::class, 'toChucId');
    }

    /**
     * Quan hệ Xuyên qua bảng yeuCauDangKy để lấy danh sách tài liệu
     */
    public function taiLieuDangKys(): HasManyThrough
    {
        return $this->hasManyThrough(
            TaiLieuDangKy::class,
            YeuCauDangKy::class,
            'toChucId',        // Khóa ngoại trên bảng yeuCauDangKy
            'yeuCauDangKyId',  // Khóa ngoại trên bảng taiLieuDangKy
            'id',              // Khóa chính trên bảng toChuc
            'id'               // Khóa chính trên bảng yeuCauDangKy
        );
    }

    public function sanPhams(): HasMany
    {
        return $this->hasMany(SanPham::class, 'nhaSanXuatId');
    }

    public function loThuocs(): HasMany
    {
        return $this->hasMany(LoThuoc::class, 'toChucId');
    }

    public function tonKhoLos(): HasMany
    {
        return $this->hasMany(TonKhoLo::class, 'toChucId');
    }

    public function chuyenGiaoDis(): HasMany
    {
        return $this->hasMany(ChuyenGiao::class, 'benGuiId');
    }

    public function chuyenGiaoDens(): HasMany
    {
        return $this->hasMany(ChuyenGiao::class, 'benNhanId');
    }

    public function blockchainIdentity(): HasOne
    {
        return $this->hasOne(BlockchainIdentity::class, 'toChucId')->latestOfMany('id');
    }

    public function blockchainIdentities(): HasMany
    {
        return $this->hasMany(BlockchainIdentity::class, 'toChucId');
    }

    public function daDuyet(): bool
    {
        return $this->trangThaiDuyet === OrganizationApprovalStatus::daDuyet;
    }
}
