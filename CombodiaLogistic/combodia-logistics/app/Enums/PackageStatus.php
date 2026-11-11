<?php

namespace App\Enums;

enum PackageStatus: string
{
    case Pending = 'pending';
    case Received = 'received';
    case Stored = 'stored';
    case Loaded = 'loaded';
    case InTransit = 'in_transit';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case Returned = 'returned';
    case Damaged = 'damaged';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Received => 'Received',
            self::Stored => 'Stored',
            self::Loaded => 'Loaded',
            self::InTransit => 'In Transit',
            self::OutForDelivery => 'Out for Delivery',
            self::Delivered => 'Delivered',
            self::Returned => 'Returned',
            self::Damaged => 'Damaged',
            self::Lost => 'Lost',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Received => 'info',
            self::Stored => 'info',
            self::Loaded => 'primary',
            self::InTransit => 'primary',
            self::OutForDelivery => 'success',
            self::Delivered => 'success',
            self::Returned => 'secondary',
            self::Damaged => 'danger',
            self::Lost => 'danger',
        };
    }
}
