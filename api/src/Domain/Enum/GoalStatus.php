<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum GoalStatus: string
{
    case IN_PROGRESS = 'IN_PROGRESS';
    case ACHIEVED = 'ACHIEVED';
    case ABANDONED = 'ABANDONED';
    case CANCELLED = 'CANCELLED';
}
