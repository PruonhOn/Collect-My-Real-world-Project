<?php

namespace App\Enums;

enum StockMovementType: string
{
    case In = 'in';
    case Out = 'out';
    case Transfer = 'transfer';
    case Adjustment = 'adjustment';
    case Return = 'return';

    public function label(): string
    {
        return match ($this) {
            self::In => 'Inbound',
            self::Out => 'Outbound',
            self::Transfer => 'Transfer',
            self::Adjustment => 'Adjustment',
            self::Return => 'Return',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::In => 'success',
            self::Out => 'danger',
            self::Transfer => 'info',
            self::Adjustment => 'warning',
            self::Return => 'primary',
        };
    }
}
