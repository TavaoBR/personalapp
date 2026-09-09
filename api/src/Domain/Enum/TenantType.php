<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum TenantType: string
{
    case PERSONAL = 'PERSONAL';
    case ASSESSORIA = 'ASSESSORIA';
}
