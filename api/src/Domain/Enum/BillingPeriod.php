<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum BillingPeriod: string
{
    case MONTHLY = 'MONTHLY';
    case QUARTERLY = 'QUARTERLY';
    case SEMIANNUAL = 'SEMIANNUAL';
    case ANNUAL = 'ANNUAL';
    case CUSTOM = 'CUSTOM';
}
