<?php

namespace App\Enums;

enum ReportStatus: string
{
    case daGui = 'DA_GUI';
    case dangXuLy = 'DANG_XU_LY';
    case daXuLy = 'DA_XU_LY';

        // Giữ lại các case cũ nếu có các chức năng khác đang dùng
    case pending = 'PENDING';
    case processing = 'PROCESSING';
    case approved = 'APPROVED';
    case rejected = 'REJECTED';
}
