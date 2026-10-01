<?php
namespace App\Enums;

enum UnitStatus: string
{
    case available = 'AVAILABLE';
    case dispensed = 'DISPENSED';
    case recalled = 'RECALLED';
}