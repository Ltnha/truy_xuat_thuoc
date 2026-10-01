<?php

namespace App\Enums;

enum AccountStatus: string
{
    case pending = 'PENDING';
    case active = 'ACTIVE';
    case locked = 'LOCKED';
    case revoked = 'REVOKED';
}