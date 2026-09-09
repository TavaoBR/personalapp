<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum PaymentStatus: string
{
    case PENDING = 'PENDING';
    case PAID = 'PAID';
    case OVERDUE = 'OVERDUE';
    case CANCELLED = 'CANCELLED';
    case REFUNDED = 'REFUNDED';
}
