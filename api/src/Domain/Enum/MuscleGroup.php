<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum MuscleGroup: string
{
    case CHEST = 'CHEST';
    case BACK = 'BACK';
    case LEGS = 'LEGS';
    case SHOULDERS = 'SHOULDERS';
    case BICEPS = 'BICEPS';
    case TRICEPS = 'TRICEPS';
    case ABDOMINALS = 'ABDOMINALS';
    case CORE = 'CORE';
    case CARDIO = 'CARDIO';
    case FULL_BODY = 'FULL_BODY';
}
