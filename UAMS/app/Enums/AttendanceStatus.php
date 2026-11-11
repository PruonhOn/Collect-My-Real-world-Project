<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Absent = 'absent';
    case Late = 'late';
    case Excused = 'excused';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Present',
            self::Absent => 'Absent',
            self::Late => 'Late',
            self::Excused => 'Excused',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Present => 'bg-green-100 text-green-800',
            self::Absent => 'bg-red-100 text-red-800',
            self::Late => 'bg-yellow-100 text-yellow-800',
            self::Excused => 'bg-blue-100 text-blue-800',
        };
    }
}
