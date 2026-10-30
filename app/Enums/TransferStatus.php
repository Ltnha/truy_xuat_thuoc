<?php
namespace App\Enums;

enum TransferStatus: string
{
    case pending = 'PENDING';
    case inTransit = 'IN_TRANSIT';
    case received = 'RECEIVED';
    case cancelled = 'CANCELLED';
    case rejected = 'REJECTED';
}