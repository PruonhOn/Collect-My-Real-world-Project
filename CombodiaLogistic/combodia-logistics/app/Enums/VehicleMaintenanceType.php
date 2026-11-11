<?php

namespace App\Enums;

enum VehicleMaintenanceType: string
{
    case Routine = 'routine';
    case Repair = 'repair';
    case Inspection = 'inspection';
    case Accident = 'accident';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Routine => 'Routine',
            self::Repair => 'Repair',
            self::Inspection => 'Inspection',
            self::Accident => 'Accident',
            self::Other => 'Other',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Routine => 'info',
            self::Repair => 'warning',
            self::Inspection => 'primary',
            self::Accident => 'danger',
            self::Other => 'secondary',
        };
    }
}
