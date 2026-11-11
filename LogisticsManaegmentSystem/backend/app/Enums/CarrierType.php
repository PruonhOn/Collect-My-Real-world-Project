<?php

namespace App\Enums;

/**
 * The transport modes a carrier sells.
 *
 * `multimodal` exists because a forwarder very often buys a door-to-door
 * product from an NVOCC without knowing which mode legs it uses, and the
 * booking has to record who was sold.
 */
enum CarrierType: string
{
    case Sea = 'sea';
    case Air = 'air';
    case Road = 'road';
    case Rail = 'rail';
    case Multimodal = 'multimodal';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
