/**
 * Master-data record types.
 *
 * These mirror the resources in `backend/app/Http/Resources`. `get` and
 * `getPaginated` strip the response envelope, so a `Party` here is the
 * resource's own `toArray()` output and nothing else.
 *
 * Nullability is taken from the migration columns and the model casts, not
 * guessed: `ports.latitude` is cast to float so it arrives as a number, and a
 * driver's expiry dates are emitted as plain `Y-m-d` rather than ISO-8601.
 *
 * The file is named for master data but is the home of every record type the
 * generic screens serve. Shipments are not master data - the module moved, so it
 * has a status rather than an `is_active` flag - and putting it in a sibling
 * file would have meant the shared registry had to import from two places to
 * describe one page.
 */

export type PartyType = 'customer' | 'shipper' | 'consignee'
export type SiteKind = 'warehouse' | 'factory'
export type CarrierMode = 'sea' | 'air' | 'road' | 'rail' | 'multimodal'
export type TruckType = 'tractor' | 'rigid' | 'box_truck' | 'flatbed' | 'reefer' | 'tanker'

/**
 * The lifecycle a consignment moves through, and the closed states.
 *
 * Mirrors `backend/app/Enums/ShipmentStatus`. A vocabulary, not a workflow: the
 * API does not validate the order, and `isClosed` is the pair that matters to
 * every screen - it is what `open_only` filters on and what decides whether a
 * past ETA counts as late.
 */
export type ShipmentStatus =
  | 'draft'
  | 'booked'
  | 'in_transit'
  | 'customs'
  | 'arrived'
  | 'delivered'
  | 'cancelled'

/**
 * The lifecycle a booking moves through.
 *
 * Mirrors `backend/app/Enums/BookingStatus`. `isClosed` marks the two states
 * that mean "no more work" for a request, and `canProduceShipment` only ever
 * says "confirmed" - advice to the UI, not something the API enforces.
 */
export type BookingStatus = 'draft' | 'requested' | 'confirmed' | 'cancelled' | 'expired'

/** Incoterms 2020. Mirrors `backend/app/Enums/Incoterm`. */
export type Incoterm = 'EXW' | 'FCA' | 'FAS' | 'FOB' | 'CIF' | 'CFR' | 'CIP' | 'CPT' | 'DAP' | 'DPU' | 'DDP'

/** The six flat address columns, presented nested under `address`. */
export interface Address {
  line_1: string | null
  line_2: string | null
  city: string | null
  state: string | null
  postal_code: string | null
  country_code: string | null
}

/**
 * Every master-data record carries these.
 *
 * `is_active` lives here and not on the shared shape below, deliberately: a
 * consignment is active or it is not, but the column that says so is `status`,
 * and a shared base that included the flag would make `is_active` look available
 * on every screen. It is opt-in per surface instead - the master-data surfaces
 * add an Active column, shipments add a status one.
 */
export interface Timestamped {
  id: number
  is_active: boolean
  notes: string | null
  created_at: string | null
  updated_at: string | null
}

/**
 * The minimum a record needs for the shared list and form machinery to work.
 *
 * This is the constraint the registry and the page are generic over, and it is
 * smaller than `Timestamped` on purpose. A generic page needs an id for the row
 * key and for the write endpoints, and `notes` because the notes textarea is
 * optional but has to behave the same everywhere. Everything else - `is_active`,
 * the address block, the relations - is per-module and belongs in the surface
 * definition, so a column reaching for a field this record does not have stays a
 * compile error.
 */
export interface AnyRecord {
  id: number
  notes: string | null
  created_at: string | null
  updated_at: string | null
}

/** The named-contact block shared by parties, carriers and sites. */
export interface Contacted {
  contact_name: string | null
  contact_email: string | null
  contact_phone: string | null
}

export interface Addressed extends Timestamped, Contacted {
  address: Address
}

export interface Party extends Addressed {
  /** Server-owned: the route decides this, the client never sends it. */
  type: PartyType
  code: string
  name: string
  tax_number: string | null
}

export interface Carrier extends Addressed {
  code: string
  name: string
  scac: string | null
  type: CarrierMode | null
}

export interface Vessel extends Timestamped {
  name: string
  imo_number: string
  mmsi: string | null
  flag_country: string | null
  vessel_type: string | null
  length_meters: number | null
  beam_meters: number | null
  capacity_teu: number | null
}

export interface Port extends Timestamped {
  un_locode: string
  name: string
  country_code: string
  port_type: string | null
  latitude: number | null
  longitude: number | null
  timezone: string | null
}

export interface Site extends Addressed {
  /** Server-owned: the route decides this, the client never sends it. */
  kind: SiteKind
  code: string
  name: string
  /** Warehouse-only. The API rejects it with `prohibited` on a factory. */
  site_type: string | null
  capacity_pallets: number | null
}

/**
 * A relation reduced to an id and a label, not a nested resource.
 *
 * The API shapes each reference by hand so a paged list does not drag a
 * carrier's trucks, a truck's driver and a port's history into every row. What
 * counts as "a label" differs per entity - a truck is named by its plate, a port
 * by its UN/LOCODE - so the reduction is declared per reference type rather than
 * normalised into one.
 */
export interface CodeNameRef {
  id: number
  code: string
  name: string
}

export type PartyRef = CodeNameRef

export type CarrierRef = CodeNameRef

export interface TruckRef {
  id: number
  plate_number: string
}

export interface VesselRef {
  id: number
  name: string
  imo_number: string
}

export interface PortRef {
  id: number
  un_locode: string
  name: string
}

export type SiteRef = CodeNameRef

export type DriverRef = CodeNameRef

/**
 * A booking as a shipment sees it.
 *
 * Reference and status only. The link answers "which request did this come
 * from", and a shipment row is not the place to read a booking's routing and
 * cargo - that is the booking's own screen. Declared here so the shipment
 * resource's `booking` key has a type to point at.
 */
export interface BookingRef {
  id: number
  reference: string
  status: BookingStatus
}

export interface Truck extends Timestamped {
  plate_number: string
  code: string | null
  carrier: CarrierRef | null
  truck_type: TruckType | null
  capacity_kg: number | null
  capacity_pallets: number | null
  make: string | null
  model: string | null
  /**
   * A `string(4)` column with no model cast, so what arrives depends on the
   * driver: MySQL's VARCHAR always yields `'2021'`, while SQLite returns whatever
   * was stored and a seeder writing `2021` yields the number. Both are rendered
   * by a text input, which stringifies on the way in and sends it back as text,
   * so the difference is invisible - but the type has to admit both rather than
   * pretend one of them is guaranteed.
   */
  year: string | number | null
}

export interface Driver extends Timestamped {
  code: string
  name: string
  license_number: string | null
  phone: string | null
  email: string | null
  nationality: string | null
  carrier: CarrierRef | null
  truck: TruckRef | null
  /** `Y-m-d`. */
  license_expiry: string | null
  /** `Y-m-d`. */
  medical_expiry: string | null
}

/** A record of any kind, for code that genuinely spans surfaces. */
export type MasterRecord = Party | Carrier | Vessel | Port | Site | Truck | Driver

/**
 * A consignment.
 *
 * The first non-master-data record in this file, and the differences are the
 * point: no `is_active` but a `status`, and eleven nullable relations where a
 * carrier has one. Every partner is optional because a draft is normally saved
 * before the paperwork exists, so the form has to be able to express "not known
 * yet" on every one of them.
 *
 * `status_label`, `is_closed` and `is_late` are server-computed rather than
 * derived in the client. They are answers to questions the API can answer
 * consistently - is this overdue, is this finished - and a list, a toast and a
 * report must not each reach their own conclusion.
 */
export interface Shipment extends AnyRecord {
  reference: string
  status: ShipmentStatus
  status_label: string
  is_closed: boolean
  booking?: BookingRef | null
  shipper: PartyRef | null
  consignee: PartyRef | null
  carrier: CarrierRef | null
  vessel: VesselRef | null
  port_of_loading: PortRef | null
  port_of_discharge: PortRef | null
  origin_site: SiteRef | null
  destination_site: SiteRef | null
  truck: TruckRef | null
  driver: DriverRef | null
  incoterm: Incoterm | null
  incoterm_place: string | null
  cargo_description: string | null
  pieces: number | null
  weight_kg: number | null
  /**
   * A `decimal(10,3)`, which PHP hands back as a string.
   *
   * Typed as a string on purpose: a float would have lost the trailing zeros, so
   * a form reading `8.5` back into a three-decimal field would send `8.5` where
   * the column means `8.500`, and the two would not compare equal.
   */
  volume_cbm: string | null
  /** `Y-m-d`. A date, not an instant: a booking confirmation has no time on it. */
  etd: string | null
  /** `Y-m-d`. */
  eta: string | null
  /** ISO-8601. An actual event has a time, which is the fact being recorded. */
  actual_departure: string | null
  actual_arrival: string | null
  /** Past its ETA and not yet closed. */
  is_late: boolean
}

/**
 * A request for space with a carrier, and the answer that came back.
 *
 * Mirrors the backend resource. The shape deliberately resembles a shipment:
 * same partners, routing and cargo. What differs is the lifecycle (`requested`
 * and `confirmed` instead of `in_transit` and `arrived`) and the dates being the
 * requested ones. `status_label` and `is_closed` are server-computed so the UI
 * does not need to interpret the enum.
 */
export interface Booking extends AnyRecord {
  reference: string
  status: BookingStatus
  status_label: string
  is_closed: boolean
  shipper: PartyRef | null
  consignee: PartyRef | null
  carrier: CarrierRef | null
  vessel: VesselRef | null
  port_of_loading: PortRef | null
  port_of_discharge: PortRef | null
  origin_site: SiteRef | null
  destination_site: SiteRef | null
  incoterm: Incoterm | null
  incoterm_place: string | null
  cargo_description: string | null
  pieces: number | null
  weight_kg: number | null
  /**
   * A `decimal(10,3)`, string for the same reason as `volume_cbm` on Shipment:
   * trailing zeros must be preserved when round-tripping.
   */
  volume_cbm: string | null
  /** `Y-m-d`. */
  requested_etd: string | null
  /** `Y-m-d`. */
  requested_eta: string | null
}

/**
 * The milestones a carrier can report, from the API's point of view.
 *
 * Mirrors `backend/app/Enums/TrackingMilestone`. These are reported states, not
 * the consignment's own lifecycle: `shipments.status` is where the consignment
 * has got to, and a tracking milestone is what somebody last heard. They overlap
 * deliberately - `delivered` is both - because the consignment status is ours to
 * decide and the milestone is the carrier's claim.
 *
 * `is_problem` arrives from the server rather than being listed here, so the two
 * states that count as problems are defined once, in the enum.
 */
export type TrackingMilestone =
  | 'booked'
  | 'gate_in'
  | 'departed'
  | 'transshipment'
  | 'arrived'
  | 'customs_hold'
  | 'customs_cleared'
  | 'out_for_delivery'
  | 'delivered'
  | 'exception'

/**
 * The consignment a tracking row belongs to, reduced.
 *
 * A worklist row that only said "customs hold" would send the operator to the
 * consignment to find out which one, so the reference, status, carrier and parties
 * come along - still far short of a full `Shipment`, which would drag a vessel and
 * eleven other relations onto every row of a paged list.
 */
export interface TrackingShipmentRef {
  id: number
  reference: string
  status: ShipmentStatus
  status_label: string
  is_closed: boolean
  is_late: boolean
  /** `Y-m-d`. The planned arrival, for a revised date to be read against. */
  eta: string | null
  carrier: CarrierRef | null
  shipper: PartyRef | null
  consignee: PartyRef | null
  port_of_discharge: PortRef | null
}

/**
 * What we last heard about a consignment.
 *
 * One of these exists for every consignment, and unlike every other record in
 * this file it cannot be created or deleted: the API grants `tracking.view` and
 * `tracking.update` and nothing else, because the row is provisioned by the
 * consignment rather than by a person. A shipment created without one is a bug
 * the API is shaped to make impossible.
 *
 * There is no status of its own. Everything nullable is nullable because a
 * consignment nobody has heard from yet is the normal state of a new one, and
 * requiring a milestone to record one would mean inventing a carrier report.
 *
 * `is_delayed`, `is_unreported` and `is_stale` are server-computed for the same
 * reason a shipment's `is_late` is: they are answers the API can give
 * consistently, and the worklist filter, the row badge and any later report must
 * not each reach their own conclusion about the same three-day rule.
 */
export interface ShipmentTracking extends AnyRecord {
  /** Optional, because the resource only emits it when the relation is loaded. */
  shipment?: TrackingShipmentRef | null
  last_milestone: TrackingMilestone | null
  last_milestone_label: string | null
  is_problem: boolean
  current_location: string | null
  /** ISO-8601. A report is an event, so it has a time on it. */
  last_reported_at: string | null
  /**
   * What the carrier has since promised, beside the consignment's own `eta`
   * rather than over it. `Y-m-d`.
   */
  revised_eta: string | null
  delay_reason: string | null
  /** The carrier's own name for the feed, e.g. `EDI` or a portal's title. */
  source: string | null
  is_delayed: boolean
  is_unreported: boolean
  is_stale: boolean
}

/**
 * Where a piece of equipment is in its journey.
 *
 * Mirrors `backend/app/Enums/ContainerStatus`. A vocabulary, not a workflow, and
 * the statuses overlap the consignment's own on purpose: a box can be at customs
 * while the consignment is recorded as in transit, because the two are updated by
 * different people and the disagreement is itself the finding.
 */
export type ContainerStatus =
  | 'empty'
  | 'loaded'
  | 'in_transit'
  | 'customs_hold'
  | 'released'
  | 'delivered'
  | 'damaged'

/**
 * Where a customs filing stands with the authority.
 *
 * Mirrors `backend/app/Enums/CustomsStatus`. The interesting boundary is
 * `approved` against `completed`: a filing is cleared on paper when it is approved
 * and finished only when it is completed, so the cargo has been released to be
 * collected for a step that the row does not yet call done.
 */
export type CustomsStatus =
  | 'pending'
  | 'submitted'
  | 'under_review'
  | 'approved'
  | 'rejected'
  | 'completed'

/**
 * How a delivery attempt is going.
 *
 * Mirrors `backend/app/Enums/DeliveryStatus`. `failed` is not terminal: it is the
 * state the row stays in while somebody rebooks, which is why the row is kept
 * rather than deleted - the failure is the reason a second attempt exists.
 */
export type DeliveryStatus =
  | 'scheduled'
  | 'picking_up'
  | 'in_transit'
  | 'delivered'
  | 'failed'
  | 'cancelled'

/**
 * The consignment every operations record hangs off, reduced.
 *
 * The same four fields in all three resources, and the reason they are declared
 * once here is that the three screens each open with "which consignment is this" -
 * an operator quotes a consignment reference to a broker or a driver, never the id
 * of a filing or a manifest line.
 *
 * Not the same as `TrackingShipmentRef` even though the shape overlaps: that one
 * carries a carrier, parties and an ETA because a worklist is read by asking how far
 * along the cargo is, and a box, a filing or a van is read by asking whose it is.
 */
export interface ConsignmentRef {
  id: number
  reference: string
  status: ShipmentStatus
  status_label: string
  is_closed: boolean
}

/**
 * A consignment as a container row sees it.
 *
 * The discharge port and the consignee come along for a specific reason: a box on a
 * customs hold is read by asking which border it is stuck at and who is waiting for
 * it, and both answers are here rather than one click away.
 */
export interface ContainerShipmentRef extends ConsignmentRef {
  port_of_discharge: PortRef | null
  consignee: PartyRef | null
}

/**
 * A consignment as a customs filing sees it.
 *
 * `is_late` rather than a date, because a revised arrival is what makes a filing
 * late and the consignment owns the comparison.
 */
export interface CustomsShipmentRef extends ConsignmentRef {
  is_late: boolean
}

/** A consignment as a delivery row sees it. */
export interface DeliveryShipmentRef extends ConsignmentRef {
  is_late: boolean
  consignee: PartyRef | null
}

/**
 * The port a filing was made at.
 *
 * Its own type rather than `PortRef` because the resource includes the country and
 * a customs screen reads a port as a country far more often than a master-data one
 * does. Nullable by design: the column nulls on delete, so a filing outlives the
 * port it was made at and the row still has to render.
 */
export interface ClearancePortRef {
  id: number
  un_locode: string
  name: string
  country_code: string
}

/** The vehicle on a delivery, which a driver identifies by its plate. */
export interface DeliveryTruckRef {
  id: number
  code: string | null
  plate_number: string
}

/**
 * A box on a consignment's manifest.
 *
 * The first one-to-many record in this file, and the reason `shipment_id` is
 * required on the form is the key rather than a detail: a container number is
 * reusable, the same box goes round again on a later consignment, so the pair is
 * what the database enforces and what a person means by "the box on 0001".
 *
 * `utilisation_percent` is the server's, and is null whenever the weights are
 * unknown rather than zero - an empty box and an unweighed one are different
 * findings and the list has to be able to tell them apart.
 */
export interface Container extends AnyRecord {
  container_number: string
  /** ISO 6346 size and type code, e.g. `45G1`. */
  iso_code: string | null
  type: string | null
  status: ContainerStatus | null
  status_label: string | null
  shipment?: ContainerShipmentRef | null
  seal_number: string | null
  tare_weight_kg: number | null
  max_gross_weight_kg: number | null
  gross_weight_kg: number | null
  /** Whole percent of the rating left, or null when the weights are unknown. */
  utilisation_percent: number | null
  is_empty: boolean
  needs_attention: boolean
  is_closed: boolean
}

/**
 * One consignment's business with one port's customs authority.
 *
 * A consignment that transships clears more than once, so this is one-to-many
 * against the consignment and unique on the pair. `is_held` and `needs_attention`
 * are different questions and are answered differently: held is "the goods are
 * still with the authority" and attention is "somebody has to chase this", so a
 * filing that has not been made yet is the first and not the second.
 */
export interface CustomsClearance extends AnyRecord {
  shipment?: CustomsShipmentRef | null
  port?: ClearancePortRef | null
  status: CustomsStatus | null
  status_label: string | null
  /** The authority's own reference, which exists only once something is filed. */
  entry_number: string | null
  broker: string | null
  /** ISO-8601. A filing is an event, so it has a time on it. */
  submitted_at: string | null
  cleared_at: string | null
  is_held: boolean
  needs_attention: boolean
  is_closed: boolean
  hold_reason: string | null
}

/**
 * A booked collection, and what became of it.
 *
 * The only record here keyed by a reference that is ours rather than inherited -
 * a container number belongs to the carrier and a customs entry number to the
 * authority, but `DLV-2026-00001` is this system's, so it is unique on its own and
 * is the column a list is sorted and searched by.
 *
 * `is_overdue` and `is_outstanding` deliberately disagree about a failed attempt:
 * the cargo has not been handed over, so it is outstanding, but its slot did come
 * round and the driver attended, so it is not late. A row needing a new slot is not
 * the same finding as a slot that passed with nobody there.
 */
export interface Delivery extends AnyRecord {
  reference: string
  shipment?: DeliveryShipmentRef | null
  status: DeliveryStatus | null
  status_label: string | null
  truck?: DeliveryTruckRef | null
  driver?: DriverRef | null
  /** ISO-8601. A booked slot has a time on it - that is the whole point of it. */
  scheduled_at: string | null
  picked_up_at: string | null
  delivered_at: string | null
  is_overdue: boolean
  is_outstanding: boolean
  is_moving: boolean
  has_failed: boolean
  is_closed: boolean
  contact_name: string | null
  contact_phone: string | null
  delivery_address: string | null
  pod_reference: string | null
  failure_reason: string | null
}

/* -------------------------------------------------------------------------- */
/* Finance                                                                     */
/* -------------------------------------------------------------------------- */

export type ChargeType =
  | 'freight'
  | 'handling'
  | 'documentation'
  | 'customs'
  | 'storage'
  | 'insurance'
  | 'fuel'
  | 'demurrage'
  | 'detention'
  | 'other'

export type ChargeStatus = 'draft' | 'issued' | 'void'

/**
 * Five states, of which a person sets two.
 *
 * `partially_paid` and `paid` are what the money says - the server writes them
 * from the payment rows - so a status column is read as a fact about the account
 * rather than as a value a form chose.
 */
export type InvoiceStatus = 'draft' | 'issued' | 'partially_paid' | 'paid' | 'void'

export type PaymentMethod =
  | 'bank_transfer'
  | 'cash'
  | 'card'
  | 'cheque'
  | 'letter_of_credit'
  | 'adjustment'

export type ExpenseCategory =
  | 'freight'
  | 'duty'
  | 'brokerage'
  | 'handling'
  | 'storage'
  | 'demurrage'
  | 'insurance'
  | 'fuel'
  | 'overhead'
  | 'payroll'
  | 'other'

/**
 * A consignment as a finance record sees it.
 *
 * Narrower than the `Shipment` a consignment screen renders: money is raised
 * against a job, and a job is identified by its reference and how far along it
 * is. The operational detail is a different screen's problem.
 */
export type ChargeShipmentRef = ConsignmentRef

/** An invoice as a line on a charge, and as a charge list's filter. */
export interface InvoiceRef {
  id: number
  reference: string
  status: InvoiceStatus | null
  status_label: string | null
  /** Decimal string. The balance *owing*, not the total billed. */
  balance: string
  due_date: string | null
}

export interface Charge extends AnyRecord {
  description: string
  status: ChargeStatus | null
  status_label: string | null
  type: ChargeType | null
  type_label: string | null
  quantity: string
  unit_price: string
  tax_rate: string
  subtotal: string
  tax_amount: string
  total: string
  currency: string
  is_billable: boolean
  is_invoiced: boolean
  is_settled: boolean
  shipment: ChargeShipmentRef | null
  invoice: InvoiceRef | null
}

export interface Invoice extends AnyRecord {
  reference: string
  status: InvoiceStatus | null
  status_label: string | null
  /** `Y-m-d`; null until the invoice is issued, which is what stamps it. */
  issue_date: string | null
  /** `Y-m-d`; null is legal and means "no deadline", not "missing". */
  due_date: string | null
  subtotal: string
  tax_amount: string
  total: string
  paid_amount: string
  balance: string
  currency: string
  is_outstanding: boolean
  is_settled: boolean
  is_overdue: boolean
  days_overdue: number
  /** Whether the server will still accept a PATCH. False once money has moved. */
  is_editable: boolean
  customer: PartyRef | null
}

/** An invoice as a receipt is written against it. */
export interface PaymentInvoiceRef {
  id: number
  reference: string
  status: InvoiceStatus | null
  status_label: string | null
  total: string
  balance: string
  due_date: string | null
  customer: PartyRef | null
}

export interface Payment extends AnyRecord {
  reference: string
  amount: string
  method: PaymentMethod | null
  method_label: string | null
  paid_at: string | null
  bank_reference: string | null
  /**
   * Whether this row moves the balance *down*. A receipt does; an `adjustment`
   * does not - it is a credit note, and the server subtracts it from what has been
   * received, which raises the balance again. The API sends this rather than
   * leaving the client to infer it from the method, so the badge and the
   * arithmetic cannot disagree.
   */
  is_receipt: boolean
  /** Cheque or letter of credit not yet cleared. */
  needs_reconciliation: boolean
  invoice: PaymentInvoiceRef | null
}

export interface ExpenseBookingRef {
  id: number
  reference: string
}

export interface Expense extends AnyRecord {
  reference: string
  category: ExpenseCategory | null
  category_label: string | null
  vendor_name: string
  vendor_reference: string | null
  amount: string
  currency: string
  /** `Y-m-d`; when the money left, which is what a margin is cut on. */
  incurred_on: string | null
  /**
   * Whether the customer owes this back. A billable expense is deducted from the
   * consignment's margin; a spend is the business's own cost.
   */
  is_billable: boolean
  counts_against_margin: boolean
  /** What it took off the margin. Zero for a spend. */
  margin_amount: string
  /** What it cost the business. Zero for a billable line. */
  spend_amount: string
  description: string | null
  shipment: ChargeShipmentRef | null
  /** The booking this cost was raised under, where the job had one. */
  booking: ExpenseBookingRef | null
  carrier: CarrierRef | null
}

/* -------------------------------------------------------------------------- */
/* Documents                                                                   */
/* -------------------------------------------------------------------------- */

/**
 * The kinds of paperwork the business holds.
 *
 * Mirrors `backend/app/Enums/DocumentType`. A vocabulary rather than a workflow:
 * the API validates membership and nothing enforces an order, and a bill of lading
 * does not progress from a packing list. `isExternalPaperwork` on the enum is what
 * separates the cargo paperwork from the business's own certificates - it is why
 * this list cannot simply be "the enums that are about shipments".
 */
export type DocumentType =
  | 'bill_of_lading'
  | 'commercial_invoice'
  | 'packing_list'
  | 'certificate_of_origin'
  | 'insurance_certificate'
  | 'customs_declaration'
  | 'delivery_order'
  | 'other'

/**
 * The person who put a file on the register.
 *
 * Carried inside the file summary rather than as its own relation: it is a fact
 * about the *upload*, not about the document, and two versions of an amended
 * document can have been uploaded by different people. It is nullable because a
 * version outlives the account that made it.
 */
export interface DocumentUploaderRef {
  id: number
  name: string
}

/**
 * The copy a download would serve.
 *
 * A summary and never the bytes, and never a public URL - the one way to read a
 * file is the authorised endpoint. `version` is the current copy's number and
 * `version_count` on the document says how many others there are, because "v3" on
 * its own invites the assumption that nothing came before it.
 */
export interface DocumentFileSummary {
  /** The name the file arrived with, which is the name a download saves it under. */
  name: string
  mime_type: string | null
  size_bytes: number | null
  /** 1 for a document that has never been amended. */
  version: number
  /** ISO-8601: an upload is an event, so it has a time on it. */
  uploaded_at: string | null
  uploaded_by: DocumentUploaderRef | null
}

/**
 * A piece of paperwork the business holds.
 *
 * A description and a reference to its current file, not the file itself: the row
 * is what makes a register searchable, and the bytes live on a private disk behind
 * an endpoint that checks a permission of its own.
 *
 * The consignment is optional, and that is the shape of most of this table rather
 * than a gap in it - the fleet insurance certificate and the audited accounts
 * belong to the business and not to a job.
 *
 * `is_expired`, `expires_in_days` and `needs_attention` are three different
 * questions and all three arrive from the server. They cannot be derived in the
 * client without duplicating the warning window as a second constant, and a badge,
 * a filter and a future notification would then each be free to disagree.
 */
export interface Document extends AnyRecord {
  title: string
  /** The third party's own number. Repeated across documents, so never unique. */
  reference: string | null
  document_type: DocumentType | null
  document_type_label: string | null
  shipment?: ConsignmentRef | null
  /** `Y-m-d`. */
  issued_on: string | null
  /** `Y-m-d`. A certificate is valid through its expiry date. */
  expires_on: string | null
  file: DocumentFileSummary | null
  /** Including `file`. 1 for a document that has never been amended. */
  version_count: number
  /** API path, never an absolute URL - the client's base URL may be another host. */
  download_url: string
  is_expired: boolean
  /** Negative once it has passed; null when the document never expires. */
  expires_in_days: number | null
  needs_attention: boolean
}

/* -------------------------------------------------------------------------- */
/* Surface map                                                                 */
/* -------------------------------------------------------------------------- */

/**
 * Which record type each surface serves.
 *
 * Three surfaces share the `parties` table and two share `sites`, so the ten
 * master-data screens are backed by seven record shapes. This map is what lets
 * the registry be generic: a column declared for `ports` is type-checked against
 * `Port`, so `record.un_locode` is a compile error there and legal on `vessels`.
 *
 * It is not a list of master-data screens. `shipments`, `bookings` and the three
 * operations modules are here because the same registry describes them, not because
 * they are lookup data - see `MasterSurfaceKey` for the ten that are.
 */
export interface SurfaceRecordMap {
  customers: Party
  shippers: Party
  consignees: Party
  carriers: Carrier
  vessels: Vessel
  ports: Port
  warehouses: Site
  factories: Site
  trucks: Truck
  drivers: Driver
  shipments: Shipment
  bookings: Booking
  tracking: ShipmentTracking
  containers: Container
  customs: CustomsClearance
  deliveries: Delivery
  charges: Charge
  invoices: Invoice
  payments: Payment
  expenses: Expense
  documents: Document
}

export type SurfaceKey = keyof SurfaceRecordMap

/**
 * The ten lookup tables, as opposed to the modules that record transactions.
 *
 * This is the set the sidebar's Master Data section and the master route table are
 * keyed over, and the set whose screens get an `is_active` column. It is not the
 * set a reference field may point at - see `ReferenceSurfaceKey`, which is wider.
 *
 * Written as an exclusion list rather than as `MASTER_SURFACE_KEYS` so that adding
 * a transactional surface to the registry is a deliberate act: a new one is a
 * master-data screen until this says otherwise, which is the mistake worth making
 * the compiler catch.
 */
/**
 * The ten lookup tables, and only those.
 *
 * The exclusion list is what keeps the sidebar honest. Everything else in the
 * registry - consignments, the three operations modules, and the four finance
 * modules - is served by the same page but is not lookup data, and a
 * `Record<MasterSurfaceKey, _>` in the navigation or the router is what stops
 * finance gaining a second "Master Data" section full of things that are not
 * master data.
 */
export type MasterSurfaceKey = Exclude<
  SurfaceKey,
  | 'shipments'
  | 'bookings'
  | 'tracking'
  | 'containers'
  | 'customs'
  | 'deliveries'
  | 'charges'
  | 'invoices'
  | 'payments'
  | 'expenses'
  | 'documents'
>

/**
 * Every surface a reference field is allowed to point at.
 *
 * Wider than `MasterSurfaceKey`: a consignment names the booking it came from, and
 * a container, a customs filing and a delivery all name the consignment they
 * belong to - which is what makes the consignment a reference target after all.
 *
 * Two exclusions, and the second is the less obvious one.
 *
 * `tracking` is excluded because it has no rows anyone chooses - a tracking row is
 * found through its consignment, not picked off a list - so a reference to one
 * would offer exactly one option, the consignment it belongs to. And the cycle the
 * earlier version of this type existed to prevent has a real, useful direction
 * now: a consignment points at a booking, and three operational records point back
 * at the consignment. What must never happen is a consignment naming another
 * consignment, and nothing in the registry offers it.
 *
 * `documents` is excluded because documents point *at* things and nothing points
 * back. A consignment does not have a "documents" dropdown - its paperwork is
 * found by searching the register - and adding one would mean a form that writes a
 * document by picking another document. The register is a place things are found,
 * not a thing a record is related to.
 */
export type ReferenceSurfaceKey = Exclude<SurfaceKey, 'tracking' | 'documents'>

/** The record type a surface's rows have. */
export type RecordOf<K extends SurfaceKey> = SurfaceRecordMap[K]
