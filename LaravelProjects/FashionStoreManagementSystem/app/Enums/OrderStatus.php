<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Completed = 'completed';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';
    case Voided = 'voided';

    public function label(): string
    {
        return match ($this) {
            self::Completed => 'Completed',
            self::Refunded => 'Refunded',
            self::PartiallyRefunded => 'Partially Refunded',
            self::Voided => 'Voided',
        };
    }
}
