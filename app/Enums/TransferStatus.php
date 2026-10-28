<?php
namespace App\Enums;

enum TransferStatus: string
{
    case pending = 'PENDING';
    case received = 'RECEIVED';
}