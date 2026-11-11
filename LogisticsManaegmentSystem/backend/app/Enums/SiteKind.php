<?php

namespace App\Enums;

/**
 * Warehouses and factories share the `sites` table; this is what separates them.
 *
 * A factory is an origin point, a warehouse is a handling point. The distinction
 * matters for duty and bonded-goods logic, which is why it is stored rather than
 * inferred from the name.
 */
enum SiteKind: string
{
    case Warehouse = 'warehouse';
    case Factory = 'factory';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
