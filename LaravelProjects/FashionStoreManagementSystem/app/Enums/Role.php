<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Cashier = 'cashier';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Manager => 'Manager',
            self::Cashier => 'Cashier / Staff',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
