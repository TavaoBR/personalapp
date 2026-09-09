<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum MembershipStatus: string
{
    case ACTIVE = 'ACTIVE';
    case INVITED = 'INVITED';
    case INACTIVE = 'INACTIVE';
}
