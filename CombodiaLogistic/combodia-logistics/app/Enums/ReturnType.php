<?php

namespace App\Enums;

enum ReturnType: string
{
    case Pickup = 'pickup';
    case DeliveryRefused = 'delivery_refused';
    case Damaged = 'damaged';
    case WrongItem = 'wrong_item';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Pickup => 'Pickup',
            self::DeliveryRefused => 'Delivery Refused',
            self::Damaged => 'Damaged',
            self::WrongItem => 'Wrong Item',
            self::Other => 'Other',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pickup => 'info',
            self::DeliveryRefused => 'warning',
            self::Damaged => 'danger',
            self::WrongItem => 'danger',
            self::Other => 'secondary',
        };
    }
}
