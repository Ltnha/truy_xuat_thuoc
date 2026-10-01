<?php
namespace App\Enums;

enum ProductApprovalStatus: string
{
    case choDuyet = 'CHO_DUYET';
    case daDuyet = 'DA_DUYET';
    case tuChoi = 'TU_CHOI';
}