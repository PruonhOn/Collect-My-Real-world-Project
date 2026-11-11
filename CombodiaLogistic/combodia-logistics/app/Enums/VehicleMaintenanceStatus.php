<?php

namespace App\Enums;

enum VehicleMaintenanceStatus: string
{
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Scheduled => 'info',
            self::InProgress => 'warning',
            self::Completed => 'success',
            self::Cancelled => 'secondary',
        };
    }
}
