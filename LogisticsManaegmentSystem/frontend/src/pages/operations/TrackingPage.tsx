import { RequirePermission } from '@/components/guards/ProtectedRoute'
import { MasterDataPage } from '@/features/master-data/MasterDataPage'
import { SURFACES } from '@/features/master-data/registry'

/**
 * What each consignment last reported.
 *
 * A surface on the shared page, and the first one served by it that cannot be
 * created or deleted. The API grants `tracking.view` and `tracking.update` and
 * nothing else, because a tracking row is provisioned by the consignment rather
 * than by a person - so the New and Delete controls the shared page would
 * normally render are gated on permissions this user cannot hold and simply never
 * appear. Nothing in this file has to know that; the permission list is the whole
 * mechanism, which is the argument for deriving the controls from permissions
 * rather than from a per-surface flag.
 *
 * The alternative was a page of its own, and the reason that was not needed is
 * worth recording: the shared machinery had to stop assuming a record could be
 * created before it could serve one that cannot be, and it already had for
 * shipments, whose form is twenty fields. A second assumption would have been the
 * moment to notice the first.
 */
export function TrackingPage() {
  return (
    <RequirePermission permission={SURFACES.tracking.viewPermission}>
      <MasterDataPage surface={SURFACES.tracking} />
    </RequirePermission>
  )
}
