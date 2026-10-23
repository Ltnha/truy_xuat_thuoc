<?php

namespace App\Services\Blockchain;

use App\Enums\OrganizationType;

class RoleMap
{
    /**
     * Mã Băm Vai Trò chuẩn hóa khớp 100% với Smart Contract BatchTraceability.sol
     */
    public const ROLE_CO_QUAN   = '0x1d311b942596aef61ec59d06e9f164585203bbded6508df661898fbdb9984af5';
    public const ROLE_NHA_THUOC = '0xb1802fd42310626117e379b25258b1b91a10c166206dd9986fbfba9c5790c55b';
    public const ROLE_NPP       = '0xc89bc684fc08226b0536c7149feab571fab99542ae80739c2eaa9c072463d66b';
    public const ROLE_NSX       = '0x15b45b7c513f33a44d38557efbe88fa1f48c940795233c714b16de859fd7cf60';

    public static function cho(string|OrganizationType $loaiToChuc): string
    {
        $value = $loaiToChuc instanceof OrganizationType ? $loaiToChuc->value : $loaiToChuc;

        return match ($value) {
            'NHA_SAN_XUAT'  => self::ROLE_NSX,
            'NHA_PHAN_PHOI' => self::ROLE_NPP,
            'NHA_THUOC'     => self::ROLE_NHA_THUOC,
            'CO_QUAN_QUAN_LY' => self::ROLE_CO_QUAN,
            default         => throw new \InvalidArgumentException("Loại hình tổ chức không hợp lệ: {$value}"),
        };
    }
}
