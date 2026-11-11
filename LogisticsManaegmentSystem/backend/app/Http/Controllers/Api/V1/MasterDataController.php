<?php

namespace App\Http\Controllers\Api\V1;

/**
 * Master-data resources on top of the shared CRUD behaviour.
 *
 * Lookup tables: the customers, shippers, consignees, carriers, vessels, ports,
 * warehouses, factories, trucks and drivers that other modules reference. They
 * are characterised by an `is_active` flag, which is the only thing this subclass
 * adds - the list/search/paginate/write behaviour is entirely inherited, so a
 * change to it cannot reach master data without also reaching shipments.
 *
 * Two of the ten surfaces share a table behind this one controller, and the
 * discriminator that separates them comes from the route rather than the payload.
 * That is handled in PartyController and SiteController, not here.
 */
abstract class MasterDataController extends RecordController
{
    /**
     * Every master-data entity can be deactivated, so `is_active` is a real
     * filter and a caller can list everything or narrow to live records only.
     */
    protected function hasActiveFlag(): bool
    {
        return true;
    }
}
