<?php

namespace App\Enums;

enum DispatchDriverStatus: string
{
    case Assigned = 'assigned';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Started = 'started';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Assigned => 'Assigned',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
            self::Started => 'Started',
            self::Completed => 'Completed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Assigned => 'info',
            self::Accepted => 'success',
            self::Rejected => 'danger',
            self::Started => 'primary',
            self::Completed => 'success',
        };
    }
}
