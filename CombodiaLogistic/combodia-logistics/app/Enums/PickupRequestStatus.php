<?php

namespace App\Enums;

enum PickupRequestStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Assigned = 'assigned';
    case PickedUp = 'picked_up';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Assigned => 'Assigned',
            self::PickedUp => 'Picked Up',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Confirmed => 'info',
            self::Assigned => 'primary',
            self::PickedUp => 'success',
            self::Cancelled => 'secondary',
        };
    }
}
