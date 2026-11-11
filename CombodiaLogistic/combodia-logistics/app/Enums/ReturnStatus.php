<?php

namespace App\Enums;

enum ReturnStatus: string
{
    case Requested = 'requested';
    case Approved = 'approved';
    case InTransit = 'in_transit';
    case Received = 'received';
    case Inspected = 'inspected';
    case Completed = 'completed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Requested',
            self::Approved => 'Approved',
            self::InTransit => 'In Transit',
            self::Received => 'Received',
            self::Inspected => 'Inspected',
            self::Completed => 'Completed',
            self::Rejected => 'Rejected',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Requested => 'warning',
            self::Approved => 'info',
            self::InTransit => 'primary',
            self::Received => 'primary',
            self::Inspected => 'info',
            self::Completed => 'success',
            self::Rejected => 'danger',
        };
    }
}
