<?php

namespace App\Enums;

enum VehicleStatus: string
{
    case Available = 'available';
    case InUse = 'in_use';
    case Maintenance = 'maintenance';
    case OutOfService = 'out_of_service';
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::InUse => 'In Use',
            self::Maintenance => 'Maintenance',
            self::OutOfService => 'Out of Service',
            self::Retired => 'Retired',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Available => 'success',
            self::InUse => 'primary',
            self::Maintenance => 'warning',
            self::OutOfService => 'danger',
            self::Retired => 'secondary',
        };
    }
}
