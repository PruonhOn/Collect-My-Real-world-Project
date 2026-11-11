<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case Active = 'active';
    case Dropped = 'dropped';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Dropped => 'Dropped',
            self::Withdrawn => 'Withdrawn',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'bg-green-100 text-green-800',
            self::Dropped => 'bg-red-100 text-red-800',
            self::Withdrawn => 'bg-yellow-100 text-yellow-800',
        };
    }
}
