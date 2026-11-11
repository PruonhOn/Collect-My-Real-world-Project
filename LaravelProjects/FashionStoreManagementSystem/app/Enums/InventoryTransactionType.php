<?php

namespace App\Enums;

enum InventoryTransactionType: string
{
    case Purchase = 'purchase';
    case Sale = 'sale';
    case Return = 'return';
    case Adjustment = 'adjustment';
    case Damage = 'damage';

    public function direction(): string
    {
        return match ($this) {
            self::Purchase, self::Return => 'in',
            self::Sale, self::Damage => 'out',
            self::Adjustment => 'adjustment',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Purchase => 'Stock In (Purchase)',
            self::Sale => 'Sale',
            self::Return => 'Return',
            self::Adjustment => 'Adjustment',
            self::Damage => 'Damage / Loss',
        };
    }
}
