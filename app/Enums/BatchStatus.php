<?php
namespace App\Enums;

enum BatchStatus: string
{
    case created = 'CREATED';
    case inTransit = 'IN_TRANSIT';
    case recalled = 'RECALLED';

    public function nhan(): string
    {
        return match ($this) {
            self::created => 'Đã tạo',
            self::inTransit => 'Đang lưu thông',
            self::recalled => 'Đã thu hồi',
        };
    }

    public function mauBadge(): string
    {
        return match ($this) {
            self::created => 'bg-blue-100 text-blue-800',
            self::inTransit => 'bg-yellow-100 text-yellow-800',
            self::recalled => 'bg-red-100 text-red-800',
        };
    }
}