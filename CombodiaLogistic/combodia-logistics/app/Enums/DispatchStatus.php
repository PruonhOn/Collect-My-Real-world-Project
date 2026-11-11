<?php

namespace App\Enums;

enum DispatchStatus: string
{
    case Pending = 'pending';
    case Assigned = 'assigned';
    case Loaded = 'loaded';
    case InTransit = 'in_transit';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Assigned => 'Assigned',
            self::Loaded => 'Loaded',
            self::InTransit => 'In Transit',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Assigned => 'info',
            self::Loaded => 'primary',
            self::InTransit => 'primary',
            self::Delivered => 'success',
            self::Cancelled => 'secondary',
        };
    }
}
