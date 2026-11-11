import { RequirePermission } from '@/components/guards/ProtectedRoute'
import { MasterDataPage } from '@/features/master-data/MasterDataPage'
import { SURFACES } from '@/features/master-data/registry'

/**
 * The manifest: which box is on which consignment.
 *
 * Three lines for the same reason the shipments page is three lines - the columns,
 * the nine form fields, the seven filters and the consignment dropdown are all
 * declared on the surface, so this file is the only place that knows containers is
 * a page.
 *
 * What this surface needed from the shared machinery that shipments did not is a
 * composite key. A container number is reused across consignments, so the form
 * asks for a consignment as a required field rather than inferring one, and the
 * list's `label` is the box number on its own because that is what gets quoted
 * back - with the consignment beside it as a column.
 */
export function ContainersPage() {
  return (
    <RequirePermission permission={SURFACES.containers.viewPermission}>
      <MasterDataPage surface={SURFACES.containers} />
    </RequirePermission>
  )
}
