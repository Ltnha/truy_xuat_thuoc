<?php
namespace App\Enums;

enum LookupResult: string
{
    case valid = 'VALID';
    case invalid = 'INVALID';
    case recalled = 'RECALLED';
    case expired = 'EXPIRED';
    case notFound = 'NOT_FOUND';
}