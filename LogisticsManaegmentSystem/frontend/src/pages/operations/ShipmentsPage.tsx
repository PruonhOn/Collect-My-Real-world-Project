import { RequirePermission } from '@/components/guards/ProtectedRoute'
import { MasterDataPage } from '@/features/master-data/MasterDataPage'
import { SURFACES } from '@/features/master-data/registry'

/**
 * The shipments list and form.
 *
 * Three lines, and deliberately so: everything a consignment screen needs -
 * columns, the twenty-odd form fields, the five filters, the reference dropdowns,
 * the permissions - is declared on the surface definition, so this file is the
 * only place that knows shipments is a page.
 *
 * It reuses the master-data page rather than duplicating it, and the surface is
 * a `SurfaceDef<Shipment>` rather than a `SurfaceDef<Party>`, which is the whole
 * reason the shared machinery is generic over the record type. A status column
 * instead of an active flag, ten references instead of none, and no `is_active`
 * anywhere - the page had to stop assuming that shape to take this one.
 *
 * Named `MasterDataPage` still, which is now slightly a misnomer: it is the
 * shared CRUD screen for any registry surface. Renaming it would have churned
 * eleven call sites for no behaviour change, so the rename is left for whenever
 * the next module lands.
 */
export function ShipmentsPage() {
  return (
    <RequirePermission permission={SURFACES.shipments.viewPermission}>
      <MasterDataPage surface={SURFACES.shipments} />
    </RequirePermission>
  )
}
