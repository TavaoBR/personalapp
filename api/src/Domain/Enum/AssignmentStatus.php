<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum AssignmentStatus: string
{
    case ACTIVE = 'ACTIVE';
    case ENDED = 'ENDED';
}
