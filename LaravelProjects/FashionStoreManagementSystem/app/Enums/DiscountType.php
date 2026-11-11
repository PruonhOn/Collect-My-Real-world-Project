<?php

namespace App\Enums;

enum DiscountType: string
{
    case None = 'none';
    case Percent = 'percent';
    case Fixed = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::None => 'No discount',
            self::Percent => 'Percentage (%)',
            self::Fixed => 'Fixed amount',
        };
    }
}
