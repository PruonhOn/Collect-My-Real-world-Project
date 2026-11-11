<?php

namespace App\Enums;

enum Currency: string
{
    case USD = 'USD';
    case KHR = 'KHR';

    public function label(): string
    {
        return match ($this) {
            self::USD => 'US Dollar ($)',
            self::KHR => 'Cambodian Riel (៛)',
        };
    }

    public function symbol(): string
    {
        return match ($this) {
            self::USD => '$',
            self::KHR => '៛',
        };
    }
}
