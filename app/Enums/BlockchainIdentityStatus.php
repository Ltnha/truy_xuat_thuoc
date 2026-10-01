<?php
namespace App\Enums;

enum BlockchainIdentityStatus: string
{
    case active = 'ACTIVE';
    case inactive = 'INACTIVE';
    case revoked = 'REVOKED';
}