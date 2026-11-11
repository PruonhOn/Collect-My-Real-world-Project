import { Ban, Download, Send } from 'lucide-react'
import type { ReactNode } from 'react'
import { Badge } from '@/components/ui/Badge'
import { CellStack } from '@/components/ui/Table'
import {
  formatDate,
  formatDateTime,
  formatMoney,
  formatNumber,
  formatRelativeTime,
  humanise,
} from '@/lib/format'
import type { ListQuery } from '@/types/api'
import { LIVE_REFRESH } from '@/lib/realtime'
import {
  ActiveBadge,
  AddressCell,
  BookingStatusBadge,
  ChargeStatusBadge,
  ContactCell,
  ContainerStatusBadge,
  CustomsStatusBadge,
  DeliveryStatusBadge,
  DocumentExpiryCell,
  DocumentFileCell,
  DueDateCell,
  ExpiryCell,
  ExpenseCategoryBadge,
  InvoiceStatusBadge,
  MoneyCell,
  PaymentAmountCell,
  PaymentMethodBadge,
  RelationCell,
  ShipmentStatusBadge,
  Text,
  Unassigned,
} from '@/features/master-data/cells'
import type {
  AnyRecord,
  Booking,
  Carrier,
  Charge,
  Container,
  CustomsClearance,
  Delivery,
  Document,
  Driver,
  Expense,
  Invoice,
  MasterSurfaceKey,
  Party,
  Payment,
  Port,
  RecordOf,
  ReferenceSurfaceKey,
  Shipment,
  ShipmentTracking,
  Site,
  SurfaceKey,
  Timestamped,
  Truck,
  Vessel,
} from '@/features/master-data/types'

/**
 * The screens served by the shared list-and-form page, described as data.
 *
 * The backend already collapses these into one controller with per-entity
 * extension points. Writing a React page per module against a near-identical API
 * shape would undo that: a fix to filtering or pagination would have to be
 * applied once per module and one of them would be missed. So there is one page
 * and one form, and everything that genuinely differs between modules - the
 * columns, the fields, the filters, the permissions - is declared below.
 *
 * This is also not a list of master-data screens. `shipments` is registered here
 * because it is served by the same page, and the reason the base record shape is
 * `AnyRecord` rather than something carrying `is_active`: a consignment has a
 * status, not a flag. See `MasterSurfaceKey` for the ten that are lookup tables.
 *
 * Four conventions are worth knowing before editing:
 *
 * - A field's `name` is the *request* key, which is flat. The API presents the
 *   address nested but accepts it flat, so `address_line_1` on a form is not a
 *   mistake.
 * - A column's `sortKey` is only set when the module's controller whitelists
 *   that column. Anything else sorts as a no-op on the server, which looks like
 *   a broken header rather than an ignored one, so they stay un-clickable.
 * - The registry is generic in the record type, so `ports` columns are checked
 *   against `Port` and `vessels` columns against `Vessel`. A column reaching for
 *   a field the wrong surface does not have is a build error, not a blank cell.
 * - A field holding a relation id names it in `relationFields`, so the form can
 *   read it back out of the record the API returns.
 */


export type { SurfaceKey }

/* -------------------------------------------------------------------------- */
/* Field and column shapes                                                     */
/* -------------------------------------------------------------------------- */

export type FieldKind =
  | 'text'
  | 'email'
  | 'tel'
  | 'number'
  | 'decimal'
  | 'date'
  | 'datetime'
  | 'select'
  | 'checkbox'
  | 'textarea'
  | 'file'

export interface FieldOption {
  value: string
  label: string
}

/**
 * A select whose options come from another module.
 *
 * A discriminated union on `surface` rather than a single widened type, so the
 * form can narrow to the referenced surface and call `label` with a `Carrier` or
 * a `Truck` without a cast. The alternative - typing the label against every
 * record type at once - is unsatisfiable, because the function is contravariant
 * in its argument.
 *
 * Constrained to `ReferenceSurfaceKey`, which now covers the consignment as well
 * as lookup data and bookings. A consignment points at the booking it came from and
 * the three operations modules point back at the consignment they belong to, which
 * is a real relationship in both directions; what stays impossible is a
 * consignment naming another consignment, and nothing here offers it.
 */
export interface ReferenceDefOf<K extends ReferenceSurfaceKey> {
  surface: K
  label: (record: RecordOf<K>) => string
  /**
   * Defaults to live records only. The API's `exists` rule for `carrier_id` and
   * `truck_id` requires `is_active`, so offering an inactive record would
   * guarantee a 422 on submit.
   */
  query?: ListQuery
}

/**
 * A reference whose options are both kinds of site.
 *
 * Shipments' origin and destination are the one place a single dropdown has to
 * span two surfaces. The API validates them against `sites` with no `kind`
 * filter, so a consignment may leave a factory as readily as a warehouse, and a
 * reference pinned to one surface would have to hide half the valid answers - a
 * dropdown that silently could not show a factory, on the one field where picking
 * the wrong kind is impossible to spot afterwards.
 *
 * Its own type rather than a `surfaces: K[]` array, because a hook cannot be
 * called once per array element. Two named surfaces mean two calls written out,
 * which is also all the generality this needs: warehouses and factories are the
 * only pair that shares a table and is genuinely interchangeable. The requests
 * are keyed and cached, so a form with two site fields costs one extra fetch.
 */
export interface SiteReferenceDef {
  surfaces: readonly ['warehouses', 'factories']
  label: (record: Site) => string
  query?: ListQuery
}

/** A reference that reads from exactly one surface. */
export type SingleReferenceDef = {
  [K in ReferenceSurfaceKey]: ReferenceDefOf<K>
}[ReferenceSurfaceKey]

/** Every module a field may reference. */
export type ReferenceDef = SingleReferenceDef | SiteReferenceDef


export interface FieldDef {
  name: string
  label: string
  kind: FieldKind
  required?: boolean
  placeholder?: string
  hint?: string
  /** Spans the full width of the two-column form grid. */
  wide?: boolean
  options?: FieldOption[]
  reference?: ReferenceDef
  min?: number
  max?: number
  maxLength?: number
  step?: string
  /**
   * The `accept` list for a `file` field.
   *
   * Declared here rather than read from a global so the surface's contract is in
   * one place: a document register accepts PDF and images, a future attachments
   * screen might not, and a filter that silently differed from what the API
   * accepts would reject the file after the user had chosen it - the browser's
   * `accept` is a hint for the picker, never a validation, so the two have to be
   * written in the same place to be comparable.
   */
  accept?: string
}

export interface ColumnDef<T extends AnyRecord> {
  key: string
  header: string
  sortKey?: string
  align?: 'left' | 'right' | 'center'
  render: (record: T) => ReactNode
}

export interface FilterDef {
  name: string
  label: string
  kind: 'text' | 'select'
  placeholder?: string
  options?: FieldOption[]
  reference?: ReferenceDef
}

/**
 * An action on one row, beyond edit and delete.
 *
 * Declarative on purpose: the page owns the mutation, the cache invalidation and
 * the confirmation, so a surface contributes *which* actions a row has and *when*
 * they apply, and nothing about how a request is made. A surface that needed to
 * run its own fetch would be the one surface whose errors, toasts and refetch
 * behaviour could drift from every other.
 *
 * Invoice lifecycle is what needs this. `issue` and `void` are POSTs to their own
 * endpoints rather than a PATCH, so a form that sets `status` cannot express them:
 * sending a bill is an act with a zero-total check attached, and a form field is
 * the wrong shape for that.
 */
export interface RowActionDef {
  /** Stable key, and the button's test id stem. */
  key: string
  label: string
  icon: ReactNode
  /** Required permission. Checked against the viewer's own grants, not inferred. */
  permission: string
  /**
   * A sentence for the confirmation dialog. Absent means the action fires
   * immediately, which is the right default for the reversible and the wrong one
   * for anything commercial.
   */
  confirm?: string
  /** The button's label while the confirmation is open. Defaults to the label. */
  confirmLabel?: string
  /** Danger tone: not for delete, but for a decision that cannot be undone. */
  destructive?: boolean
  /** Path under the record, so `issue` becomes `/invoices/12/issue`. */
  endpoint?: string
  /**
   * Past tense for the success toast, e.g. `issued`.
   *
   * Declared rather than derived from the label: turning "Issue invoice" into
   * "Invoiced" is a rule that is wrong the moment a third action exists.
   */
  successLabel?: string
  /**
   * A direct, precomputed URL to follow. Mutually exclusive with `endpoint` in
   * intent: lifecycle actions are a POST to a nested endpoint, whereas a download
   * is a GET to a path the API already published.
   */
  href?: string
  /** Open the link in a new tab. Defaults to `false`, so a POST stays in-app. */
  external?: boolean
}

export interface SurfaceDef<T extends AnyRecord> {

  key: SurfaceKey
  /** API path relative to the base URL. */
  path: string
  title: string
  singular: string
  /** Shown under the page title. */
  description: string
  /**
   * What the API requires to read this surface.
   *
   * Deliberately the broad permission, not the surface's own: an operator
   * reading a shipment has to see who sent it, so `routes/api.php` authorises
   * reading any party with `customer.view` and any site with `warehouse.view`.
   * Gating the page on `shipper.view` instead would hide a screen the API would
   * happily serve.
   */
  viewPermission: string
  /**
   * Namespace for the write permissions, so `.create`/`.update`/`.delete` are
   * derived rather than restated. Writes stay per-surface even where reads are
   * shared: who may rewrite a customer's terms is not who may rewrite a
   * consignee's delivery address.
   */
  writeNamespace: string
  columns: ColumnDef<T>[]
  fields: FieldDef[]
  filters: FilterDef[]
  searchPlaceholder: string
  /** A column the module whitelists, so the initial order is not just `id`. */
  defaultSort: string
  /**
   * How a record is named in prose: the delete confirmation, the toast, the
   * table's accessible row name.
   */
  label: (record: T) => string
  /**
   * Labels for a record's relations, keyed by the field that writes them.
   *
   * Only needed where a field holds an id: the option list for a reference is
   * fetched, and a relation that is no longer in that list would otherwise
   * render as blank. Surfaces without relation fields return nothing.
   */
  relationLabels: (record: T) => Record<string, string | undefined>
  /**
   * Which request key each relation is written under: `{ carrier: 'carrier_id' }`.
   *
   * The API returns a relation as an object and accepts an id, so opening the
   * edit form for a truck has to turn `carrier: { id, name, code }` back into
   * `carrier_id`. That mapping used to be hardcoded for carrier and truck, which
   * stopped being true the moment a surface had a third reference - and a
   * shipment has nine. Declaring it here keeps the form free of a list of
   * relation names that would have to be edited in two places.
   *
   * Only the named relations are lifted, so a relation the surface has no field
   * for cannot leak into a payload.
   */
  relationFields?: Record<string, string>
  /**
   * How often this list re-fetches while it is open, in milliseconds.
   *
   * Set only where the rows change while somebody is looking at them: a
   * consignment's last report, or a worklist another desk edits. Absent means
   * the list holds the shared staleTime and never re-fetches on its own.
   */
  refetchInterval?: number
  /**
   * Per-row actions for records this module can act on, evaluated per record.
   *
   * Returning them from a function rather than declaring a fixed list is what
   * makes a lifecycle conditional: "Issue" is only meaningful on a draft, and a
   * button that appears on a paid invoice and returns 409 is worse than no
   * button. A surface with no lifecycle omits this entirely.
   */
  rowActions?: (record: T) => RowActionDef[]
  /**
   * Whether a record accepts a PATCH or a DELETE at all.
   *
   * The server is the authority, and its `is_editable` flag is what this reads:
   * an issued invoice is refused by both, so offering Edit and Delete on a bill the
   * customer is already holding is a dead end the user discovers only after
   * filling the form in. One predicate rather than two because a record that cannot
   * be edited cannot usefully be deleted either - deleting an invoice is a delete of
   * a draft.
   */
  canModify?: (record: T) => boolean
}


/** Derives the write permissions for a surface from its namespace. */
export function writePermissions(surface: SurfaceDef<never>) {
  return {
    create: `${surface.writeNamespace}.create`,
    update: `${surface.writeNamespace}.update`,
    delete: `${surface.writeNamespace}.delete`,
  }
}

/**
 * Every master-data surface ends its table with the same status column.
 *
 * Constrained to `Timestamped` rather than the shared `AnyRecord`, because it
 * reads `is_active` - a column only the lookup tables have. A surface with a
 * lifecycle instead declares its own status column, as shipments does.
 */
const activeColumn = <T extends Timestamped>(): ColumnDef<T> => ({
  key: 'is_active',
  header: 'Status',
  render: (record) => <ActiveBadge active={record.is_active} />,
})


/* -------------------------------------------------------------------------- */
/* Shared field groups                                                         */
/* -------------------------------------------------------------------------- */

const CONTACT_FIELDS: FieldDef[] = [
  { name: 'contact_name', label: 'Contact name', kind: 'text', maxLength: 255 },
  { name: 'contact_email', label: 'Contact email', kind: 'email', maxLength: 255 },
  { name: 'contact_phone', label: 'Contact phone', kind: 'tel', maxLength: 50 },
]

const ADDRESS_FIELDS: FieldDef[] = [
  { name: 'address_line_1', label: 'Address line 1', kind: 'text', maxLength: 255 },
  { name: 'address_line_2', label: 'Address line 2', kind: 'text', maxLength: 255 },
  { name: 'city', label: 'City', kind: 'text', maxLength: 120 },
  { name: 'state', label: 'State / province', kind: 'text', maxLength: 120 },
  { name: 'postal_code', label: 'Postal code', kind: 'text', maxLength: 30 },
  { name: 'country_code', label: 'Country code', kind: 'text', maxLength: 2, hint: 'ISO 3166-1 alpha-2, e.g. AE' },
]

const NOTES_FIELD: FieldDef = {
  name: 'notes',
  label: 'Notes',
  kind: 'textarea',
  wide: true,
  maxLength: 2000,
  placeholder: 'Anything the team should know about this record.',
}

const ACTIVE_FIELD: FieldDef = { name: 'is_active', label: 'Active', kind: 'checkbox' }

const ACTIVE_FILTER: FilterDef = {
  name: 'is_active',
  label: 'Status',
  kind: 'select',
  options: [
    { value: '', label: 'All statuses' },
    { value: '1', label: 'Active only' },
    { value: '0', label: 'Inactive only' },
  ],
}

const COUNTRY_FILTER: FilterDef = { name: 'country_code', label: 'Country', kind: 'text', placeholder: 'AE' }

const CARRIER_MODE_OPTIONS: FieldOption[] = [
  { value: 'sea', label: 'Sea' },
  { value: 'air', label: 'Air' },
  { value: 'road', label: 'Road' },
  { value: 'rail', label: 'Rail' },
  { value: 'multimodal', label: 'Multimodal' },
]

const CARRIER_REFERENCE: ReferenceDefOf<'carriers'> = {
  surface: 'carriers',
  label: (record) => `${record.name} (${record.code})`,
  query: { per_page: 100, is_active: 1, sort: 'name', direction: 'asc' },
}

const TRUCK_REFERENCE: ReferenceDefOf<'trucks'> = {
  surface: 'trucks',
  label: (record) => record.plate_number,
  query: { per_page: 100, is_active: 1, sort: 'plate_number', direction: 'asc' },
}

const DRIVER_REFERENCE: ReferenceDefOf<'drivers'> = {
  surface: 'drivers',
  label: (record) => `${record.name} (${record.code})`,
  query: { per_page: 100, is_active: 1, sort: 'name', direction: 'asc' },
}

const VESSEL_REFERENCE: ReferenceDefOf<'vessels'> = {
  surface: 'vessels',
  label: (record) => `${record.name} (IMO ${record.imo_number})`,
  query: { per_page: 100, is_active: 1, sort: 'name', direction: 'asc' },
}

const PORT_REFERENCE: ReferenceDefOf<'ports'> = {
  surface: 'ports',
  label: (record) => `${record.un_locode} — ${record.name}`,
  query: { per_page: 100, is_active: 1, sort: 'name', direction: 'asc' },
}

/**
 * A party reference for one specific party surface.
 *
 * Generic over the surface rather than taking a merged party list, because the
 * three surfaces are separate APIs and the API's `exists` rule for `shipper_id`
 * requires `type = shipper`. Offering customers here would guarantee a 422 on
 * submit for every selection.
 */
function partyReference<K extends 'shippers' | 'consignees'>(surface: K): ReferenceDefOf<K> {
  return {
    surface,
    label: (record) => `${record.name} (${record.code})`,
    query: { per_page: 100, is_active: 1, sort: 'name', direction: 'asc' },
  }
}

/**
 * A site reference, for a shipment's origin and destination legs.
 *
 * One definition for both fields, and it spans both site surfaces on purpose -
 * see `SiteReferenceDef`.
 */
const SITE_REFERENCE: SiteReferenceDef = {
  surfaces: ['warehouses', 'factories'],
  label: (record) => `${record.name} (${record.code})`,
  query: { per_page: 100, is_active: 1, sort: 'name', direction: 'asc' },
}

const SHIPMENT_STATUS_OPTIONS: FieldOption[] = [
  { value: 'draft', label: 'Draft' },
  { value: 'booked', label: 'Booked' },
  { value: 'in_transit', label: 'In transit' },
  { value: 'customs', label: 'At customs' },
  { value: 'arrived', label: 'Arrived' },
  { value: 'delivered', label: 'Delivered' },
  { value: 'cancelled', label: 'Cancelled' },
]

const BOOKING_STATUS_OPTIONS: FieldOption[] = [
  { value: 'draft', label: 'Draft' },
  { value: 'requested', label: 'Requested' },
  { value: 'confirmed', label: 'Confirmed' },
  { value: 'cancelled', label: 'Cancelled' },
  { value: 'expired', label: 'Expired' },
]

/**
 * The milestones a carrier can report, in the order the cargo meets them.
 *
 * Ten options, matching `TrackingMilestone`. The wording is the API's, not
 * `humanise`'s: a client-side prettifier would have to be kept in step with the
 * enum, and the label an operator reads is the one the server decided on.
 *
 * Listed in journey order rather than alphabetically because the dropdown is read
 * as a sequence - "where can it be by now" - and `exception` sits at the end where
 * it belongs, since it is not a point on the journey.
 */
const TRACKING_MILESTONE_OPTIONS: FieldOption[] = [
  { value: 'booked', label: 'Booked' },
  { value: 'gate_in', label: 'Gate in' },
  { value: 'departed', label: 'Departed' },
  { value: 'transshipment', label: 'Transshipment' },
  { value: 'arrived', label: 'Arrived' },
  { value: 'customs_hold', label: 'Customs hold' },
  { value: 'customs_cleared', label: 'Customs cleared' },
  { value: 'out_for_delivery', label: 'Out for delivery' },
  { value: 'delivered', label: 'Delivered' },
  { value: 'exception', label: 'Exception' },
]

/**
 * A shipment's link to the booking it was raised from.
 *
 * The one reference that points at a transactional module rather than lookup
 * data, and the reason `ReferenceSurfaceKey` exists. No `is_active` in the query:
 * bookings have no such column, and asking for live records only would be a filter
 * the API quietly ignores. Every booking is offered, closed ones included,
 * because a consignment is routinely raised against a request that has since been
 * cancelled and the operator still has to be able to find it.
 */
const BOOKING_REFERENCE: ReferenceDefOf<'bookings'> = {
  surface: 'bookings',
  label: (record) => `${record.reference} — ${record.status_label}`,
  query: { per_page: 100, sort: 'reference', direction: 'desc' },
}

/**
 * A consignment reference, for the three operations modules.
 *
 * The one reference that points at a consignment, and the only thing that changed
 * about `ReferenceSurfaceKey` when these modules landed. Every consignment is
 * offered, closed ones included, and that is not an oversight: a delivery is
 * recorded against a consignment that may already be marked delivered - the two are
 * updated by different people, and hiding closed consignments would make the row
 * that exists precisely to report that disagreement impossible to write.
 *
 * No `is_active` in the query, for the same reason `BOOKING_REFERENCE` has none: a
 * consignment has a status, not a flag, so the filter would be one the API ignores.
 */
const SHIPMENT_REFERENCE: ReferenceDefOf<'shipments'> = {
  surface: 'shipments',
  label: (record) => `${record.reference} — ${record.status_label}`,
  query: { per_page: 100, sort: 'reference', direction: 'desc' },
}

const INCOTERM_OPTIONS: FieldOption[] = [
  { value: 'EXW', label: 'EXW — Ex Works' },
  { value: 'FCA', label: 'FCA — Free Carrier' },
  { value: 'FAS', label: 'FAS — Free Alongside Ship' },
  { value: 'FOB', label: 'FOB — Free On Board' },
  { value: 'CIF', label: 'CIF — Cost, Insurance and Freight' },
  { value: 'CFR', label: 'CFR — Cost and Freight' },
  { value: 'CIP', label: 'CIP — Carriage and Insurance Paid To' },
  { value: 'CPT', label: 'CPT — Carriage Paid To' },
  { value: 'DAP', label: 'DAP — Delivered at Place' },
  { value: 'DPU', label: 'DPU — Delivered at Terminal' },
  { value: 'DDP', label: 'DDP — Delivered Duty Paid' },
]


/**
 * The three operations vocabularies, in the order each thing meets them.
 *
 * Seven, six and six options, matching `ContainerStatus`, `CustomsStatus` and
 * `DeliveryStatus`. The wording is the API's own `label()` output, for the same
 * reason the tracking milestones are: the option list and the badge on a row are
 * then the same string by construction rather than by two translations agreeing.
 *
 * Listed in the order the thing happens rather than alphabetically, because a
 * dropdown of statuses is read as a sequence - "where can it be by now" - and the
 * states that end a record sit last in each, where they belong.
 */
const CONTAINER_STATUS_OPTIONS: FieldOption[] = [
  { value: 'empty', label: 'Empty' },
  { value: 'loaded', label: 'Loaded' },
  { value: 'in_transit', label: 'In transit' },
  { value: 'customs_hold', label: 'Customs hold' },
  { value: 'released', label: 'Released' },
  { value: 'delivered', label: 'Delivered' },
  { value: 'damaged', label: 'Damaged' },
]

const CUSTOMS_STATUS_OPTIONS: FieldOption[] = [
  { value: 'pending', label: 'Pending' },
  { value: 'submitted', label: 'Submitted' },
  { value: 'under_review', label: 'Under review' },
  { value: 'approved', label: 'Approved' },
  { value: 'rejected', label: 'Rejected' },
  { value: 'completed', label: 'Completed' },
]

const DELIVERY_STATUS_OPTIONS: FieldOption[] = [
  { value: 'scheduled', label: 'Scheduled' },
  { value: 'picking_up', label: 'Picking up' },
  { value: 'in_transit', label: 'In transit' },
  { value: 'delivered', label: 'Delivered' },
  { value: 'failed', label: 'Failed' },
  { value: 'cancelled', label: 'Cancelled' },
]

/**
 * The consignment filter, shared by all three operations modules.
 *
 * Every one of these records is read by asking whose it is, and the consignment is
 * the first question in all three cases. Declared once so the three screens cannot
 * drift into offering the same dropdown with different wording.
 */
const SHIPMENT_FILTER: FilterDef = {
  name: 'shipment_id',
  label: 'Consignment',
  kind: 'select',
  reference: SHIPMENT_REFERENCE,
}

const CARRIER_FILTER: FilterDef = {
  name: 'carrier_id',
  label: 'Carrier',
  kind: 'select',
  reference: CARRIER_REFERENCE,
}

/* -------------------------------------------------------------------------- */
/* Document vocabulary                                                         */
/* -------------------------------------------------------------------------- */

/**
 * The kinds of paperwork, in the order a consignment meets them.
 *
 * Cargo documents first because that is how the list is read on a shipment, then
 * the certificates the business holds for itself, then the two that sit either side
 * of the border. `other` last because it is the admission that something arrived
 * that does not fit - and a document register that cannot hold a scan nobody could
 * classify is a register people stop using.
 */
const DOCUMENT_TYPE_OPTIONS: FieldOption[] = [
  { value: 'bill_of_lading', label: 'Bill of lading' },
  { value: 'commercial_invoice', label: 'Commercial invoice' },
  { value: 'packing_list', label: 'Packing list' },
  { value: 'certificate_of_origin', label: 'Certificate of origin' },
  { value: 'insurance_certificate', label: 'Insurance certificate' },
  { value: 'customs_declaration', label: 'Customs declaration' },
  { value: 'delivery_order', label: 'Delivery order' },
  { value: 'other', label: 'Other' },
]

/**
 * The types the API will accept, as the browser's own `accept` list.
 *
 * Mirrors `logistics.documents.allowed_mime_types`. The browser treats `accept` as
 * a hint for its file picker and never as validation, so a file chosen any other
 * way still has to be refused by the API - which is the point of pinning the list
 * here: it makes the two comparable in one file rather than leaving the contract
 * to be matched by eye against a PHP config array.
 */
const DOCUMENT_ACCEPT = [
  '.pdf',
  'image/jpeg',
  'image/png',
  'image/webp',
  '.doc',
  '.docx',
  '.xls',
  '.xlsx',
].join(',')

/* -------------------------------------------------------------------------- */
/* Finance references                                                          */
/* -------------------------------------------------------------------------- */

/**
 * A customer, for the two modules that bill one.
 *
 * `is_active: 1` because `InvoiceController`'s `exists` rule on `customer_id`
 * requires a live party, so offering an archived one would guarantee a 422 on
 * submit. Read off the `customers` surface rather than `shippers`, which is the
 * same table: the API filters it to `type = customer` itself, and a reference
 * pointed at the wrong scope would let a shipper be chosen for an invoice.
 */
const CUSTOMER_REFERENCE: ReferenceDefOf<'customers'> = {
  surface: 'customers',
  label: (record) => `${record.name} (${record.code})`,
  query: { per_page: 100, is_active: 1, sort: 'name', direction: 'asc' },
}

/**
 * An invoice, for choosing the bill a line or a receipt belongs to.
 *
 * Every invoice is offered, drafts and voids included, and that is deliberate
 * rather than an oversight. A void invoice's lines have to remain attachable to it
 * as history, and a receipt against a draft is refused with a 409 that says why -
 * which is a better answer than an invoice missing from the dropdown, on a screen
 * where its absence would look like the bill does not exist.
 *
 * The label carries the balance, because the question this dropdown answers is
 * "which bill is still open" and a list of numbers with no reference beside them
 * cannot be read that way.
 */
const INVOICE_REFERENCE: ReferenceDefOf<'invoices'> = {
  surface: 'invoices',
  label: (record) => `${record.reference} — ${record.status_label ?? humanise(record.status)}`,
  query: { per_page: 100, sort: 'reference', direction: 'desc' },
}

/* -------------------------------------------------------------------------- */
/* Finance vocabularies                                                         */
/* -------------------------------------------------------------------------- */

/**
 * What a line is for, in the order a consignment meets them.
 *
 * The wording is the API's own `label()` output, for the same reason the
 * operations vocabularies use theirs: the option in the dropdown and the badge on
 * the row are then the same string by construction rather than by two
 * translations agreeing.
 */
const CHARGE_TYPE_OPTIONS: FieldOption[] = [
  { value: 'freight', label: 'Freight' },
  { value: 'handling', label: 'Handling' },
  { value: 'documentation', label: 'Documentation' },
  { value: 'customs', label: 'Customs' },
  { value: 'storage', label: 'Storage' },
  { value: 'insurance', label: 'Insurance' },
  { value: 'fuel', label: 'Fuel' },
  { value: 'demurrage', label: 'Demurrage' },
  { value: 'detention', label: 'Detention' },
  { value: 'other', label: 'Other' },
]

/**
 * A charge's status, minus the states a charge cannot reach.
 *
 * `partially_paid` and `paid` are absent because a charge is a fraction of a bill
 * and says nothing about whether the bill is settled - offering them here would
 * promise a status the API refuses. `issued` is not offered either: it is derived
 * from whether the line is on an invoice, so a person sets the invoice and the
 * line follows. `void` is the only one, because withdrawing a line is a decision
 * about that line.
 */
const CHARGE_STATUS_OPTIONS: FieldOption[] = [
  { value: '', label: 'Any status' },
  { value: 'draft', label: 'Draft' },
  { value: 'issued', label: 'Issued' },
  { value: 'void', label: 'Void' },
]

/**
 * An invoice's status, with the two derived states marked as such.
 *
 * The filter dropdown offers all five because they are five genuinely different
 * sets of rows - and the two that are the point of the screen, `overdue` and
 * `outstanding`, are aliases the API answers through the same parameter rather
 * than statuses, so they sit in the same list where an operator will look for
 * them. A dropdown that omitted them would send people to the dashboard for a
 * list the invoice screen already has.
 */
const INVOICE_STATUS_OPTIONS: FieldOption[] = [
  { value: '', label: 'Any status' },
  { value: 'draft', label: 'Draft' },
  { value: 'issued', label: 'Issued' },
  { value: 'partially_paid', label: 'Partially paid' },
  { value: 'paid', label: 'Paid' },
  { value: 'void', label: 'Void' },
  { value: 'overdue', label: 'Overdue (derived)' },
  { value: 'outstanding', label: 'Outstanding (derived)' },
]

/**
 * How money moved, in the order a collections clerk reaches for them.
 *
 * `adjustment` is last and is a different thing from the other five, not another
 * way to pay: it is a credit note, and the server subtracts it from what has been
 * received rather than adding to it. It is in the list because a credit note has
 * to be recordable, and it is a receipt field rather than a method of tender for
 * the same reason - putting it in a "how did they pay" dropdown invites a bank
 * transfer to be entered as one.
 */
const PAYMENT_METHOD_OPTIONS: FieldOption[] = [
  { value: 'bank_transfer', label: 'Bank transfer' },
  { value: 'cash', label: 'Cash' },
  { value: 'card', label: 'Card' },
  { value: 'cheque', label: 'Cheque' },
  { value: 'letter_of_credit', label: 'Letter of credit' },
  { value: 'adjustment', label: 'Adjustment (credit note)' },
]

/**
 * What an outgoing cost was for.
 *
 * `duty` reads "Duty and tax" because that is the API's own label and because the
 * wider wording is what makes the one rule on this surface obvious: duty and tax
 * is money collected from the customer and handed to an authority, so it is never
 * a recharged cost and never eats margin, however billable the row looks.
 */
const EXPENSE_CATEGORY_OPTIONS: FieldOption[] = [
  { value: 'freight', label: 'Freight' },
  { value: 'duty', label: 'Duty and tax' },
  { value: 'brokerage', label: 'Brokerage' },
  { value: 'handling', label: 'Handling' },
  { value: 'storage', label: 'Storage' },
  { value: 'demurrage', label: 'Demurrage' },
  { value: 'insurance', label: 'Insurance' },
  { value: 'fuel', label: 'Fuel' },
  { value: 'overhead', label: 'Overhead' },
  { value: 'payroll', label: 'Payroll' },
  { value: 'other', label: 'Other' },
]

/** ISO 4217, because the money is. Free text, and a typo is a wrong currency. */
const CURRENCY_FIELD: FieldDef = {
  name: 'currency',
  label: 'Currency',
  kind: 'text',
  maxLength: 3,
  placeholder: 'AED',
  hint: 'ISO 4217 code, e.g. AED or USD.',
}

const REFERENCE_FIELD: FieldDef = {
  name: 'reference',
  label: 'Reference',
  kind: 'text',
  maxLength: 30,
  hint: 'Your own document number. Must be unique.',
  placeholder: 'INV-2026-0001',
}


const CODE_FIELD: FieldDef = {
  name: 'code',
  label: 'Code',
  kind: 'text',
  required: true,
  maxLength: 50,
  hint: 'Letters, numbers, dot, dash or underscore. Unique within this list.',
}

/* -------------------------------------------------------------------------- */
/* Party columns: customers, shippers, consignees                              */
/* -------------------------------------------------------------------------- */

const PARTY_COLUMNS: ColumnDef<Party>[] = [
  {
    key: 'code',
    header: 'Code',
    sortKey: 'code',
    render: (record) => <span className="font-medium text-ink-900">{record.code}</span>,
  },
  {
    key: 'name',
    header: 'Company',
    sortKey: 'name',
    render: (record) => <CellStack primary={record.name} secondary={record.contact_name ?? undefined} />,
  },
  {
    key: 'city',
    header: 'Location',
    sortKey: 'city',
    render: (record) => <AddressCell record={record} />,
  },
  {
    key: 'contact',
    header: 'Contact',
    render: (record) => <ContactCell record={record} />,
  },
  activeColumn<Party>(),
]

const PARTY_FIELDS: FieldDef[] = [
  { ...CODE_FIELD, hint: 'Letters, numbers, dot, dash or underscore. Unique among these companies.' },
  { name: 'name', label: 'Company name', kind: 'text', required: true, maxLength: 255 },
  { name: 'tax_number', label: 'Tax / VAT number', kind: 'text', maxLength: 100 },
  ...CONTACT_FIELDS,
  ...ADDRESS_FIELDS,
  ACTIVE_FIELD,
  NOTES_FIELD,
]

/* -------------------------------------------------------------------------- */
/* Site columns: warehouses, factories                                         */
/* -------------------------------------------------------------------------- */

const SITE_CORE_COLUMNS: ColumnDef<Site>[] = [
  {
    key: 'code',
    header: 'Code',
    sortKey: 'code',
    render: (record) => <span className="font-medium text-ink-900">{record.code}</span>,
  },
  {
    key: 'name',
    header: 'Site',
    sortKey: 'name',
    render: (record) => <CellStack primary={record.name} secondary={record.contact_name ?? undefined} />,
  },
  {
    key: 'city',
    header: 'Location',
    sortKey: 'city',
    render: (record) => <AddressCell record={record} />,
  },
  {
    key: 'contact',
    header: 'Contact',
    render: (record) => <ContactCell record={record} />,
  },
]

/** Everything a site has regardless of kind, up to but excluding the trailer. */
const SITE_CORE_FIELDS: FieldDef[] = [
  { ...CODE_FIELD, hint: 'Letters, numbers, dot, dash or underscore. Unique among these sites.' },
  { name: 'name', label: 'Site name', kind: 'text', required: true, maxLength: 255 },
  ...CONTACT_FIELDS,
  ...ADDRESS_FIELDS,
]

/**
 * Warehouse-only fields. The API marks these `prohibited` on a factory, so they
 * appear on the warehouse form and nowhere else.
 */
const WAREHOUSE_ONLY_FIELDS: FieldDef[] = [
  { name: 'site_type', label: 'Warehouse type', kind: 'text', maxLength: 50, placeholder: 'Bonded' },
  { name: 'capacity_pallets', label: 'Pallet capacity', kind: 'number', min: 0, max: 100000 },
]

/* -------------------------------------------------------------------------- */
/* The registry                                                                */
/* -------------------------------------------------------------------------- */

interface SurfaceMap {
  customers: SurfaceDef<Party>
  shippers: SurfaceDef<Party>
  consignees: SurfaceDef<Party>
  carriers: SurfaceDef<Carrier>
  vessels: SurfaceDef<Vessel>
  ports: SurfaceDef<Port>
  warehouses: SurfaceDef<Site>
  factories: SurfaceDef<Site>
  trucks: SurfaceDef<Truck>
  drivers: SurfaceDef<Driver>
  shipments: SurfaceDef<Shipment>
  bookings: SurfaceDef<Booking>
  tracking: SurfaceDef<ShipmentTracking>
  containers: SurfaceDef<Container>
  customs: SurfaceDef<CustomsClearance>
  deliveries: SurfaceDef<Delivery>
  charges: SurfaceDef<Charge>
  invoices: SurfaceDef<Invoice>
  payments: SurfaceDef<Payment>
  expenses: SurfaceDef<Expense>
  documents: SurfaceDef<Document>
}


export const SURFACES: SurfaceMap = {
  customers: {
    key: 'customers',
    path: '/customers',
    title: 'Customers',
    singular: 'customer',
    description: 'Companies the business bills. Also the read scope for shippers and consignees.',
    viewPermission: 'customer.view',
    writeNamespace: 'customer',
    label: (record) => record.name,
    relationLabels: () => ({}),
    columns: PARTY_COLUMNS,
    fields: PARTY_FIELDS,
    filters: [ACTIVE_FILTER, COUNTRY_FILTER],
    searchPlaceholder: 'Search by code, name, contact, city or country',
    defaultSort: 'name',
  },

  shippers: {
    key: 'shippers',
    path: '/shippers',
    title: 'Shippers',
    singular: 'shipper',
    description: 'Companies that export goods. A shipper is not a customer: it may never be invoiced.',
    viewPermission: 'customer.view',
    writeNamespace: 'shipper',
    label: (record) => record.name,
    relationLabels: () => ({}),
    columns: PARTY_COLUMNS,
    fields: PARTY_FIELDS,
    filters: [ACTIVE_FILTER, COUNTRY_FILTER],
    searchPlaceholder: 'Search by code, name, contact, city or country',
    defaultSort: 'name',
  },

  consignees: {
    key: 'consignees',
    path: '/consignees',
    title: 'Consignees',
    singular: 'consignee',
    description: 'Companies that receive goods. The delivery address lives here.',
    viewPermission: 'customer.view',
    writeNamespace: 'consignee',
    label: (record) => record.name,
    relationLabels: () => ({}),
    columns: PARTY_COLUMNS,
    fields: PARTY_FIELDS,
    filters: [ACTIVE_FILTER, COUNTRY_FILTER],
    searchPlaceholder: 'Search by code, name, contact, city or country',
    defaultSort: 'name',
  },

  carriers: {
    key: 'carriers',
    path: '/carriers',
    title: 'Carriers',
    singular: 'carrier',
    description: 'Transport providers. Trucks and drivers are assigned to a carrier.',
    viewPermission: 'carrier.view',
    writeNamespace: 'carrier',
    label: (record) => record.name,
    relationLabels: () => ({}),
    columns: [
      {
        key: 'code',
        header: 'Code',
        sortKey: 'code',
        render: (record) => <span className="font-medium text-ink-900">{record.code}</span>,
      },
      {
        key: 'name',
        header: 'Carrier',
        sortKey: 'name',
        render: (record) => (
          <CellStack primary={record.name} secondary={record.scac ? `SCAC ${record.scac}` : undefined} />
        ),
      },
      {
        key: 'type',
        header: 'Mode',
        render: (record) =>
          record.type ? <Badge tone="info">{humanise(record.type)}</Badge> : <span className="text-ink-300">—</span>,
      },
      {
        key: 'city',
        header: 'Location',
        render: (record) => <AddressCell record={record} />,
      },
      {
        key: 'contact',
        header: 'Contact',
        render: (record) => <ContactCell record={record} />,
      },
      activeColumn<Carrier>(),
    ],
    fields: [
      { ...CODE_FIELD, hint: 'Your own short reference, e.g. MAEU. Must be unique.' },
      { name: 'name', label: 'Carrier name', kind: 'text', required: true, maxLength: 255 },
      { name: 'scac', label: 'SCAC', kind: 'text', maxLength: 4, hint: 'Four letters, if the carrier has one.' },
      { name: 'type', label: 'Mode', kind: 'select', options: CARRIER_MODE_OPTIONS, placeholder: 'Not specified' },
      ...CONTACT_FIELDS,
      ...ADDRESS_FIELDS,
      ACTIVE_FIELD,
      NOTES_FIELD,
    ],
    filters: [
      ACTIVE_FILTER,
      { name: 'type', label: 'Mode', kind: 'select', options: CARRIER_MODE_OPTIONS },
      COUNTRY_FILTER,
    ],
    searchPlaceholder: 'Search by code, name, SCAC, city or country',
    defaultSort: 'name',
  },

  vessels: {
    key: 'vessels',
    path: '/vessels',
    title: 'Vessels',
    singular: 'vessel',
    description: 'Ships available for charter. Identified by IMO number, which is unique worldwide.',
    viewPermission: 'vessel.view',
    writeNamespace: 'vessel',
    label: (record) => record.name,
    relationLabels: () => ({}),
    columns: [
      {
        key: 'name',
        header: 'Vessel',
        sortKey: 'name',
        render: (record) => <span className="font-medium text-ink-900">{record.name}</span>,
      },
      {
        key: 'imo_number',
        header: 'IMO',
        sortKey: 'imo_number',
        render: (record) => <span className="tabular">{record.imo_number}</span>,
      },
      {
        key: 'mmsi',
        header: 'MMSI',
        render: (record) => <span className="tabular"><Text value={record.mmsi} /></span>,
      },
      {
        key: 'flag_country',
        header: 'Flag',
        render: (record) => <Text value={record.flag_country} />,
      },
      {
        key: 'vessel_type',
        header: 'Type',
        render: (record) => <Text value={record.vessel_type} />,
      },
      {
        key: 'dimensions',
        header: 'LOA / beam',
        align: 'right',
        render: (record) =>
          record.length_meters === null && record.beam_meters === null ? (
            <span className="text-ink-300">—</span>
          ) : (
            <span className="tabular">
              {formatNumber(record.length_meters)} × {formatNumber(record.beam_meters)} m
            </span>
          ),
      },
      {
        key: 'capacity_teu',
        header: 'TEU',
        sortKey: 'capacity_teu',
        align: 'right',
        render: (record) => <span className="tabular">{formatNumber(record.capacity_teu)}</span>,
      },
      activeColumn<Vessel>(),
    ],
    fields: [
      { name: 'name', label: 'Vessel name', kind: 'text', required: true, maxLength: 255 },
      {
        name: 'imo_number',
        label: 'IMO number',
        kind: 'text',
        required: true,
        maxLength: 7,
        hint: 'Seven digits, not starting with 0.',
      },
      { name: 'mmsi', label: 'MMSI', kind: 'text', maxLength: 9, hint: 'Nine digits.' },
      { name: 'flag_country', label: 'Flag', kind: 'text', maxLength: 2, hint: 'ISO 3166-1 alpha-2, e.g. PA.' },
      { name: 'vessel_type', label: 'Vessel type', kind: 'text', maxLength: 50, placeholder: 'Container ship' },
      { name: 'length_meters', label: 'Length overall (m)', kind: 'number', min: 1, max: 999 },
      { name: 'beam_meters', label: 'Beam (m)', kind: 'number', min: 1, max: 999 },
      { name: 'capacity_teu', label: 'Capacity (TEU)', kind: 'number', min: 0, max: 100000 },
      ACTIVE_FIELD,
      NOTES_FIELD,
    ],
    filters: [ACTIVE_FILTER],
    searchPlaceholder: 'Search by name, IMO, MMSI or flag',
    defaultSort: 'name',
  },

  ports: {
    key: 'ports',
    path: '/ports',
    title: 'Ports',
    singular: 'port',
    description: 'Loading and discharge points, identified by UN/LOCODE.',
    viewPermission: 'port.view',
    writeNamespace: 'port',
    label: (record) => record.name,
    relationLabels: () => ({}),
    columns: [
      {
        key: 'un_locode',
        header: 'UN/LOCODE',
        sortKey: 'un_locode',
        render: (record) => <span className="font-medium text-ink-900">{record.un_locode}</span>,
      },
      {
        key: 'name',
        header: 'Port',
        sortKey: 'name',
        render: (record) => <span className="font-medium text-ink-900">{record.name}</span>,
      },
      {
        key: 'country_code',
        header: 'Country',
        sortKey: 'country_code',
        render: (record) => record.country_code,
      },
      {
        key: 'port_type',
        header: 'Type',
        render: (record) => <Text value={record.port_type} />,
      },
      {
        key: 'position',
        header: 'Position',
        align: 'right',
        render: (record) =>
          record.latitude === null && record.longitude === null ? (
            <span className="text-ink-300">—</span>
          ) : (
            <span className="tabular">
              {formatNumber(record.latitude)}, {formatNumber(record.longitude)}
            </span>
          ),
      },
      {
        key: 'timezone',
        header: 'Timezone',
        render: (record) => <Text value={record.timezone} />,
      },
      activeColumn<Port>(),
    ],
    fields: [
      {
        name: 'un_locode',
        label: 'UN/LOCODE',
        kind: 'text',
        required: true,
        maxLength: 10,
        hint: 'Two country letters plus three location letters, e.g. SGSIN or DEHAM.',
      },
      { name: 'name', label: 'Port name', kind: 'text', required: true, maxLength: 255 },
      { name: 'country_code', label: 'Country code', kind: 'text', required: true, maxLength: 2, hint: 'ISO 3166-1 alpha-2, e.g. SG.' },
      { name: 'port_type', label: 'Port type', kind: 'text', maxLength: 50, placeholder: 'Seaport' },
      { name: 'latitude', label: 'Latitude', kind: 'decimal', min: -90, max: 90, step: 'any' },
      { name: 'longitude', label: 'Longitude', kind: 'decimal', min: -180, max: 180, step: 'any' },
      { name: 'timezone', label: 'Timezone', kind: 'text', maxLength: 64, placeholder: 'Asia/Singapore' },
      ACTIVE_FIELD,
      NOTES_FIELD,
    ],
    filters: [ACTIVE_FILTER, COUNTRY_FILTER],
    searchPlaceholder: 'Search by UN/LOCODE, name or country',
    defaultSort: 'name',
  },

  warehouses: {
    key: 'warehouses',
    path: '/warehouses',
    title: 'Warehouses',
    singular: 'warehouse',
    description: 'Storage sites. Also the read scope for factories.',
    viewPermission: 'warehouse.view',
    writeNamespace: 'warehouse',
    label: (record) => record.name,
    relationLabels: () => ({}),
    columns: [
      ...SITE_CORE_COLUMNS,
      {
        key: 'site_type',
        header: 'Type',
        render: (record) => <Text value={record.site_type} />,
      },
      {
        key: 'capacity_pallets',
        header: 'Pallet capacity',
        align: 'right',
        render: (record) => <span className="tabular">{formatNumber(record.capacity_pallets)}</span>,
      },
      activeColumn<Site>(),
    ],
    fields: [
      ...SITE_CORE_FIELDS,
      ...WAREHOUSE_ONLY_FIELDS,
      ACTIVE_FIELD,
      NOTES_FIELD,
    ],
    filters: [ACTIVE_FILTER, COUNTRY_FILTER],
    searchPlaceholder: 'Search by code, name, city or contact',
    defaultSort: 'name',
  },

  factories: {
    key: 'factories',
    path: '/factories',
    title: 'Factories',
    singular: 'factory',
    description: 'Production sites. A factory has no warehouse type or pallet capacity.',
    viewPermission: 'warehouse.view',
    writeNamespace: 'factory',
    label: (record) => record.name,
    relationLabels: () => ({}),
    // `site_type` and `capacity_pallets` are deliberately absent: the API marks
    // them `prohibited` on a factory, so sending them would be a 422.
    columns: [...SITE_CORE_COLUMNS, activeColumn<Site>()],
    fields: [...SITE_CORE_FIELDS, ACTIVE_FIELD, NOTES_FIELD],
    filters: [ACTIVE_FILTER, COUNTRY_FILTER],
    searchPlaceholder: 'Search by code, name, city or contact',
    defaultSort: 'name',
  },

  trucks: {
    key: 'trucks',
    path: '/trucks',
    title: 'Trucks',
    singular: 'truck',
    description: 'Vehicles available for road transport. Identified by plate number.',
    viewPermission: 'truck.view',
    writeNamespace: 'truck',
    label: (record) => record.plate_number,
    relationLabels: (record) => ({ carrier_id: record.carrier?.name }),
    relationFields: { carrier: 'carrier_id' },

    columns: [
      {
        key: 'plate_number',
        header: 'Plate number',
        sortKey: 'plate_number',
        render: (record) => <span className="font-medium text-ink-900">{record.plate_number}</span>,
      },
      {
        key: 'code',
        header: 'Code',
        sortKey: 'code',
        render: (record) => <Text value={record.code} />,
      },
      {
        key: 'carrier',
        header: 'Carrier',
        render: (record) =>
          record.carrier ? <RelationCell primary={record.carrier.name} secondary={record.carrier.code} /> : <Unassigned />,
      },
      {
        key: 'truck_type',
        header: 'Type',
        render: (record) =>
          record.truck_type ? <Badge tone="info">{humanise(record.truck_type)}</Badge> : <span className="text-ink-300">—</span>,
      },
      {
        key: 'capacity_kg',
        header: 'Capacity',
        sortKey: 'capacity_kg',
        align: 'right',
        render: (record) =>
          record.capacity_kg === null && record.capacity_pallets === null ? (
            <span className="text-ink-300">—</span>
          ) : (
            <span className="tabular">
              {formatNumber(record.capacity_kg)} kg
              {record.capacity_pallets !== null && ` · ${formatNumber(record.capacity_pallets)} plt`}
            </span>
          ),
      },
      {
        key: 'vehicle',
        header: 'Vehicle',
        render: (record) => <Text value={[record.make, record.model, record.year].filter(Boolean).join(' ')} />,
      },
      activeColumn<Truck>(),
    ],
    fields: [
      {
        name: 'plate_number',
        label: 'Plate number',
        kind: 'text',
        required: true,
        maxLength: 30,
        hint: 'Letters, numbers, spaces and dashes. Unique across all trucks.',
      },
      { name: 'code', label: 'Internal code', kind: 'text', maxLength: 50 },
      { name: 'carrier_id', label: 'Carrier', kind: 'select', reference: CARRIER_REFERENCE, placeholder: 'Not yet assigned' },
      {
        name: 'truck_type',
        label: 'Truck type',
        kind: 'select',
        placeholder: 'Not specified',
        options: [
          { value: 'tractor', label: 'Tractor' },
          { value: 'rigid', label: 'Rigid' },
          { value: 'box_truck', label: 'Box truck' },
          { value: 'flatbed', label: 'Flatbed' },
          { value: 'reefer', label: 'Refrigerated' },
          { value: 'tanker', label: 'Tanker' },
        ],
      },
      { name: 'capacity_kg', label: 'Capacity (kg)', kind: 'number', min: 0, max: 200000 },
      { name: 'capacity_pallets', label: 'Pallet capacity', kind: 'number', min: 0, max: 60 },
      { name: 'make', label: 'Make', kind: 'text', maxLength: 120 },
      { name: 'model', label: 'Model', kind: 'text', maxLength: 120 },
      { name: 'year', label: 'Year', kind: 'text', maxLength: 4, hint: 'Four digits.' },
      ACTIVE_FIELD,
      NOTES_FIELD,
    ],
    filters: [ACTIVE_FILTER, CARRIER_FILTER],
    searchPlaceholder: 'Search by plate, code, make or model',
    defaultSort: 'plate_number',
  },

  drivers: {
    key: 'drivers',
    path: '/drivers',
    title: 'Drivers',
    singular: 'driver',
    description: 'Drivers available for dispatch. Licences and medicals are tracked with expiry dates.',
    viewPermission: 'driver.view',
    writeNamespace: 'driver',
    label: (record) => record.name,
    relationLabels: (record) => ({
      carrier_id: record.carrier?.name,
      truck_id: record.truck?.plate_number,
    }),
    relationFields: { carrier: 'carrier_id', truck: 'truck_id' },

    columns: [
      {
        key: 'code',
        header: 'Code',
        sortKey: 'code',
        render: (record) => <span className="font-medium text-ink-900">{record.code}</span>,
      },
      {
        key: 'name',
        header: 'Driver',
        sortKey: 'name',
        render: (record) => <CellStack primary={record.name} secondary={record.phone ?? undefined} />,
      },
      {
        key: 'license_number',
        header: 'Licence',
        render: (record) => <Text value={record.license_number} />,
      },
      {
        key: 'carrier',
        header: 'Carrier',
        render: (record) =>
          record.carrier ? <RelationCell primary={record.carrier.name} secondary={record.carrier.code} /> : <Unassigned />,
      },
      {
        key: 'truck',
        header: 'Truck',
        render: (record) => <Text value={record.truck?.plate_number} />,
      },
      {
        key: 'license_expiry',
        header: 'Licence expiry',
        sortKey: 'license_expiry',
        render: (record) => <ExpiryCell value={record.license_expiry} />,
      },
      {
        key: 'medical_expiry',
        header: 'Medical expiry',
        render: (record) => <ExpiryCell value={record.medical_expiry} />,
      },
      activeColumn<Driver>(),
    ],
    fields: [
      {
        name: 'code',
        label: 'Code',
        kind: 'text',
        required: true,
        maxLength: 50,
        hint: 'Letters, numbers, dot, dash or underscore. Unique across all drivers.',
      },
      { name: 'name', label: 'Full name', kind: 'text', required: true, maxLength: 255 },
      { name: 'license_number', label: 'Licence number', kind: 'text', maxLength: 50 },
      { name: 'phone', label: 'Phone', kind: 'tel', maxLength: 50 },
      { name: 'email', label: 'Email', kind: 'email', maxLength: 255 },
      { name: 'nationality', label: 'Nationality', kind: 'text', maxLength: 2, hint: 'ISO 3166-1 alpha-2, e.g. IN.' },
      { name: 'carrier_id', label: 'Carrier', kind: 'select', reference: CARRIER_REFERENCE, placeholder: 'Not yet assigned' },
      {
        name: 'truck_id',
        label: 'Assigned truck',
        kind: 'select',
        reference: TRUCK_REFERENCE,
        placeholder: 'Not yet assigned',
        hint: 'A driver and their truck should belong to the same carrier.',
      },
      { name: 'license_expiry', label: 'Licence expiry', kind: 'date' },
      { name: 'medical_expiry', label: 'Medical expiry', kind: 'date' },
      ACTIVE_FIELD,
      NOTES_FIELD,
    ],
    filters: [
      ACTIVE_FILTER,
      CARRIER_FILTER,
      {
        name: 'expiring_within_days',
        label: 'Expiring within',
        kind: 'select',
        options: [
          { value: '', label: 'Any time' },
          { value: '30', label: '30 days' },
          { value: '60', label: '60 days' },
          { value: '90', label: '90 days' },
          { value: '180', label: '180 days' },
        ],
      },
    ],
    searchPlaceholder: 'Search by code, name, licence or phone',
    defaultSort: 'name',
  },

  shipments: {
    key: 'shipments',
    path: '/shipments',
    title: 'Shipments',
    singular: 'shipment',
    description:
      'Consignments in transit. Every partner is optional, so a draft can be started before the paperwork exists.',
    viewPermission: 'shipment.view',
    writeNamespace: 'shipment',
    label: (record) => record.reference,
    relationLabels: (record) => ({
      booking_id: record.booking?.reference,
      shipper_id: record.shipper?.name,
      consignee_id: record.consignee?.name,
      carrier_id: record.carrier?.name,
      vessel_id: record.vessel?.name,
      port_of_loading_id: record.port_of_loading?.un_locode,
      port_of_discharge_id: record.port_of_discharge?.un_locode,
      origin_site_id: record.origin_site?.name,
      destination_site_id: record.destination_site?.name,
      truck_id: record.truck?.plate_number,
      driver_id: record.driver?.name,
    }),
    /*
     * Eleven relations, and the mapping is declared rather than inferred. The API
     * returns `port_of_loading` but takes `port_of_loading_id`, so the two cannot
     * be derived from each other by adding a suffix - and guessing wrong here
     * would open the edit form with every dropdown blank.
     */
    relationFields: {
      booking: 'booking_id',
      shipper: 'shipper_id',
      consignee: 'consignee_id',
      carrier: 'carrier_id',
      vessel: 'vessel_id',
      port_of_loading: 'port_of_loading_id',
      port_of_discharge: 'port_of_discharge_id',
      origin_site: 'origin_site_id',
      destination_site: 'destination_site_id',
      truck: 'truck_id',
      driver: 'driver_id',
    },
    columns: [
      {
        key: 'reference',
        header: 'Reference',
        sortKey: 'reference',
        render: (record) => <span className="font-medium text-ink-900">{record.reference}</span>,
      },
      {
        key: 'status',
        header: 'Status',
        sortKey: 'status',
        render: (record) => (
          <ShipmentStatusBadge status={record.status} label={record.status_label} late={record.is_late} />
        ),
      },
      {
        key: 'parties',
        header: 'Shipper / consignee',
        render: (record) =>
          record.shipper || record.consignee ? (
            <CellStack
              primary={<Text value={record.shipper?.name ?? null} />}
              secondary={record.consignee?.name ?? undefined}
            />
          ) : (
            <Unassigned />
          ),
      },
      {
        key: 'carrier',
        header: 'Carrier',
        render: (record) =>
          record.carrier ? (
            <RelationCell primary={record.carrier.name} secondary={record.carrier.code} />
          ) : (
            <Unassigned />
          ),
      },
      {
        key: 'route',
        header: 'Route',
        render: (record) => {
          const legs = [
            record.port_of_loading?.un_locode,
            record.port_of_discharge?.un_locode,
          ].filter(Boolean)

          if (legs.length === 0) {
            return <span className="text-ink-300">—</span>
          }

          return <span className="tabular">{legs.join(' → ')}</span>
        },
      },
      {
        key: 'schedule',
        header: 'ETD / ETA',
        sortKey: 'etd',
        render: (record) =>
          record.etd === null && record.eta === null ? (
            <span className="text-ink-300">—</span>
          ) : (
            <span className="tabular">
              {record.etd ? formatDate(record.etd) : '—'} → {record.eta ? formatDate(record.eta) : '—'}
            </span>
          ),
      },
      {
        key: 'cargo',
        header: 'Cargo',
        align: 'right',
        render: (record) =>
          record.cargo_description === null && record.weight_kg === null ? (
            <span className="text-ink-300">—</span>
          ) : (
            <span className="tabular">
              {record.weight_kg === null ? '—' : `${formatNumber(record.weight_kg)} kg`}
              {record.pieces !== null && ` · ${formatNumber(record.pieces)} pcs`}
            </span>
          ),
      },
    ],
    fields: [
      {
        name: 'reference',
        label: 'Reference',
        kind: 'text',
        required: true,
        maxLength: 30,
        placeholder: 'SHP-2026-0001',
        hint: 'The number the consignment is quoted, booked and invoiced by. Unique, and stored upper-case.',
      },
      {
        name: 'status',
        label: 'Status',
        kind: 'select',
        options: SHIPMENT_STATUS_OPTIONS,
        hint: 'Where the consignment is in its lifecycle. The API does not enforce the order.',
      },
      {
        name: 'booking_id',
        label: 'Raised from booking',
        kind: 'select',
        reference: BOOKING_REFERENCE,
        placeholder: 'Not from a booking',
        hint: 'Optional. A consignment entered from a paper bill of lading has no booking behind it.',
      },
      {
        name: 'shipper_id',
        label: 'Shipper',
        kind: 'select',
        reference: partyReference('shippers'),
        placeholder: 'Not chosen yet',
      },
      {
        name: 'consignee_id',
        label: 'Consignee',
        kind: 'select',
        reference: partyReference('consignees'),
        placeholder: 'Not chosen yet',
      },
      {
        name: 'carrier_id',
        label: 'Carrier',
        kind: 'select',
        reference: CARRIER_REFERENCE,
        placeholder: 'Not yet assigned',
      },
      {
        name: 'vessel_id',
        label: 'Vessel',
        kind: 'select',
        reference: VESSEL_REFERENCE,
        placeholder: 'Not sea freight',
      },
      {
        name: 'port_of_loading_id',
        label: 'Port of loading',
        kind: 'select',
        reference: PORT_REFERENCE,
        placeholder: 'Not set',
      },
      {
        name: 'port_of_discharge_id',
        label: 'Port of discharge',
        kind: 'select',
        reference: PORT_REFERENCE,
        placeholder: 'Not set',
      },
      {
        name: 'origin_site_id',
        label: 'Origin site',
        kind: 'select',
        reference: SITE_REFERENCE,
        placeholder: 'Not set',
      },
      {
        name: 'destination_site_id',
        label: 'Destination site',
        kind: 'select',
        reference: SITE_REFERENCE,
        placeholder: 'Not set',
      },
      {
        name: 'truck_id',
        label: 'Truck',
        kind: 'select',
        reference: TRUCK_REFERENCE,
        placeholder: 'Not road freight',
      },
      {
        name: 'driver_id',
        label: 'Driver',
        kind: 'select',
        reference: DRIVER_REFERENCE,
        placeholder: 'Not assigned',
      },
      {
        name: 'incoterm',
        label: 'Incoterm',
        kind: 'select',
        options: INCOTERM_OPTIONS,
        placeholder: 'Not specified',
        hint: 'Incoterms 2020. Decides which side pays for what.',
      },
      {
        name: 'incoterm_place',
        label: 'Incoterm named place',
        kind: 'text',
        maxLength: 120,
        placeholder: 'Singapore',
        hint: 'The place the incoterm refers to, e.g. the port or the seller’s works.',
      },
      {
        name: 'cargo_description',
        label: 'Cargo description',
        kind: 'text',
        maxLength: 255,
        placeholder: 'Cotton garments, 40ft high cube',
      },
      { name: 'pieces', label: 'Pieces', kind: 'number', min: 0 },
      { name: 'weight_kg', label: 'Weight (kg)', kind: 'number', min: 0 },
      { name: 'volume_cbm', label: 'Volume (CBM)', kind: 'decimal', min: 0, step: 'any' },
      { name: 'etd', label: 'Estimated departure', kind: 'date' },
      { name: 'eta', label: 'Estimated arrival', kind: 'date' },
      { name: 'actual_departure', label: 'Actual departure', kind: 'date' },
      { name: 'actual_arrival', label: 'Actual arrival', kind: 'date' },
      NOTES_FIELD,
    ],
    filters: [
      {
        name: 'status',
        label: 'Status',
        kind: 'select',
        options: [{ value: '', label: 'All statuses' }, ...SHIPMENT_STATUS_OPTIONS],
      },
      {
        name: 'open_only',
        label: 'Open',
        kind: 'select',
        options: [
          { value: '', label: 'All shipments' },
          { value: '1', label: 'Still moving' },
        ],
      },
      {
        name: 'late',
        label: 'Overdue',
        kind: 'select',
        options: [
          { value: '', label: 'Any' },
          { value: '1', label: 'Past ETA' },
        ],
      },
      {
        name: 'unassigned',
        label: 'Assignment',
        kind: 'select',
        options: [
          { value: '', label: 'All' },
          { value: '1', label: 'No carrier' },
        ],
      },
      CARRIER_FILTER,
      {
        name: 'booking_id',
        label: 'Booking',
        kind: 'select',
        reference: BOOKING_REFERENCE,
        placeholder: 'Any booking',
      },
    ],
    searchPlaceholder: 'Search by reference, booking, cargo, shipper, consignee or carrier',
    defaultSort: 'reference',
    refetchInterval: LIVE_REFRESH.standard,
  },

  bookings: {
    key: 'bookings',
    path: '/bookings',
    title: 'Bookings',
    singular: 'booking',
    description:
      'Requests for space with a carrier, and what came back. A booking may sit here with no consignment raised from it yet.',
    viewPermission: 'booking.view',
    writeNamespace: 'booking',
    label: (record) => record.reference,
    relationLabels: (record) => ({
      shipper_id: record.shipper?.name,
      consignee_id: record.consignee?.name,
      carrier_id: record.carrier?.name,
      vessel_id: record.vessel?.name,
      port_of_loading_id: record.port_of_loading?.un_locode,
      port_of_discharge_id: record.port_of_discharge?.un_locode,
      origin_site_id: record.origin_site?.name,
      destination_site_id: record.destination_site?.name,
    }),
    /*
     * Eight relations, declared for the same reason shipments' are: the API takes
     * `port_of_loading_id` and returns `port_of_loading`, and the difference cannot
     * be derived by adding a suffix.
     */
    relationFields: {
      shipper: 'shipper_id',
      consignee: 'consignee_id',
      carrier: 'carrier_id',
      vessel: 'vessel_id',
      port_of_loading: 'port_of_loading_id',
      port_of_discharge: 'port_of_discharge_id',
      origin_site: 'origin_site_id',
      destination_site: 'destination_site_id',
    },
    columns: [
      {
        key: 'reference',
        header: 'Reference',
        sortKey: 'reference',
        render: (record) => <span className="font-medium text-ink-900">{record.reference}</span>,
      },
      {
        key: 'status',
        header: 'Status',
        sortKey: 'status',
        render: (record) => <BookingStatusBadge status={record.status} label={record.status_label} />,
      },
      {
        key: 'parties',
        header: 'Shipper / consignee',
        render: (record) =>
          record.shipper || record.consignee ? (
            <CellStack
              primary={<Text value={record.shipper?.name ?? null} />}
              secondary={record.consignee?.name ?? undefined}
            />
          ) : (
            <Unassigned />
          ),
      },
      {
        key: 'carrier',
        header: 'Carrier',
        render: (record) =>
          record.carrier ? (
            <RelationCell primary={record.carrier.name} secondary={record.carrier.code} />
          ) : (
            <Unassigned />
          ),
      },
      {
        key: 'route',
        header: 'Route',
        render: (record) => {
          const legs = [
            record.port_of_loading?.un_locode,
            record.port_of_discharge?.un_locode,
          ].filter(Boolean)

          if (legs.length === 0) {
            return <span className="text-ink-300">—</span>
          }

          return <span className="tabular">{legs.join(' → ')}</span>
        },
      },
      {
        key: 'schedule',
        header: 'Requested ETD / ETA',
        sortKey: 'requested_etd',
        render: (record) =>
          record.requested_etd === null && record.requested_eta === null ? (
            <span className="text-ink-300">—</span>
          ) : (
            <span className="tabular">
              {record.requested_etd ? formatDate(record.requested_etd) : '—'} →{' '}
              {record.requested_eta ? formatDate(record.requested_eta) : '—'}
            </span>
          ),
      },
      {
        key: 'cargo',
        header: 'Cargo',
        align: 'right',
        render: (record) =>
          record.cargo_description === null && record.weight_kg === null ? (
            <span className="text-ink-300">—</span>
          ) : (
            <span className="tabular">
              {record.weight_kg === null ? '—' : `${formatNumber(record.weight_kg)} kg`}
              {record.pieces !== null && ` · ${formatNumber(record.pieces)} pcs`}
            </span>
          ),
      },
    ],
    fields: [
      {
        name: 'reference',
        label: 'Reference',
        kind: 'text',
        required: true,
        maxLength: 30,
        placeholder: 'BKG-2026-0001',
        hint: 'The number the carrier quotes the request by. Unique, and stored upper-case.',
      },
      {
        name: 'status',
        label: 'Status',
        kind: 'select',
        options: BOOKING_STATUS_OPTIONS,
        hint: 'Where the request is in its lifecycle. The API does not enforce the order.',
      },
      {
        name: 'shipper_id',
        label: 'Shipper',
        kind: 'select',
        reference: partyReference('shippers'),
        placeholder: 'Not chosen yet',
      },
      {
        name: 'consignee_id',
        label: 'Consignee',
        kind: 'select',
        reference: partyReference('consignees'),
        placeholder: 'Not chosen yet',
      },
      {
        name: 'carrier_id',
        label: 'Carrier',
        kind: 'select',
        reference: CARRIER_REFERENCE,
        placeholder: 'Not yet sent',
      },
      {
        name: 'vessel_id',
        label: 'Vessel',
        kind: 'select',
        reference: VESSEL_REFERENCE,
        placeholder: 'Not chosen yet',
      },
      {
        name: 'port_of_loading_id',
        label: 'Port of loading',
        kind: 'select',
        reference: PORT_REFERENCE,
        placeholder: 'Not set',
      },
      {
        name: 'port_of_discharge_id',
        label: 'Port of discharge',
        kind: 'select',
        reference: PORT_REFERENCE,
        placeholder: 'Not set',
      },
      {
        name: 'origin_site_id',
        label: 'Origin site',
        kind: 'select',
        reference: SITE_REFERENCE,
        placeholder: 'Not set',
      },
      {
        name: 'destination_site_id',
        label: 'Destination site',
        kind: 'select',
        reference: SITE_REFERENCE,
        placeholder: 'Not set',
      },
      {
        name: 'incoterm',
        label: 'Incoterm',
        kind: 'select',
        options: INCOTERM_OPTIONS,
        placeholder: 'Not specified',
        hint: 'Incoterms 2020. Decides which side pays for what.',
      },
      {
        name: 'incoterm_place',
        label: 'Incoterm named place',
        kind: 'text',
        maxLength: 120,
        placeholder: 'Singapore',
        hint: 'The place the incoterm refers to, e.g. the port or the seller’s works.',
      },
      {
        name: 'cargo_description',
        label: 'Cargo description',
        kind: 'text',
        maxLength: 255,
        placeholder: 'Cotton garments, 40ft high cube',
      },
      { name: 'pieces', label: 'Pieces', kind: 'number', min: 0 },
      { name: 'weight_kg', label: 'Weight (kg)', kind: 'number', min: 0 },
      { name: 'volume_cbm', label: 'Volume (CBM)', kind: 'decimal', min: 0, step: 'any' },
      {
        name: 'requested_etd',
        label: 'Requested departure',
        kind: 'date',
        hint: 'The sailing being asked for, which is often not the one the carrier confirms.',
      },
      { name: 'requested_eta', label: 'Requested arrival', kind: 'date' },
      NOTES_FIELD,
    ],
    filters: [
      {
        name: 'status',
        label: 'Status',
        kind: 'select',
        options: [{ value: '', label: 'All statuses' }, ...BOOKING_STATUS_OPTIONS],
      },
      {
        name: 'open_only',
        label: 'Open',
        kind: 'select',
        options: [
          { value: '', label: 'All bookings' },
          { value: '1', label: 'Still live' },
        ],
      },
      {
        name: 'pending',
        label: 'Awaiting answer',
        kind: 'select',
        options: [
          { value: '', label: 'Any' },
          { value: '1', label: 'Sent, not confirmed' },
        ],
      },
      {
        name: 'unassigned',
        label: 'Assignment',
        kind: 'select',
        options: [
          { value: '', label: 'All' },
          { value: '1', label: 'No carrier' },
        ],
      },
      CARRIER_FILTER,
    ],
    searchPlaceholder: 'Search by reference, cargo, shipper, consignee or carrier',
    defaultSort: 'reference',
  },

  tracking: {
    key: 'tracking',
    path: '/tracking',
    title: 'Tracking',
    singular: 'tracking report',
    description:
      'What each consignment last reported. Every consignment has a row, so a missing milestone means nobody has said anything yet rather than nobody is watching.',
    viewPermission: 'tracking.view',
    writeNamespace: 'tracking',
    /*
     * Named after the consignment, not the row.
     *
     * A tracking row has no name of its own - it is identified entirely by which
     * consignment it describes - so every place a record is named in prose (the
     * delete confirmation, a toast, the table's accessible row name) uses the
     * consignment's reference. It is also what makes this surface the one place
     * `label` has to cope with a missing relation.
     */
    label: (record) => record.shipment?.reference ?? `Tracking report #${record.id}`,
    // No relation is written by this form, so there is nothing to map: the
    // consignment is found through the row's id, and the API ignores a
    // `shipment_id` sent by a client rather than believing it.
    relationLabels: () => ({}),
    columns: [
      {
        key: 'consignment',
        header: 'Consignment',
        // Sorted on the API as `shipment_id`; the reference itself is nested and
        // not a column of this table.
        sortKey: 'shipment_id',
        render: (record) =>
          record.shipment ? (
            <CellStack
              primary={
                <span className="font-medium text-ink-900">{record.shipment.reference}</span>
              }
              secondary={record.shipment.carrier?.name ?? undefined}
            />
          ) : (
            <Unassigned />
          ),
      },
      {
        key: 'status',
        header: 'Status',
        // The consignment's own status, not the milestone: this is where the cargo
        // has got to, which is a decision the business makes. The milestone
        // column below is the carrier's claim, and the two disagree often enough
        // that showing either one alone would mislead.
        render: (record) =>
          record.shipment ? (
            <ShipmentStatusBadge
              status={record.shipment.status}
              label={record.shipment.status_label}
              late={record.shipment.is_late}
            />
          ) : (
            <Unassigned />
          ),
      },
      {
        key: 'milestone',
        header: 'Last milestone',
        sortKey: 'last_milestone',
        render: (record) => {
          if (!record.last_milestone) {
            return <Badge tone="neutral">Not reported</Badge>
          }

          return (
            <Badge tone={record.is_problem ? 'danger' : 'info'}>
              {record.last_milestone_label ?? humanise(record.last_milestone)}
            </Badge>
          )
        },
      },
      {
        key: 'location',
        header: 'Last known location',
        render: (record) =>
          record.current_location ? (
            <CellStack
              primary={<Text value={record.current_location} />}
              secondary={record.source ?? undefined}
            />
          ) : (
            <Unassigned />
          ),
      },
      {
        key: 'reported',
        header: 'Last report',
        sortKey: 'last_reported_at',
        /*
         * Relative, and deliberately the column the screen is sorted to make sense
         * by. A worklist is read by asking how long ago we heard, so the absolute
         * timestamp goes underneath it for when the answer is "2 days ago" and the
         * operator needs the date.
         *
         * An unreported consignment reads as its own state rather than as an
         * infinitely old report, because those are different findings: one is a
         * carrier we have not heard from, the other is a consignment nobody has
         * started tracking.
         */
        render: (record) =>
          record.is_unreported ? (
            <Badge tone="warning">Never reported</Badge>
          ) : (
            <CellStack
              primary={<span className="tabular">{formatRelativeTime(record.last_reported_at)}</span>}
              secondary={formatDateTime(record.last_reported_at)}
            />
          ),
      },
      {
        key: 'revised_eta',
        header: 'Revised ETA',
        sortKey: 'revised_eta',
        /*
         * The consignment's planned arrival and the carrier's new promise, side by
         * side. A revised date is only alarming next to the date it replaced, and
         * replacing the plan rather than showing both would destroy the one record
         * that the plan was ever wrong.
         */
        render: (record) =>
          record.revised_eta === null ? (
            <span className="text-ink-300">—</span>
          ) : (
            <CellStack
              primary={<span className="tabular">{formatDate(record.revised_eta)}</span>}
              secondary={
                record.shipment?.eta ? `was ${formatDate(record.shipment.eta)}` : undefined
              }
            />
          ),
      },
      {
        key: 'flags',
        header: '',
        /*
         * No header: these are the findings rather than a column of data, and they
         * duplicate what the filters select on. Rendered only while the row is
         * still moving - a delivered consignment is silent for weeks by definition,
         * and a "stale" badge on finished cargo is an alarm about nothing.
         */
        render: (record) => {
          if (record.shipment?.is_closed) return null

          const flags = []

          if (record.is_problem) {
            flags.push(
              <Badge key="problem" tone="danger">
                Needs attention
              </Badge>,
            )
          }

          if (record.is_delayed) {
            flags.push(
              <Badge key="delayed" tone="warning">
                Delayed
              </Badge>,
            )
          }

          if (record.is_stale) {
            flags.push(
              <Badge key="stale" tone="warning">
                Gone quiet
              </Badge>,
            )
          }

          if (flags.length === 0) return null

          return <span className="flex flex-wrap gap-1">{flags}</span>
        },
      },
    ],
    fields: [
      {
        name: 'last_milestone',
        label: 'Last milestone',
        kind: 'select',
        options: TRACKING_MILESTONE_OPTIONS,
        placeholder: 'Not reported yet',
        hint: 'What the carrier last said. Leave blank for a consignment nobody has heard from.',
      },
      {
        name: 'current_location',
        label: 'Current location',
        kind: 'text',
        maxLength: 160,
        placeholder: 'Port of Singapore, terminal 3',
        hint: 'Free text, in the carrier’s words. A place, a leg, or "on the water".',
      },
      {
        name: 'last_reported_at',
        label: 'Last reported at',
        kind: 'date',
        hint: 'When this report arrived, not when the event happened. A milestone noticed this morning can be dated to yesterday.',
      },
      {
        name: 'revised_eta',
        label: 'Revised ETA',
        kind: 'date',
        hint: 'What the carrier has since promised. Kept beside the consignment’s own ETA, not over it.',
      },
      {
        name: 'delay_reason',
        label: 'Delay reason',
        kind: 'text',
        maxLength: 255,
        placeholder: 'Customs examination extended',
        hint: 'Optional even when a delay is recorded: an operator told the cargo is behind often knows the new date and not the reason.',
        wide: true,
      },
      {
        name: 'source',
        label: 'Source',
        kind: 'text',
        maxLength: 50,
        placeholder: 'EDI',
        hint: 'Where the report came from — a carrier portal, an EDI feed, a phone call. Not constrained, because the set grows with every carrier onboarded.',
      },
      NOTES_FIELD,
    ],
    filters: [
      {
        name: 'last_milestone',
        label: 'Milestone',
        kind: 'select',
        options: [{ value: '', label: 'Any milestone' }, ...TRACKING_MILESTONE_OPTIONS],
      },
      {
        name: 'stale',
        label: 'Quiet',
        kind: 'select',
        options: [
          { value: '', label: 'Any' },
          { value: '1', label: 'Gone quiet' },
        ],
      },
      {
        name: 'delayed',
        label: 'Delayed',
        kind: 'select',
        options: [
          { value: '', label: 'Any' },
          { value: '1', label: 'Running late' },
        ],
      },
      {
        name: 'problems',
        label: 'Problems',
        kind: 'select',
        options: [
          { value: '', label: 'Any' },
          { value: '1', label: 'Needs attention' },
        ],
      },
      {
        name: 'open_only',
        label: 'Open',
        kind: 'select',
        options: [
          { value: '', label: 'All consignments' },
          { value: '1', label: 'Still moving' },
        ],
      },
      CARRIER_FILTER,
    ],
    searchPlaceholder: 'Search by consignment reference, location, delay reason or source',
    /*
     * Most recently heard first, rather than by id or reference. A worklist is
     * read newest-report-first, and the alternative would leave the consignment
     * that went quiet this morning at the bottom of page one.
     */
    defaultSort: 'last_reported_at',
    /*
     * The shortest interval on any surface: this is the one screen whose whole
     * purpose is what changed while you were not looking, so it is the one
     * screen where the update has to arrive without being asked for.
     */
    refetchInterval: LIVE_REFRESH.fast,
  },

  containers: {
    key: 'containers',
    path: '/containers',
    title: 'Containers',
    singular: 'container',
    description:
      'The equipment on each consignment. A box number repeats across consignments, so every row is read against the consignment it is travelling on.',
    viewPermission: 'container.view',
    writeNamespace: 'container',
    /*
     * The box number, not the row - the same argument as the tracking surface's
     * choice, and for a sharper reason: this is the one number a carrier quotes
     * back to you, so it has to be the string in every confirmation, toast and
     * dialog rather than an id.
     */
    label: (record) => record.container_number,
    relationLabels: (record) => ({
      shipment_id: record.shipment?.reference,
    }),
    relationFields: { shipment: 'shipment_id' },
    columns: [
      {
        key: 'container_number',
        header: 'Container',
        sortKey: 'container_number',
        render: (record) => (
          <CellStack
            primary={
              <span className="font-medium text-ink-900 tabular">{record.container_number}</span>
            }
            secondary={record.iso_code ?? undefined}
          />
        ),
      },
      {
        key: 'status',
        header: 'Status',
        sortKey: 'status',
        render: (record) => (
          <ContainerStatusBadge
            status={record.status}
            label={record.status_label}
            attention={record.needs_attention}
          />
        ),
      },
      {
        key: 'shipment',
        header: 'Consignment',
        /*
         * The consignment's reference and the port it is bound for together, because
         * a box on a customs hold is read by asking which border it is stuck at, and
         * that is the one question a bare reference cannot answer.
         */
        render: (record) =>
          record.shipment ? (
            <CellStack
              primary={
                <span className="font-medium text-ink-900">{record.shipment.reference}</span>
              }
              secondary={record.shipment.port_of_discharge?.un_locode ?? undefined}
            />
          ) : (
            <Unassigned />
          ),
      },
      {
        key: 'consignee',
        header: 'Consignee',
        render: (record) =>
          record.shipment?.consignee ? (
            <Text value={record.shipment.consignee.name} />
          ) : (
            <Unassigned />
          ),
      },
      {
        key: 'type',
        header: 'Type',
        render: (record) =>
          record.type ? <Text value={record.type} /> : <span className="text-ink-300">—</span>,
      },
      {
        key: 'seal_number',
        header: 'Seal',
        render: (record) =>
          record.seal_number ? (
            <span className="tabular">{record.seal_number}</span>
          ) : (
            <span className="text-ink-300">—</span>
          ),
      },
      {
        key: 'utilisation',
        header: 'Rating used',
        align: 'right',
        /*
         * The weight against the rating rather than the payload, which is the
         * server's comparison and not one made here - and it reads as remaining
         * capacity, because that is what the number is.
         *
         * Null is rendered as a dash rather than as 0% on purpose. An unweighed box
         * and a box with nothing in it are different findings, and a zero would say
         * the second about a row that only knows the first.
         */
        render: (record) =>
          record.utilisation_percent === null ? (
            <span className="text-ink-300">—</span>
          ) : (
            <span className="tabular">
              {formatNumber(record.gross_weight_kg)} / {formatNumber(record.max_gross_weight_kg)} kg
            </span>
          ),
      },
    ],
    fields: [
      {
        name: 'shipment_id',
        label: 'Consignment',
        kind: 'select',
        required: true,
        reference: SHIPMENT_REFERENCE,
        hint: 'The consignment this box is travelling on. Required, because a container number is reused and only the pair identifies the box.',
      },
      {
        name: 'container_number',
        label: 'Container number',
        kind: 'text',
        required: true,
        maxLength: 20,
        placeholder: 'MSCU7741203',
        hint: 'The box marking, upper-cased. Unique per consignment rather than globally - the same box comes back on a later one.',
      },
      {
        name: 'status',
        label: 'Status',
        kind: 'select',
        options: CONTAINER_STATUS_OPTIONS,
        hint: 'Where the box is in its journey. The API does not enforce the order.',
      },
      {
        name: 'iso_code',
        label: 'ISO size and type',
        kind: 'text',
        maxLength: 10,
        placeholder: '45G1',
        hint: 'The ISO 6346 code a carrier quotes, e.g. 45G1 for a 40ft high cube.',
      },
      {
        name: 'type',
        label: 'Description',
        kind: 'text',
        maxLength: 50,
        placeholder: '40ft high cube, dry',
        hint: 'Free text, in your own words. The ISO code above is the one that is standardised.',
      },
      {
        name: 'seal_number',
        label: 'Seal number',
        kind: 'text',
        maxLength: 50,
        placeholder: 'SL771204',
        hint: 'The seal applied at stuffing. It changes on arrival when the box is cut open, which is how a delivery is proved.',
      },
      {
        name: 'tare_weight_kg',
        label: 'Tare (kg)',
        kind: 'number',
        min: 0,
        hint: 'The empty weight from the box’s data plate. Recorded because it is the figure a reweigh is checked against.',
      },
      {
        name: 'gross_weight_kg',
        label: 'Gross weight (kg)',
        kind: 'number',
        min: 0,
        hint: 'Box plus contents, as weighed when it was stuffed. With the rating below, this is what fills the utilisation column.',
      },
      {
        name: 'max_gross_weight_kg',
        label: 'Max gross weight (kg)',
        kind: 'number',
        min: 0,
        placeholder: '30480',
        hint: 'The box’s rated capacity. Leave it blank rather than guessing: an invented rating makes the utilisation column confidently wrong.',
      },
      NOTES_FIELD,
    ],
    filters: [
      {
        name: 'status',
        label: 'Status',
        kind: 'select',
        options: [{ value: '', label: 'Any status' }, ...CONTAINER_STATUS_OPTIONS],
      },
      SHIPMENT_FILTER,
      {
        name: 'needs_attention',
        label: 'Attention',
        kind: 'select',
        options: [
          { value: '', label: 'Any' },
          { value: '1', label: 'Needs attention' },
        ],
      },
      {
        name: 'open_only',
        label: 'Open',
        kind: 'select',
        options: [
          { value: '', label: 'All consignments' },
          { value: '1', label: 'Still moving' },
        ],
      },
      {
        name: 'empty',
        label: 'Empty',
        kind: 'select',
        options: [
          { value: '', label: 'Any' },
          { value: '1', label: 'Empty' },
        ],
      },
      {
        name: 'iso_code',
        label: 'ISO code',
        kind: 'text',
        placeholder: '45G1',
      },
      CARRIER_FILTER,
    ],
    searchPlaceholder: 'Search by box number, consignment reference or carrier',
    defaultSort: 'container_number',
  },

  customs: {
    key: 'customs',
    path: '/customs',
    title: 'Customs',
    singular: 'clearance',
    description:
      'One consignment’s business with one port’s authority. A transshipment clears more than once, so a consignment can have several filings.',
    viewPermission: 'customs.view',
    writeNamespace: 'customs',
    /*
     * The entry number where there is one, and the consignment and port where there
     * is not.
     *
     * A filing that has not been made yet has no entry number at all, which is
     * exactly the row a customs screen exists to surface - so naming it needs a
     * fallback that says whose business it is and where, rather than "Clearance #12".
     */
    label: (record) =>
      record.entry_number
        ? `${record.entry_number} — ${record.shipment?.reference ?? `filing #${record.id}`}`
        : `${record.shipment?.reference ?? `Filing #${record.id}`} — ${
            record.port?.un_locode ?? 'no port on file'
          }`,
    relationLabels: (record) => ({
      shipment_id: record.shipment?.reference,
      port_id: record.port?.un_locode,
    }),
    relationFields: { shipment: 'shipment_id', port: 'port_id' },
    columns: [
      {
        key: 'consignment',
        header: 'Consignment',
        /*
         * Sorted on the API as `shipment_id`; the reference itself is nested and not
         * a column of this table.
         */
        sortKey: 'shipment_id',
        render: (record) =>
          record.shipment ? (
            <CellStack
              primary={
                <span className="font-medium text-ink-900">{record.shipment.reference}</span>
              }
              secondary={record.shipment.status_label}
            />
          ) : (
            <Unassigned />
          ),
      },
      {
        key: 'port',
        header: 'Port',
        render: (record) =>
          record.port ? (
            <CellStack primary={record.port.un_locode} secondary={record.port.name} />
          ) : (
            /*
             * Not a dash. A null port means the lookup entry was removed after the
             * filing was made, and the row still says what happened - so it reads as
             * a port that is no longer on file rather than as a filing with no port,
             * which would suggest it was never made.
             */
            <span className="text-ink-500">Port no longer on file</span>
          ),
      },
      {
        key: 'status',
        header: 'Status',
        sortKey: 'status',
        render: (record) => (
          <CustomsStatusBadge
            status={record.status}
            label={record.status_label}
            held={record.is_held}
            attention={record.needs_attention}
          />
        ),
      },
      {
        key: 'entry_number',
        header: 'Entry number',
        /*
         * The authority's own reference, and blank until something is filed. A
         * filing with no entry number is a real and common state rather than a
         * missing value, so it is named as one.
         */
        render: (record) =>
          record.entry_number ? (
            <span className="tabular">{record.entry_number}</span>
          ) : (
            <Badge tone="neutral">Not filed</Badge>
          ),
      },
      {
        key: 'broker',
        header: 'Broker',
        render: (record) =>
          record.broker ? <Text value={record.broker} /> : <Unassigned />,
      },
      {
        key: 'submitted_at',
        header: 'Submitted',
        sortKey: 'submitted_at',
        /*
         * When the filing went in, not when it cleared. A refusal is the case that
         * makes the distinction matter: the date is how long the broker has been
         * waiting, and a screen showing only the clearing date would show nothing at
         * all for the rows that went wrong.
         */
        render: (record) =>
          record.submitted_at ? (
            <span className="tabular">{formatDateTime(record.submitted_at)}</span>
          ) : (
            <span className="text-ink-300">—</span>
          ),
      },
      {
        key: 'cleared_at',
        header: 'Cleared',
        sortKey: 'cleared_at',
        render: (record) =>
          record.cleared_at ? (
            <span className="tabular">{formatDateTime(record.cleared_at)}</span>
          ) : (
            <span className="text-ink-300">—</span>
          ),
      },
      {
        key: 'hold_reason',
        header: 'Why it is held',
        /*
         * A free-text column that is the reason a worklist is worth having: a
         * "Re-export declaration refused" on the row is the whole finding, and
         * without it the operator opens each filing to read one line of prose.
         */
        render: (record) =>
          record.hold_reason ? (
            <span className="line-clamp-2">{record.hold_reason}</span>
          ) : (
            <span className="text-ink-300">—</span>
          ),
      },
    ],
    fields: [
      {
        name: 'shipment_id',
        label: 'Consignment',
        kind: 'select',
        required: true,
        reference: SHIPMENT_REFERENCE,
        hint: 'The consignment being cleared. One filing per consignment per port, so a transshipment has two.',
      },
      {
        name: 'port_id',
        label: 'Port',
        kind: 'select',
        reference: PORT_REFERENCE,
        placeholder: 'Not recorded',
        hint: 'Where the filing was made. Optional because the port can be deleted from the lookup table afterwards, and the filing outlives it.',
      },
      {
        name: 'status',
        label: 'Status',
        kind: 'select',
        options: CUSTOMS_STATUS_OPTIONS,
        hint: 'Where the filing stands. Approved means the cargo may leave; only completed means nothing further is expected.',
      },
      {
        name: 'entry_number',
        label: 'Entry number',
        kind: 'text',
        maxLength: 60,
        placeholder: 'SG-4471/2026',
        hint: 'The authority’s own reference, once something is filed. Blank while the filing is still pending.',
      },
      {
        name: 'broker',
        label: 'Broker',
        kind: 'text',
        maxLength: 120,
        hint: 'Who is handling it. This is who an operator rings, so it is worth having even before the filing exists.',
      },
      {
        name: 'submitted_at',
        label: 'Submitted at',
        kind: 'datetime',
        hint: 'When the filing went in. Left blank while nothing has been submitted, which is how the screen tells a pending filing from a slow one.',
      },
      {
        name: 'cleared_at',
        label: 'Cleared at',
        kind: 'datetime',
        hint: 'When the goods were released. Only meaningful on a completed filing.',
      },
      {
        name: 'hold_reason',
        label: 'Hold reason',
        kind: 'text',
        maxLength: 255,
        placeholder: 'Phytosanitary certificate not presented',
        hint: 'What the authority is waiting on. This is the line a worklist is read for, so write it for whoever picks the row up next.',
        wide: true,
      },
      NOTES_FIELD,
    ],
    filters: [
      {
        name: 'status',
        label: 'Status',
        kind: 'select',
        options: [{ value: '', label: 'Any status' }, ...CUSTOMS_STATUS_OPTIONS],
      },
      SHIPMENT_FILTER,
      {
        name: 'port_id',
        label: 'Port',
        kind: 'select',
        reference: PORT_REFERENCE,
      },
      {
        name: 'held',
        label: 'Held',
        kind: 'select',
        options: [
          { value: '', label: 'Any' },
          { value: '1', label: 'With the authority' },
        ],
      },
      {
        name: 'needs_attention',
        label: 'Attention',
        kind: 'select',
        options: [
          { value: '', label: 'Any' },
          { value: '1', label: 'Needs attention' },
        ],
      },
      {
        name: 'open_only',
        label: 'Open',
        kind: 'select',
        options: [
          { value: '', label: 'All consignments' },
          { value: '1', label: 'Still moving' },
        ],
      },
      CARRIER_FILTER,
    ],
    searchPlaceholder: 'Search by entry number, broker, consignment or port',
    /*
     * Most recently submitted first. A customs queue is worked in the order filings
     * arrived, and sorting by id or leaving it unsorted would bury the filing that
     * went in this morning under a year of finished ones.
     */
    defaultSort: 'submitted_at',
  },

  deliveries: {
    key: 'deliveries',
    path: '/deliveries',
    title: 'Deliveries',
    singular: 'delivery',
    description:
      'Booked collections and what became of them. A consignment can have several, and a failed attempt stays on the record because it is the reason the next one exists.',
    viewPermission: 'delivery.view',
    writeNamespace: 'delivery',
    label: (record) => record.reference,
    relationLabels: (record) => ({
      shipment_id: record.shipment?.reference,
      truck_id: record.truck?.plate_number,
      driver_id: record.driver?.name,
    }),
    relationFields: { shipment: 'shipment_id', truck: 'truck_id', driver: 'driver_id' },
    columns: [
      {
        key: 'reference',
        header: 'Reference',
        sortKey: 'reference',
        render: (record) => (
          <span className="font-medium text-ink-900 tabular">{record.reference}</span>
        ),
      },
      {
        key: 'status',
        header: 'Status',
        sortKey: 'status',
        render: (record) => (
          <DeliveryStatusBadge
            status={record.status}
            label={record.status_label}
            overdue={record.is_overdue}
          />
        ),
      },
      {
        key: 'shipment',
        header: 'Consignment',
        sortKey: 'shipment_id',
        render: (record) =>
          record.shipment ? (
            <CellStack
              primary={
                <span className="font-medium text-ink-900">{record.shipment.reference}</span>
              }
              secondary={record.shipment.consignee?.name ?? undefined}
            />
          ) : (
            <Unassigned />
          ),
      },
      {
        key: 'scheduled_at',
        header: 'Booked slot',
        sortKey: 'scheduled_at',
        /*
         * The slot is the first thing a driver asks about and the first thing an
         * operator is chasing, so it is sorted by and shown with its time rather
         * than as a date. Relative underneath, because "2h ago" is how an overdue
         * slot is actually read.
         */
        render: (record) =>
          record.scheduled_at ? (
            <CellStack
              primary={<span className="tabular">{formatDateTime(record.scheduled_at)}</span>}
              secondary={formatRelativeTime(record.scheduled_at)}
            />
          ) : (
            <span className="text-ink-300">Not booked</span>
          ),
      },
      {
        key: 'vehicle',
        header: 'Vehicle',
        /*
         * The plate and the driver together, because a driver is identified by the
         * truck they arrived in as often as by name - the same substitution the
         * delivery address column below invites.
         */
        render: (record) =>
          record.truck ? (
            <CellStack
              primary={<span className="tabular">{record.truck.plate_number}</span>}
              secondary={record.driver?.name ?? undefined}
            />
          ) : (
            <Unassigned />
          ),
      },
      {
        key: 'delivered_at',
        header: 'Delivered',
        sortKey: 'delivered_at',
        render: (record) =>
          record.delivered_at ? (
            <CellStack
              primary={<span className="tabular">{formatDateTime(record.delivered_at)}</span>}
              secondary={record.pod_reference ?? undefined}
            />
          ) : (
            <span className="text-ink-300">—</span>
          ),
      },
      {
        key: 'failure_reason',
        header: 'Why it failed',
        /*
         * Only rendered for a failed attempt, so the column is empty for every row
         * that went to plan. A dash would imply something is missing on the rows
         * that are fine; nothing at all says the question does not apply.
         */
        render: (record) =>
          record.failure_reason ? (
            <span className="line-clamp-2">{record.failure_reason}</span>
          ) : null
        ,
      },
    ],
    fields: [
      {
        name: 'reference',
        label: 'Reference',
        kind: 'text',
        required: true,
        maxLength: 30,
        placeholder: 'DLV-2026-0001',
        hint: 'Ours rather than the carrier’s, so it is unique on its own. There is no generator: type it, as with a consignment reference.',
      },
      {
        name: 'shipment_id',
        label: 'Consignment',
        kind: 'select',
        required: true,
        reference: SHIPMENT_REFERENCE,
        hint: 'The consignment being collected. Closed consignments are still offered - recording the collection against one is how a disagreement gets settled.',
      },
      {
        name: 'status',
        label: 'Status',
        kind: 'select',
        options: DELIVERY_STATUS_OPTIONS,
        hint: 'Failed is not the end: leave the row failed and book the retry, so the attempt that went wrong stays on the record.',
      },
      {
        name: 'truck_id',
        label: 'Truck',
        kind: 'select',
        reference: TRUCK_REFERENCE,
        placeholder: 'Not assigned',
        hint: 'Optional, and left blank more often than not: a collection booked days ahead has no vehicle on it yet.',
      },
      {
        name: 'driver_id',
        label: 'Driver',
        kind: 'select',
        reference: DRIVER_REFERENCE,
        placeholder: 'Not assigned',
      },
      {
        name: 'scheduled_at',
        label: 'Booked slot',
        kind: 'datetime',
        hint: 'When the vehicle is due. The API treats a missing slot as not-yet-booked rather than as late, so leave it blank until there is one.',
      },
      {
        name: 'picked_up_at',
        label: 'Picked up at',
        kind: 'datetime',
        hint: 'When the cargo was collected. Independent of the slot on purpose - a driver who turned up early still picked it up.',
      },
      {
        name: 'delivered_at',
        label: 'Delivered at',
        kind: 'datetime',
        hint: 'When it was handed over. Set this and a proof-of-delivery reference to close the row out.',
      },
      {
        name: 'contact_name',
        label: 'Contact name',
        kind: 'text',
        maxLength: 120,
        hint: 'Who to ask for on arrival.',
      },
      {
        name: 'contact_phone',
        label: 'Contact phone',
        kind: 'tel',
        maxLength: 40,
      },
      {
        name: 'delivery_address',
        label: 'Delivery address',
        kind: 'textarea',
        maxLength: 2000,
        placeholder: '725 Harbor Plaza, Long Beach, CA 90802 - bay 14, ask for Jordan',
        hint: 'The driver’s instructions, bay and all. Anything the address fields on a consignment would not carry.',
      },
      {
        name: 'pod_reference',
        label: 'Proof of delivery',
        kind: 'text',
        maxLength: 60,
        placeholder: 'POD-2026-004412',
        hint: 'The signed docket’s own number. This is what a later dispute is settled from.',
      },
      {
        name: 'failure_reason',
        label: 'Failure reason',
        kind: 'text',
        maxLength: 255,
        placeholder: 'Site gate closed on the first attempt, no access',
        hint: 'Why the attempt did not work. A worklist is read for this line, so write it for whoever books the retry.',
        wide: true,
      },
      NOTES_FIELD,
    ],
    filters: [
      {
        name: 'status',
        label: 'Status',
        kind: 'select',
        options: [{ value: '', label: 'Any status' }, ...DELIVERY_STATUS_OPTIONS],
      },
      SHIPMENT_FILTER,
      {
        name: 'truck_id',
        label: 'Truck',
        kind: 'select',
        reference: TRUCK_REFERENCE,
      },
      {
        name: 'driver_id',
        label: 'Driver',
        kind: 'select',
        reference: DRIVER_REFERENCE,
      },
      {
        name: 'overdue',
        label: 'Overdue',
        kind: 'select',
        options: [
          { value: '', label: 'Any' },
          { value: '1', label: 'Slot has passed' },
        ],
      },
      {
        name: 'outstanding',
        label: 'Outstanding',
        kind: 'select',
        options: [
          { value: '', label: 'Any' },
          { value: '1', label: 'Not yet handed over' },
        ],
      },
      {
        name: 'failed',
        label: 'Failed',
        kind: 'select',
        options: [
          { value: '', label: 'Any' },
          { value: '1', label: 'Attempt failed' },
        ],
      },
    ],
    searchPlaceholder: 'Search by reference, consignment, contact or address',
    /*
     * Soonest slot first, so the next collection due is at the top rather than
     * whichever row was created last. The API allows this column to be sorted, and
     * a delivery list is read as a diary.
     */
    defaultSort: 'scheduled_at',
  },

  /* -------------------------------------------------------------------------- */
  /* Charges: priced work against a consignment                                  */
  /* -------------------------------------------------------------------------- */

  charges: {
    key: 'charges',
    path: '/charges',
    title: 'Charges',
    singular: 'charge',
    description:
      'Priced work raised against a consignment. A line is a quote until it goes on an invoice, and it follows the bill from there.',
    viewPermission: 'charge.view',
    writeNamespace: 'charge',
    label: (record) =>
      `${record.description} — ${formatMoney(record.total, record.currency)} (${
        record.shipment?.reference ?? `charge #${record.id}`
      })`,
    relationLabels: (record) => ({
      shipment_id: record.shipment?.reference,
      invoice_id: record.invoice?.reference,
    }),
    relationFields: { shipment: 'shipment_id', invoice: 'invoice_id' },
    columns: [
      {
        key: 'description',
        header: 'Line',
        sortKey: 'description',
        render: (record) => (
          <CellStack
            primary={<span className="font-medium text-ink-900">{record.description}</span>}
            secondary={record.type_label}
          />
        ),
      },
      {
        key: 'shipment',
        header: 'Consignment',
        /*
         * Sorted on the API as `shipment_id`; the reference is nested rather than
         * a column of this table, which is the arrangement the customs and
         * delivery surfaces already use for theirs.
         */
        sortKey: 'shipment_id',
        render: (record) =>
          record.shipment ? (
            <CellStack
              primary={<span className="font-medium text-ink-900">{record.shipment.reference}</span>}
              secondary={record.shipment.status_label}
            />
          ) : (
            <Unassigned />
          ),
      },
      {
        key: 'total',
        header: 'Total',
        sortKey: 'total',
        align: 'right',
        /*
         * One money column, not four. Quantity, rate, tax and subtotal are all in
         * the edit form, and a line list is read for the figure that will be
         * billed - a table of unit rates says nothing about what the month is
         * worth.
         */
        render: (record) => <MoneyCell value={record.total} currency={record.currency} />,
      },
      {
        key: 'status',
        header: 'Status',
        sortKey: 'status',
        render: (record) => (
          <ChargeStatusBadge
            status={record.status}
            label={record.status_label}
            invoiced={record.is_invoiced}
            settled={record.is_settled}
          />
        ),
      },
      {
        key: 'invoice',
        header: 'Invoice',
        sortKey: 'invoice_id',
        render: (record) =>
          record.invoice ? (
            <RelationCell
              primary={record.invoice.reference}
              secondary={record.invoice.status_label ?? undefined}
            />
          ) : (
            /*
             * "Not billed" rather than a dash, because an unbilled line is the
             * normal state of most of this list and is exactly the row somebody
             * opened the screen to find.
             */
            <Badge tone="neutral">Not billed</Badge>
          ),
      },
    ],
    fields: [
      {
        name: 'shipment_id',
        label: 'Consignment',
        kind: 'select',
        required: true,
        reference: SHIPMENT_REFERENCE,
      },
      {
        name: 'description',
        label: 'Description',
        kind: 'text',
        required: true,
        maxLength: 255,
        placeholder: 'Ocean freight, Jebel Ali to Rotterdam',
      },
      { name: 'type', label: 'Type', kind: 'select', options: CHARGE_TYPE_OPTIONS },
      {
        name: 'quantity',
        label: 'Quantity',
        kind: 'decimal',
        step: '0.0001',
        /* Not required: the API defaults it to 1, and the hint says so. */
        hint: 'Defaults to 1.',
      },
      { name: 'unit_price', label: 'Unit price', kind: 'decimal', step: '0.0001', placeholder: '0.00' },
      {
        name: 'tax_rate',
        label: 'Tax rate %',
        kind: 'decimal',
        step: '0.0001',
        min: 0,
        max: 100,
        hint: 'A percentage, so 5 for 5% VAT. 100 is the ceiling.',
      },
      CURRENCY_FIELD,
      {
        name: 'invoice_id',
        label: 'Invoice',
        kind: 'select',
        reference: INVOICE_REFERENCE,
        /*
         * Putting a line on a bill is how a bill is assembled, and the server
         * rewrites that bill's totals and status when this changes - in both
         * directions, so taking a line off a bill puts it back to a quote. Said
         * here because the alternative, a separate "attach lines" screen on the
         * invoice, is the same edit with one more screen in the way.
         */
        hint: 'Putting a line on a bill re-totals it. Clear this to un-bill the line.',
        wide: true,
      },
      {
        name: 'status',
        label: 'Status',
        kind: 'select',
        options: [
          { value: '', label: 'Unchanged' },
          { value: 'void', label: 'Void' },
        ],
        hint: 'Void withdraws the line. Every other status follows the invoice.',
      },
      NOTES_FIELD,
    ],
    filters: [
      { name: 'status', label: 'Status', kind: 'select', options: CHARGE_STATUS_OPTIONS },
      { name: 'type', label: 'Type', kind: 'select', options: CHARGE_TYPE_OPTIONS },
      SHIPMENT_FILTER,
      { name: 'invoice_id', label: 'Invoice', kind: 'select', reference: INVOICE_REFERENCE },
      {
        /*
         * The worklist this screen exists for, and the reason it is a filter
         * rather than a status: "not yet billed" is a null invoice, not a state,
         * and the API answers it as a scope.
         */
        name: 'uninvoiced',
        label: 'Billed',
        kind: 'select',
        options: [
          { value: '', label: 'Any' },
          { value: '1', label: 'Not yet billed' },
        ],
      },
    ],
    searchPlaceholder: 'Search by line or consignment reference',
    /*
     * Newest first: a line list is worked through in the order it was raised, and
     * the API whitelists `created_at`.
     */
    defaultSort: 'created_at',
  },

  /* -------------------------------------------------------------------------- */
  /* Invoices: what a customer has been asked for                                */
  /* -------------------------------------------------------------------------- */

  invoices: {
    key: 'invoices',
    path: '/invoices',
    title: 'Invoices',
    singular: 'invoice',
    description:
      'What the business has asked a customer for. A draft is somebody assembling a figure; issuing it is a commitment that starts the due-date clock.',
    viewPermission: 'invoice.view',
    writeNamespace: 'invoice',
    label: (record) => `${record.reference} — ${record.customer?.name ?? 'no customer on file'}`,
    relationLabels: (record) => ({ customer_id: record.customer?.name }),
    relationFields: { customer: 'customer_id' },
    columns: [
      {
        key: 'reference',
        header: 'Invoice',
        sortKey: 'reference',
        render: (record) => (
          <CellStack
            primary={<span className="font-medium text-ink-900 tabular">{record.reference}</span>}
            secondary={record.customer?.name}
          />
        ),
      },
      {
        key: 'status',
        header: 'Status',
        sortKey: 'status',
        render: (record) => (
          <InvoiceStatusBadge
            status={record.status}
            label={record.status_label}
            overdue={record.is_overdue}
          />
        ),
      },
      {
        key: 'total',
        header: 'Total',
        sortKey: 'total',
        align: 'right',
        render: (record) => <MoneyCell value={record.total} currency={record.currency} />,
      },
      {
        key: 'balance',
        header: 'Balance',
        sortKey: 'balance',
        align: 'right',
        /*
         * The balance, with the total under it as context. On a receivables list
         * the balance is the figure that gets chased and the total is the one that
         * gets argued about, and one column cannot be both - so the second line is
         * always present rather than only on the part-paid rows, where a bare
         * balance reads as a mistake.
         */
        render: (record) => (
          <CellStack
            primary={<MoneyCell value={record.balance} currency={record.currency} />}
            secondary={`of ${formatMoney(record.total, record.currency)}`}
          />
        ),
      },
      {
        key: 'due_date',
        header: 'Due',
        sortKey: 'due_date',
        render: (record) => <DueDateCell value={record.due_date} overdue={record.is_overdue} />,
      },
      {
        key: 'issue_date',
        header: 'Issued',
        sortKey: 'issue_date',
        render: (record) =>
          record.issue_date ? (
            <span className="tabular">{formatDate(record.issue_date)}</span>
          ) : (
            /*
             * A draft has no issue date and that is not a missing value - the date
             * is what issuing stamps. Naming the state stops the column reading as
             * a bill somebody forgot to send.
             */
            <Badge tone="neutral">Not issued</Badge>
          ),
      },
    ],
    fields: [
      {
        name: 'customer_id',
        label: 'Customer',
        kind: 'select',
        required: true,
        reference: CUSTOMER_REFERENCE,
      },
      REFERENCE_FIELD,
      {
        name: 'status',
        label: 'Status',
        kind: 'select',
        options: [
          { value: '', label: 'Unchanged' },
          { value: 'draft', label: 'Draft' },
          { value: 'void', label: 'Void' },
        ],
        /*
         * No "issued" option, and that is the point of the whole row: sending a
         * bill is an act with a zero-total check attached, so it is the Issue
         * action on the row and not a value in a dropdown.
         */
        hint: 'An invoice is sent with the Issue action, not set here.',
      },
      { name: 'issue_date', label: 'Issue date', kind: 'date', hint: 'Stamped when the invoice is issued.' },
      {
        name: 'due_date',
        label: 'Due date',
        kind: 'date',
        hint: 'Leave empty for no deadline. An invoice with no due date is never late.',
      },
      CURRENCY_FIELD,
      NOTES_FIELD,
    ],
    filters: [
      { name: 'status', label: 'Status', kind: 'select', options: INVOICE_STATUS_OPTIONS },
      { name: 'customer_id', label: 'Customer', kind: 'select', reference: CUSTOMER_REFERENCE },
      {
        name: 'outstanding',
        label: 'Money',
        kind: 'select',
        options: [
          { value: '', label: 'Any' },
          { value: '1', label: 'Still owing' },
        ],
      },
    ],
    searchPlaceholder: 'Search by invoice number or customer',
    /*
     * Soonest due first. An invoice list is a diary, and the API whitelists
     * `due_date`; the consequence accepted here is that drafts with no deadline
     * sort together at one end, which is where a bill nobody has dated belongs.
     */
    defaultSort: 'due_date',
    /*
     * Only a draft can be written to, and that is the server's `is_editable` read
     * back rather than a guess from the status - an issued invoice is refused by
     * both PATCH and DELETE, so offering either button is a dead end.
     */
    canModify: (record) => record.is_editable,
    /*
     * The two lifecycle actions, offered on the states where they mean something.
     *
     * `issue` is on a draft only: the endpoint is a 409 on anything else, and a
     * button that is reliably refused is worse than an absent one. `void` is the
     * opposite - it is allowed from any state, including part-paid, because a
     * write-off is a decision somebody has to be able to make, and it is confirmed
     * because the balance stays put and the customer still holds the document.
     */
    rowActions: (record) => {
      const actions: RowActionDef[] = []

      if (record.status === 'draft') {
        actions.push({
          key: 'issue',
          label: 'Issue invoice',
          icon: <Send className="size-4" aria-hidden />,
          permission: 'invoice.update',
          endpoint: 'issue',
          successLabel: 'issued',
          confirm: 'Issue this invoice? The due-date clock starts today and the invoice can no longer be edited.',
          confirmLabel: 'Issue',
        })
      }

      if (record.status !== 'void') {
        actions.push({
          key: 'void',
          label: 'Void invoice',
          icon: <Ban className="size-4" aria-hidden />,
          permission: 'invoice.update',
          endpoint: 'void',
          successLabel: 'voided',
          destructive: true,
          confirm: 'Void this invoice? The balance stays on the record as a write-off and the charges become drafts again.',
          confirmLabel: 'Void',
        })
      }

      return actions
    },
  },

  /* -------------------------------------------------------------------------- */
  /* Payments: money received, and money returned                                */
  /* -------------------------------------------------------------------------- */

  payments: {
    key: 'payments',
    path: '/payments',
    title: 'Payments',
    singular: 'payment',
    description:
      'What came in against a bill, and what went back out. A credit note is a payment row too, which is why both appear here.',
    viewPermission: 'payment.view',
    writeNamespace: 'payment',
    label: (record) =>
      `${record.reference} — ${formatMoney(record.amount)} on ${
        record.invoice?.reference ?? 'an invoice no longer on file'
      }`,
    relationLabels: (record) => ({ invoice_id: record.invoice?.reference }),
    relationFields: { invoice: 'invoice_id' },
    columns: [
      {
        key: 'reference',
        header: 'Receipt',
        sortKey: 'reference',
        render: (record) => (
          <CellStack
            primary={<span className="font-medium text-ink-900 tabular">{record.reference}</span>}
            secondary={record.bank_reference ?? undefined}
          />
        ),
      },
      {
        key: 'invoice',
        header: 'Invoice',
        /*
         * No `sortKey`. `PaymentController::sortable()` does not whitelist
         * `invoice_id`, and a header that looks sortable but silently is not is
         * worse than a plain one.
         */
        render: (record) =>
          record.invoice ? (
            <CellStack primary={record.invoice.reference} secondary={record.invoice.customer?.name} />
          ) : (
            <Unassigned />
          ),
      },
      {
        key: 'amount',
        header: 'Amount',
        sortKey: 'amount',
        align: 'right',
        /*
         * Signed, and coloured by direction. A credit note is stored as a positive
         * amount and subtracted by the server, so an unsigned column would show a
         * refund among the incoming money looking exactly like a large receipt.
         * No currency: a payment has none of its own - it is denominated in its
         * invoice's currency, and showing one here would imply it can differ.
         */
        render: (record) => (
          <PaymentAmountCell value={record.amount} isReceipt={record.is_receipt} />
        ),
      },
      {
        key: 'method',
        header: 'Method',
        sortKey: 'method',
        render: (record) => (
          <PaymentMethodBadge
            method={record.method}
            label={record.method_label}
            isReceipt={record.is_receipt}
          />
        ),
      },
      {
        key: 'paid_at',
        header: 'Received',
        sortKey: 'paid_at',
        /*
         * A full timestamp rather than a date: a bank transfer that lands at 09:40
         * and one that lands at 16:55 are the same day and a different story, and
         * this is the column a bank reconciliation is read from.
         */
        render: (record) =>
          record.paid_at ? <span className="tabular">{formatDateTime(record.paid_at)}</span> : <Unassigned />,
      },
      {
        key: 'reconciliation',
        header: 'Cleared',
        render: (record) =>
          /*
           * Only ever "Pending" or nothing. A settled row needs no badge: the
           * absence of one is the good news, and a second green tick on every
           * cleared receipt would be colour spent on nothing.
           */
          record.needs_reconciliation ? <Badge tone="warning">Awaiting clearance</Badge> : <Text value={null} />,
      },
    ],
    fields: [
      {
        name: 'invoice_id',
        label: 'Invoice',
        kind: 'select',
        required: true,
        reference: INVOICE_REFERENCE,
        wide: true,
      },
      REFERENCE_FIELD,
      { name: 'amount', label: 'Amount', kind: 'decimal', required: true, step: '0.0001', placeholder: '0.00' },
      {
        name: 'method',
        label: 'Method',
        kind: 'select',
        required: true,
        options: PAYMENT_METHOD_OPTIONS,
        /*
         * The direction is a consequence of the method, not a separate field: the
         * server reads the same six values to decide whether this row adds to what
         * has been received or subtracts from it. Stated here so nobody enters a
         * credit note as a transfer and then wonders why the balance went up.
         */
        hint: 'An adjustment is a credit note: it is subtracted from what has been received, so the balance goes up.',
        wide: true,
      },
      {
        name: 'paid_at',
        label: 'Received at',
        kind: 'datetime',
        hint: 'Defaults to now. The value date, not the keying date.',
      },
      { name: 'bank_reference', label: 'Bank reference', kind: 'text', maxLength: 120 },
      NOTES_FIELD,
    ],
    filters: [
      { name: 'method', label: 'Method', kind: 'select', options: PAYMENT_METHOD_OPTIONS },
      { name: 'invoice_id', label: 'Invoice', kind: 'select', reference: INVOICE_REFERENCE },
      /*
       * The payer, not the invoice. A bank statement is read account by account
       * rather than bill by bill, so "everything this customer paid us" is asked
       * far more often here than it is on the invoices screen.
       */
      { name: 'customer_id', label: 'Customer', kind: 'select', reference: CUSTOMER_REFERENCE },
      {
        name: 'needs_reconciliation',
        label: 'Cleared',
        kind: 'select',
        options: [
          { value: '', label: 'Any' },
          { value: '1', label: 'Awaiting clearance' },
        ],
      },
    ],
    searchPlaceholder: 'Search by receipt, bank or invoice reference',
    /*
     * Newest first. A cash book is read as a diary, and the API whitelists
     * `paid_at`, which is the date the money moved rather than the row's id.
     */
    defaultSort: 'paid_at',
  },

  /* -------------------------------------------------------------------------- */
  /* Expenses: what the business paid out                                        */
  /* -------------------------------------------------------------------------- */

  expenses: {
    key: 'expenses',
    path: '/expenses',
    title: 'Expenses',
    singular: 'expense',
    description:
      'Costs the business has incurred. A billable one is recharged to the customer and comes off the job’s margin; duty and tax is the exception, because it is collected and handed on rather than spent.',
    viewPermission: 'expense.view',
    writeNamespace: 'expense',
    label: (record) =>
      `${record.reference} — ${record.vendor_name} ${formatMoney(record.amount, record.currency)}`,
    relationLabels: (record) => ({
      shipment_id: record.shipment?.reference,
      booking_id: record.booking?.reference,
      carrier_id: record.carrier?.name,
    }),
    relationFields: { shipment: 'shipment_id', booking: 'booking_id', carrier: 'carrier_id' },
    columns: [
      {
        key: 'reference',
        header: 'Expense',
        sortKey: 'reference',
        render: (record) => (
          <CellStack
            primary={<span className="font-medium text-ink-900 tabular">{record.reference}</span>}
            secondary={record.vendor_name}
          />
        ),
      },
      {
        key: 'category',
        header: 'Category',
        sortKey: 'category',
        render: (record) => (
          <ExpenseCategoryBadge
            category={record.category}
            label={record.category_label}
            billable={record.is_billable}
            countsAgainstMargin={record.counts_against_margin}
          />
        ),
      },
      {
        key: 'amount',
        header: 'Amount',
        sortKey: 'amount',
        align: 'right',
        render: (record) => <MoneyCell value={record.amount} currency={record.currency} />,
      },
      {
        key: 'margin_amount',
        header: 'Margin impact',
        align: 'right',
        /*
         * What the cost did to the job's margin, which is not the amount and not
         * zero either. Duty and tax is the row where the two differ, and it is
         * exactly the row a finance officer is checking - so the column states the
         * figure the report is built on rather than leaving them to re-derive the
         * rule from a category name.
         */
        render: (record) =>
          record.counts_against_margin ? (
            <MoneyCell value={record.margin_amount} currency={record.currency} />
          ) : (
            <span className="block text-right text-ink-300">—</span>
          ),
      },
      {
        key: 'shipment',
        header: 'Job',
        sortKey: 'shipment_id',
        /*
         * One column for all three references, because a cost is attributed to
         * whichever of them it has and the row is still "ours" when it has none -
         * rent and salaries belong to no consignment and are real rows, not missing
         * data. The consignment leads where there is one, since it is the most
         * specific answer.
         */
        render: (record) =>
          record.shipment || record.booking || record.carrier ? (
            <CellStack
              primary={
                record.shipment?.reference ?? record.booking?.reference ?? record.carrier?.code ?? ''
              }
              secondary={
                record.shipment
                  ? record.shipment.status_label
                  : record.carrier?.name ?? record.booking?.reference
              }
            />
          ) : (
            <Unassigned />
          ),
      },
      {
        key: 'incurred_on',
        header: 'Incurred',
        sortKey: 'incurred_on',
        render: (record) =>
          record.incurred_on ? (
            <span className="tabular">{formatDate(record.incurred_on)}</span>
          ) : (
            <Unassigned />
          ),
      },
    ],
    fields: [
      REFERENCE_FIELD,
      { name: 'vendor_name', label: 'Vendor', kind: 'text', maxLength: 255, placeholder: 'Maersk' },
      {
        name: 'vendor_reference',
        label: 'Vendor reference',
        kind: 'text',
        maxLength: 120,
        /*
         * Not unique, and the hint says so, because a monthly carrier statement
         * carries the same number on a dozen lines. The registry's other reference
         * fields are all unique and a field that repeats without saying why is
         * indistinguishable from a bug.
         */
        hint: 'The vendor’s own number. Not unique: one statement number covers many lines.',
      },
      { name: 'category', label: 'Category', kind: 'select', required: true, options: EXPENSE_CATEGORY_OPTIONS },
      { name: 'amount', label: 'Amount', kind: 'decimal', required: true, step: '0.0001', placeholder: '0.00' },
      CURRENCY_FIELD,
      {
        name: 'incurred_on',
        label: 'Incurred on',
        kind: 'date',
        hint: 'The day the cost was incurred, not the day it was keyed in. Defaults to today.',
      },
      {
        name: 'is_billable',
        label: 'Billable to customer',
        kind: 'checkbox',
        hint: 'Duty and tax is never recharged, whatever this says.',
      },
      {
        name: 'shipment_id',
        label: 'Consignment',
        kind: 'select',
        reference: SHIPMENT_REFERENCE,
      },
      { name: 'booking_id', label: 'Booking', kind: 'select', reference: BOOKING_REFERENCE },
      { name: 'carrier_id', label: 'Carrier', kind: 'select', reference: CARRIER_REFERENCE },
      {
        name: 'description',
        label: 'Description',
        kind: 'textarea',
        maxLength: 2000,
        wide: true,
        placeholder: 'Terminal handling for the week ending 12 Sep.',
      },
      NOTES_FIELD,
    ],
    filters: [
      { name: 'category', label: 'Category', kind: 'select', options: EXPENSE_CATEGORY_OPTIONS },
      SHIPMENT_FILTER,
      CARRIER_FILTER,
      {
        /*
         * The two figures a cost lands in, as a choice rather than a default.
         *
         * Margin excludes duty and tax because that money was collected from the
         * customer and handed to an authority; total spend includes it because it
         * genuinely left the bank. Defaulting either one means somebody reading the
         * other is right by accident.
         */
        name: 'margin_bearing',
        label: 'Counts as',
        kind: 'select',
        options: [
          { value: '', label: 'Margin and spend' },
          { value: '1', label: 'Margin only' },
        ],
      },
      {
        name: 'billable',
        label: 'Billable',
        kind: 'select',
        options: [
          { value: '', label: 'Any' },
          { value: '1', label: 'Recharged to customer' },
        ],
      },
      {
        name: 'unattributed',
        label: 'Attribution',
        kind: 'select',
        options: [
          { value: '', label: 'Any' },
          { value: '1', label: 'No job behind it' },
        ],
      },
    ],
    searchPlaceholder: 'Search by reference, vendor, consignment or carrier',
    /*
     * Most recent cost first, on the day it was incurred rather than the day it
     * was keyed in - a month-end margin report is read by the month the cost
     * belongs to, and the API whitelists `incurred_on` for exactly that reason.
     */
    defaultSort: 'incurred_on',
  },

  /* -------------------------------------------------------------------------- */
  /* Documents: the paperwork the business holds                                 */
  /* -------------------------------------------------------------------------- */

  documents: {
    key: 'documents',
    path: '/documents',
    title: 'Documents',
    singular: 'document',
    description:
      'Paperwork on file: bills of lading, invoices, certificates and declarations, filed against a consignment where there is one and against the business where there is not.',
    viewPermission: 'document.view',
    writeNamespace: 'document',
    /*
     * The title is the label because in a document register the title is what the
     * row *is* - "Maersk Bill of Lading 47 copies" - while the reference is the
     * third party's own number, which is frequently blank and never unique. A
     * counterparty reference is the identity of a consignment or an invoice; here
     * it is an attribute that happens to be useful when it exists.
     */
    label: (record) => record.title,
    relationLabels: (record) => ({
      shipment_id: record.shipment?.reference,
    }),
    relationFields: { shipment: 'shipment_id' },
    columns: [
      {
        key: 'title',
        header: 'Document',
        sortKey: 'title',
        render: (record) => (
          <CellStack
            primary={<span className="font-medium text-ink-900">{record.title}</span>}
            secondary={record.reference ? <span className="tabular">{record.reference}</span> : null}
          />
        ),
      },
      {
        key: 'document_type',
        header: 'Type',
        sortKey: 'document_type',
        render: (record) =>
          record.document_type ? (
            <Badge tone="neutral">{record.document_type_label ?? humanise(record.document_type)}</Badge>
          ) : (
            <Unassigned />
          ),
      },
      {
        key: 'file',
        header: 'File',
        /*
         * Unsorted on purpose. The API whitelists sorts by the document's own
         * fields, and the interesting ordering here is "most urgent first", which
         * is a question about a date plus two computed flags rather than a column -
         * so it is answered by the expiry column and by the filters.
         */
        render: (record) =>
          record.file ? (
            <DocumentFileCell
              name={record.file.name}
              version={record.file.version}
              size={record.file.size_bytes}
            />
          ) : (
            /*
             * Reachable, and not a failure of this screen. Deleting a file off the
             * disk leaves its row pointing at nothing, and the API answers a
             * download with a 409 that says so in words. Showing the document as if
             * it were fine would be the one thing worse than showing the gap.
             */
            <span className="text-danger-600">File missing</span>
          ),
      },
      {
        key: 'shipment',
        header: 'Consignment',
        sortKey: 'shipment_id',
        render: (record) =>
          record.shipment ? (
            <CellStack primary={<span className="tabular">{record.shipment.reference}</span>} secondary={record.shipment.status_label} />
          ) : (
            /*
             * "Company-wide" rather than a dash, because it is not missing data. The
             * fleet insurance certificate belongs to no consignment and the register
             * is wrong to imply otherwise; and a dash here would be the same glyph a
             * genuinely absent value gets, so the two would be indistinguishable.
             */
            <span className="text-ink-400">Company-wide</span>
          ),
      },
      {
        key: 'expires_on',
        header: 'Expires',
        sortKey: 'expires_on',
        render: (record) => (
          <DocumentExpiryCell
            value={record.expires_on}
            isExpired={record.is_expired}
            needsAttention={record.needs_attention}
          />
        ),
      },
      {
        key: 'version_count',
        header: 'Versions',
        align: 'right',
        /*
         * The count rather than the current version's number, because "v3" alone
         * invites the assumption that nothing came before it and invites the wrong
         * reaction when a download gives you v3 of 3.
         */
        render: (record) => (
          <span className="tabular text-ink-500">{formatNumber(record.version_count)}</span>
        ),
      },
    ],
    fields: [
      {
        name: 'title',
        label: 'Title',
        kind: 'text',
        required: true,
        maxLength: 255,
        placeholder: 'Maersk Bill of Lading — Shanghai to Rotterdam',
      },
      {
        name: 'document_type',
        label: 'Type',
        kind: 'select',
        required: true,
        options: DOCUMENT_TYPE_OPTIONS,
        hint: 'Which kind of paper this is. Filing it wrongly is the one mistake that cannot be fixed by downloading it again.',
      },
      {
        name: 'reference',
        label: 'Reference',
        kind: 'text',
        maxLength: 120,
        hint: 'The number printed on the document. Optional, and not unique — one number can cover several filings.',
      },
      {
        name: 'issued_on',
        label: 'Issued on',
        kind: 'date',
      },
      {
        name: 'expires_on',
        label: 'Expires on',
        kind: 'date',
        /*
         * "Optional" is doing real work in that label. Plenty of this paperwork
         * never expires, and forcing a date on a bill of lading would mean
         * inventing one and then nagging about it forever.
         */
        hint: 'Leave empty if it does not expire. Anything within 30 days is flagged.',
      },
      {
        name: 'shipment_id',
        label: 'Consignment',
        kind: 'select',
        /*
         * The same reference the three operations modules use, and for the same
         * reason: a document is filed against a consignment long after it closed, so
         * hiding closed ones would make the paperwork of a finished job unrecordable.
         *
         * The field is what differs, being optional rather than required - so the
         * dropdown offers a real "not set" instead of forcing an answer. Nothing
         * about the reference itself needs its own definition to express that.
         */
        reference: SHIPMENT_REFERENCE,
        hint: 'Optional. Leave empty for the business’s own certificates and accounts.',
      },
      {
        name: 'file',
        label: 'File',
        kind: 'file',
        required: true,
        accept: DOCUMENT_ACCEPT,
        wide: true,
        /*
         * Required on create and optional on amend, and the form handles that by
         * reading the record rather than by a field saying "required": an amended
         * document that changes its expiry date and re-uploads nothing is an ordinary
         * correction, and a form that demanded a file would make it impossible.
         */
        hint: 'PDF, an image, or an Office document, up to 20 MB. Uploading a new file keeps the old one as a version.',
      },
      NOTES_FIELD,
    ],
    filters: [
      { name: 'type', label: 'Type', kind: 'select', options: DOCUMENT_TYPE_OPTIONS },
      SHIPMENT_FILTER,
      {
        /*
         * Two filters rather than one "needs attention" dropdown, because the two
         * questions are not nested: a document can be expired without ever having
         * been close to expiring, and only the second of these two answers can be
         * acted on by renewing. Collapsing them would leave a lapsed insurance
         * certificate indistinguishable from one that was simply due soon.
         *
         * The window is the server's `expiry_warning_days`, not a constant here -
         * that is why the row carries `needs_attention` rather than leaving this
         * filter to compare dates in the browser.
         */
        name: 'expiring',
        label: 'Expiring',
        kind: 'select',
        options: [
          { value: '', label: 'Any' },
          { value: '1', label: 'Within the warning window' },
        ],
      },
      {
        name: 'expired',
        label: 'Expired',
        kind: 'select',
        options: [
          { value: '', label: 'Any' },
          { value: '1', label: 'Already lapsed' },
        ],
      },
      {
        name: 'company_wide',
        label: 'Filed against',
        kind: 'select',
        options: [
          { value: '', label: 'Any' },
          { value: '1', label: 'The business, not a consignment' },
        ],
      },
    ],
    searchPlaceholder: 'Search by title, reference, type or consignment',
    /*
     * By title rather than by upload time. A register is consulted by what a thing
     * is, and the row that comes back from "Maersk" should be the Maersk bill of
     * lading whether it was filed this morning or three years ago.
     */
    defaultSort: 'title',
    rowActions: (record) => [
      /*
       * The download action carries its own permission, which is not the permission
       * that lets you see the row. Being able to read "Maersk Bill of Lading" is
       * not the same authority as being able to take the file away, and the API
       * enforces the difference - so the button does too, and is absent rather than
       * disabled for a viewer who cannot use it.
       *
       * The path comes from `download_url`, which the API publishes rather than the
       * client assembling, so the storage route cannot drift from the link here
       * without the endpoint moving with it.
       */
      {
        key: 'download',
        label: 'Download',
        icon: <Download size={14} />,
        permission: 'document.download',
        href: record.download_url,
        external: true,
      },
    ],
  },
}

/**
 * The ten lookup tables, in navigation order.
 *
 * Listed rather than derived from `Object.keys(SURFACES)`, because the registry
 * also holds shipments and the sidebar's Master Data section must not gain a
 * consignment list. Everything a module needs is derived from this list and the
 * registry - the label, the URL and the permission - so a surface added here
 * cannot appear in one place and not the other.
 */
export const MASTER_SURFACE_KEYS = [
  'customers',
  'shippers',
  'consignees',
  'carriers',
  'vessels',
  'ports',
  'warehouses',
  'factories',
  'trucks',
  'drivers',
] as const satisfies readonly MasterSurfaceKey[]

/** Every surface, in registry order. */
export const SURFACE_KEYS = Object.keys(SURFACES) as SurfaceKey[]

