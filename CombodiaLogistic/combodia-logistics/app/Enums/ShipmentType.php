<?php

namespace App\Enums;

enum ShipmentType: string
{
    case Import = 'import';
    case Export = 'export';
    case Domestic = 'domestic';
    case International = 'international';

    public function label(): string
    {
        return match ($this) {
            self::Import => 'Import',
            self::Export => 'Export',
            self::Domestic => 'Domestic',
            self::International => 'International',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Import => 'info',
            self::Export => 'primary',
            self::Domestic => 'success',
            self::International => 'warning',
        };
    }
}
