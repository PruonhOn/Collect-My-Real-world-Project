<?php

namespace App\Enums;

enum ShipmentStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case PickupScheduled = 'pickup_scheduled';
    case PickedUp = 'picked_up';
    case WarehouseReceived = 'warehouse_received';
    case InTransit = 'in_transit';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Approved => 'Approved',
            self::PickupScheduled => 'Pickup Scheduled',
            self::PickedUp => 'Picked Up',
            self::WarehouseReceived => 'Warehouse Received',
            self::InTransit => 'In Transit',
            self::OutForDelivery => 'Out for Delivery',
            self::Delivered => 'Delivered',
            self::Failed => 'Failed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'info',
            self::PickupScheduled => 'info',
            self::PickedUp => 'primary',
            self::WarehouseReceived => 'primary',
            self::InTransit => 'primary',
            self::OutForDelivery => 'success',
            self::Delivered => 'success',
            self::Failed => 'danger',
            self::Cancelled => 'secondary',
        };
    }
}
