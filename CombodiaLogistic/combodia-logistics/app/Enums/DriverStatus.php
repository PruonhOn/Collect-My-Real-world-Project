<?php

namespace App\Enums;

enum DriverStatus: string
{
    case Available = 'available';
    case Busy = 'busy';
    case OffDuty = 'off_duty';
    case Suspended = 'suspended';
    case Terminated = 'terminated';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::Busy => 'Busy',
            self::OffDuty => 'Off Duty',
            self::Suspended => 'Suspended',
            self::Terminated => 'Terminated',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Available => 'success',
            self::Busy => 'primary',
            self::OffDuty => 'secondary',
            self::Suspended => 'warning',
            self::Terminated => 'danger',
        };
    }
}
