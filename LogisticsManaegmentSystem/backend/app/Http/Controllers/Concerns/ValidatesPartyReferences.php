<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\PartyType;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * Party references that have to be the right *kind* of party.
 *
 * Shipments, bookings and everything downstream of them all point at the same
 * `parties` table through a column that carries meaning - `shipper_id` means a
 * party of type shipper, not any party at all. A bare `exists:parties,id` would
 * let a module name a customer as its shipper: the id is real, the record loads,
 * and the dropdown in the UI then shows a blank because no shipper had that id.
 * The type is part of the reference's meaning, so it belongs in the check, and
 * the check is identical everywhere it is needed - which is why it lives here
 * rather than being written out per controller.
 */
trait ValidatesPartyReferences
{
    /**
     * A rule accepting only a live party of the given type.
     *
     * Soft-deleted rows are excluded alongside the type check: a deleted shipper
     * still occupies the id, and a reference to one would render as a blank
     * option for the same reason an unfiltered `exists` does.
     */
    protected function partyOfType(PartyType $type): Exists
    {
        return Rule::exists('parties', 'id')
            ->where('type', $type->value)
            ->whereNull('deleted_at');
    }
}
