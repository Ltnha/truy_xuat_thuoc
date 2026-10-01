<?php
namespace App\Enums;

enum OrganizationApprovalStatus: string
{
    case choDuyet = 'CHO_DUYET';
    case daDuyet = 'DA_DUYET';
    case tuChoi = 'TU_CHOI';
    case biThuHoi = 'BI_THU_HOI';
}