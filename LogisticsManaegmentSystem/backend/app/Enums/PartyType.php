<?php

namespace App\Enums;

/**
 * What role a party plays in a movement.
 *
 * Backed by the string stored in `parties.type`. Adding a case here is the whole
 * change needed to introduce a fourth kind of party; the API surfaces and the
 * permission catalog are declared separately and must be updated alongside.
 */
enum PartyType: string
{
    case Customer = 'customer';
    case Shipper = 'shipper';
    case Consignee = 'consignee';

    /**
     * The `*.view` permission this type is governed by.
     *
     * Kept next to the enum so a new type cannot be added without also stating
     * who is allowed to read it.
     */
    public function permissionPrefix(): string
    {
        return $this->value;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
