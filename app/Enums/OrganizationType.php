<?php

namespace App\Enums;

enum OrganizationType: string
{
    case nhaSanXuat = 'NHA_SAN_XUAT';
    case nhaPhanPhoi = 'NHA_PHAN_PHOI';
    case nhaThuoc = 'NHA_THUOC';
    case coQuanQuanLy = 'CO_QUAN_QUAN_LY';

    public function nhan(): string
    {
        return match ($this) {
            self::nhaSanXuat => 'Nhà sản xuất',
            self::nhaPhanPhoi => 'Nhà phân phối',
            self::nhaThuoc => 'Nhà thuốc',
            self::coQuanQuanLy => 'Cơ quan quản lý',
        };
    }
}
