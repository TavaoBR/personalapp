<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum EnrollmentStatus: string
{
    case ACTIVE = 'ACTIVE';
    case PENDING = 'PENDING';
    case EXPIRED = 'EXPIRED';
    case CANCELLED = 'CANCELLED';
}
