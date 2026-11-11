<?php

namespace App\Enums;

enum DriverScheduleStatus: string
{
    case Scheduled = 'scheduled';
    case Working = 'working';
    case Completed = 'completed';
    case Absent = 'absent';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::Working => 'Working',
            self::Completed => 'Completed',
            self::Absent => 'Absent',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Scheduled => 'info',
            self::Working => 'primary',
            self::Completed => 'success',
            self::Absent => 'danger',
            self::Cancelled => 'secondary',
        };
    }
}
