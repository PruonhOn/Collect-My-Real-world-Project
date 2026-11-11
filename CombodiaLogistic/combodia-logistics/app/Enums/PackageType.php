<?php

namespace App\Enums;

enum PackageType: string
{
    case Box = 'box';
    case Envelope = 'envelope';
    case Pallet = 'pallet';
    case Drum = 'drum';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Box => 'Box',
            self::Envelope => 'Envelope',
            self::Pallet => 'Pallet',
            self::Drum => 'Drum',
            self::Custom => 'Custom',
        };
    }
}
