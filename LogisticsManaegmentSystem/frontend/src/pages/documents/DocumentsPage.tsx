import { RequirePermission } from '@/components/guards/ProtectedRoute'
import { MasterDataPage } from '@/features/master-data/MasterDataPage'
import { SURFACES } from '@/features/master-data/registry'

/**
 * The document register.
 *
 * Three lines, like every other surface page: pick the surface, hand it to the
 * shared page, gate it on the permission the API requires. Searching, paging, the
 * upload form and the download row action are all on the surface definition, and
 * `MasterDataPage` is generic over the record, so there is nothing screen-specific
 * left to write here.
 *
 * A top-level route rather than a `/master/...` one, because a document is not a
 * lookup table - it is a record with a file behind it - which is also why
 * `documents` is absent from `MASTER_SURFACE_KEYS` and from the Master Data section.
 *
 * The gate is the surface's own `viewPermission`, read rather than retyped, so the
 * screen and the API cannot disagree about who may read the register. Downloading is
 * a *different* permission, checked on the button and again by the API: being able
 * to read a bill of lading is not the same authority as being able to take a copy of
 * it.
 */
export function DocumentsPage() {
  return (
    <RequirePermission permission={SURFACES.documents.viewPermission}>
      <MasterDataPage surface={SURFACES.documents} />
    </RequirePermission>
  )
}
