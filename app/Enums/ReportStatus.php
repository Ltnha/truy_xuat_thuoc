<?php
namespace App\Enums;

enum ReportStatus: string
{
    case pending = 'PENDING';
    case processing = 'PROCESSING';
    case approved = 'APPROVED';
    case rejected = 'REJECTED';
}