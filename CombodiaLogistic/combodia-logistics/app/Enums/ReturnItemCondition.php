<?php

namespace App\Enums;

enum ReturnItemCondition: string
{
    case Good = 'good';
    case Damaged = 'damaged';
    case Defective = 'defective';

    public function label(): string
    {
        return match ($this) {
            self::Good => 'Good',
            self::Damaged => 'Damaged',
            self::Defective => 'Defective',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Good => 'success',
            self::Damaged => 'warning',
            self::Defective => 'danger',
        };
    }
}
