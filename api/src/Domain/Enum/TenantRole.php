<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum TenantRole: string
{
    case OWNER = 'OWNER';
    case ADMIN = 'ADMIN';
    case MANAGER = 'MANAGER';
    case TRAINER = 'TRAINER';
    case ASSISTANT = 'ASSISTANT';
    case FINANCE = 'FINANCE';
    case STUDENT = 'STUDENT';
}
