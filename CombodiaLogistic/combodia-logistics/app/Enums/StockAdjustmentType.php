<?php

namespace App\Enums;

enum StockAdjustmentType: string
{
    case Addition = 'addition';
    case Deduction = 'deduction';
    case Correction = 'correction';

    public function label(): string
    {
        return match ($this) {
            self::Addition => 'Addition',
            self::Deduction => 'Deduction',
            self::Correction => 'Correction',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Addition => 'success',
            self::Deduction => 'danger',
            self::Correction => 'warning',
        };
    }
}
