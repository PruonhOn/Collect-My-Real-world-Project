import { RequirePermission } from '@/components/guards/ProtectedRoute'
import { MasterDataPage } from '@/features/master-data/MasterDataPage'
import { SURFACES } from '@/features/master-data/registry'

/**
 * Booked collections and what became of them.
 *
 * A consignment can have several of these, which is the first surface on this page
 * that is genuinely one-to-many and keyed by something other than the consignment:
 * the reference is ours and unique, and the list is sorted by the booked slot
 * because a delivery list is read as a diary.
 *
 * The booked slot is the one field here declared as a `datetime` rather than a
 * date. A slot booked for 14:00 stored as midnight would read as overdue from the
 * moment it was saved, and the overdue filter is the alarm this screen exists to
 * raise - so the time has to survive the round trip rather than being truncated on
 * the way in.
 */
export function DeliveriesPage() {
  return (
    <RequirePermission permission={SURFACES.deliveries.viewPermission}>
      <MasterDataPage surface={SURFACES.deliveries} />
    </RequirePermission>
  )
}
