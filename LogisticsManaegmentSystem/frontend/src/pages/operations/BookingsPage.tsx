import { RequirePermission } from '@/components/guards/ProtectedRoute'
import { MasterDataPage } from '@/features/master-data/MasterDataPage'
import { SURFACES } from '@/features/master-data/registry'

/**
 * The bookings list and form.
 *
 * Three lines for the same reason `ShipmentsPage` is three lines: columns, the
 * fields, the filters, the reference dropdowns and the permissions are all
 * declared on the surface definition, so this file is the only place that knows
 * bookings is a page.
 *
 * It shares the CRUD screen rather than duplicating it, which is what the generic
 * `SurfaceDef<Booking>` bought. The surface has a status column rather than an
 * active flag, eight references, and no `is_active` anywhere - the same shape a
 * consignment has, because a booking holds the same routing and cargo.
 */
export function BookingsPage() {
  return (
    <RequirePermission permission={SURFACES.bookings.viewPermission}>
      <MasterDataPage surface={SURFACES.bookings} />
    </RequirePermission>
  )
}
