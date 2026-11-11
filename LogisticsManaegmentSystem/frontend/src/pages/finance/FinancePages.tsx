import { RequirePermission } from '@/components/guards/ProtectedRoute'
import { MasterDataPage } from '@/features/master-data/MasterDataPage'
import { SURFACES } from '@/features/master-data/registry'
import type { SurfaceDef } from '@/features/master-data/registry'
import type { AnyRecord } from '@/features/master-data/types'

/**
 * The four finance screens.
 *
 * One file for all four, and the same three lines each, for the reason the
 * operations screens are also one line: everything a finance module needs -
 * columns, form fields, filters, the reference dropdowns, the permissions, the
 * invoice lifecycle buttons - is declared on its surface definition. The registry
 * has to be generic over the record type for this to typecheck, which is what
 * lets a money table and a parties table share a page without either pretending
 * the other exists.
 *
 * The helper rather than four hand-written wrappers, because a wrapper that
 * repeats itself is where a screen quietly ends up gated on the wrong permission:
 * `viewPermission` is read off the surface, so the gate and the API's own
 * authorisation cannot drift apart the way a retyped string would.
 */
function FinancePage<T extends AnyRecord>({ surface }: { surface: SurfaceDef<T> }) {
  return (
    <RequirePermission permission={surface.viewPermission}>
      <MasterDataPage surface={surface} />
    </RequirePermission>
  )
}

/** Priced work raised against a consignment. */
export function ChargesPage() {
  return <FinancePage surface={SURFACES.charges} />
}

/** What the business has asked a customer for, and what is still owing. */
export function InvoicesPage() {
  return <FinancePage surface={SURFACES.invoices} />
}

/** Money received, and credit notes going back out. */
export function PaymentsPage() {
  return <FinancePage surface={SURFACES.payments} />
}

/** What the business has paid out, and what of it is recharged. */
export function ExpensesPage() {
  return <FinancePage surface={SURFACES.expenses} />
}
