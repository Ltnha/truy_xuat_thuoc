<?php

namespace App\Enums;

enum DecisionType: string
{
    case thuHoi = 'THU_HOI';
    case tuChoi = 'TU_CHOI';
    case ghiNhan = 'GHI_NHAN';

    public function nhan(): string
    {
        return match ($this) {
            self::thuHoi => 'Thu hồi lô hàng',
            self::tuChoi => 'Từ chối báo cáo',
            self::ghiNhan => 'Ghi nhận hàng giả ngoại vi',
        };
    }
}
