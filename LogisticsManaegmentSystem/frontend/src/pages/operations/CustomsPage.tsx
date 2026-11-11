import { RequirePermission } from '@/components/guards/ProtectedRoute'
import { MasterDataPage } from '@/features/master-data/MasterDataPage'
import { SURFACES } from '@/features/master-data/registry'

/**
 * One consignment's business with one port's customs authority.
 *
 * The third operations screen on the shared page, and the one that needed the
 * shared `label` contract most: a filing has no name of its own. It may have no
 * entry number - because nothing has been filed yet, which is the state the screen
 * exists to surface - so the surface's label falls back to the consignment and port
 * and only uses the authority's reference when there is one.
 *
 * The two badges on a row are separate because the API's two questions are: a
 * filing still with the authority, and a filing somebody has to chase. A refusal is
 * the second and not the first, and merging them would make the two filters on the
 * toolbar and the rows they return describe one list.
 */
export function CustomsPage() {
  return (
    <RequirePermission permission={SURFACES.customs.viewPermission}>
      <MasterDataPage surface={SURFACES.customs} />
    </RequirePermission>
  )
}
