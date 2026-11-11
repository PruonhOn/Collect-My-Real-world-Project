<?php

namespace App\Enums;

enum IncomeStatus: string
{
    case Pending = 'pending';
    case Received = 'received';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Received => 'Received',
            self::Rejected => 'Rejected',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Received => 'success',
            self::Rejected => 'danger',
        };
    }
}
