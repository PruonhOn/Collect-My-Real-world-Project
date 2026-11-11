<?php

namespace App\Enums;

enum AttendanceMethod: string
{
    case Manual = 'manual';
    case Qr = 'qr';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Qr => 'QR Code',
        };
    }
}
