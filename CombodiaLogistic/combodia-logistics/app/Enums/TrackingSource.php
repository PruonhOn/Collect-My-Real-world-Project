<?php

namespace App\Enums;

enum TrackingSource: string
{
    case Manual = 'manual';
    case GpsSimulation = 'gps_simulation';
    case DriverApp = 'driver_app';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::GpsSimulation => 'GPS Simulation',
            self::DriverApp => 'Driver App',
        };
    }
}
