<?php

namespace App\Enums;

enum SessionStatus: string
{
    case Scheduled = 'scheduled';
    case Active = 'active';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::Active => 'Active',
            self::Closed => 'Closed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Scheduled => 'bg-gray-100 text-gray-800',
            self::Active => 'bg-green-100 text-green-800',
            self::Closed => 'bg-red-100 text-red-800',
        };
    }
}
