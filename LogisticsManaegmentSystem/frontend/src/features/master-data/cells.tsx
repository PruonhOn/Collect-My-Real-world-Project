import { Badge, type Tone } from '@/components/ui/Badge'
import { CellStack } from '@/components/ui/Table'
import { daysUntil, formatBytes, formatDate, formatMoney, formatSignedMoney, humanise } from '@/lib/format'
import type {
  Address,
  BookingStatus,
  ChargeStatus,
  Contacted,
  ContainerStatus,
  CustomsStatus,
  DeliveryStatus,
  ExpenseCategory,
  InvoiceStatus,
  PaymentMethod,
  ShipmentStatus,
} from '@/features/master-data/types'

/**
 * The cell renderers shared between surfaces.
 *
 * Kept apart from the surface registry so that file stays declarative: it
 * describes what each module looks like, and these describe how the pieces of a
 * row are drawn. The separation also means adding a surface cannot accidentally
 * change how an unrelated one renders a status.
 */

export function ActiveBadge({ active }: { active: boolean }) {
  return (
    <Badge tone={active ? 'success' : 'neutral'} dot>
      {active ? 'Active' : 'Inactive'}
    </Badge>
  )
}

/**
 * Colour per shipment status.
 *
 * A total `Record` rather than a partial one with a default, so a status added to
 * the enum without a colour here is a build error. That is the outcome worth
 * having: it forces a decision about how a new state looks instead of shipping a
 * list where one badge is indistinguishable from every other.
 */
const SHIPMENT_TONES: Record<ShipmentStatus, Tone> = {
  draft: 'neutral',
  booked: 'info',
  in_transit: 'brand',
  customs: 'warning',
  arrived: 'purple',
  delivered: 'success',
  cancelled: 'danger',
}

/**
 * A shipment's lifecycle position, and whether it has slipped.
 *
 * The label is the server's `status_label` rather than a client `humanise`, so
 * the wording is one translation for the API, the UI and anything downstream that
 * prints the value. `late` is the server's `is_late` for the same reason - it
 * knows the ETA and whether the consignment is closed, and a row, a summary and a
 * report should not each reach their own conclusion about what counts as
 * overdue. The marker is a separate badge because being late is a fact about the
 * dates, not another stage; painting the status red would lose where it got to.
 */
export function ShipmentStatusBadge({
  status,
  label,
  late,
}: {
  status: ShipmentStatus
  label: string
  late: boolean
}) {
  return (
    <span className="inline-flex items-center gap-1.5">
      <Badge tone={SHIPMENT_TONES[status]} dot>
        {label}
      </Badge>
      {late && <Badge tone="danger">Late</Badge>}
    </span>
  )
}

/**
 * Colour per booking status.
 *
 * A total `Record` for the same reason as the shipment tones: a status added to
 * the enum without a colour here is a build error, which forces the decision
 * rather than shipping two badges that look alike.
 *
 * The vocabulary is about the request rather than the movement, so `confirmed`
 * takes the tone `delivered` has on a shipment - it is the state the request
 * reached, and everything after it belongs to the consignment. `expired` is a
 * warning rather than a danger: nobody chose it, and a row that looks like a
 * mistake would be read as one.
 */
const BOOKING_TONES: Record<BookingStatus, Tone> = {
  draft: 'neutral',
  requested: 'info',
  confirmed: 'success',
  cancelled: 'danger',
  expired: 'warning',
}

/**
 * A booking's position in the request lifecycle.
 *
 * The label is the server's `status_label` for the same reason the shipment badge
 * uses it: one translation for the API, the UI and anything downstream that prints
 * the value, rather than a client `humanise` that would have to be kept in step
 * with the enum.
 */
export function BookingStatusBadge({ status, label }: { status: BookingStatus; label: string }) {
  return (
    <Badge tone={BOOKING_TONES[status]} dot>
      {label}
    </Badge>
  )
}

/**
 * Colour per container status.
 *
 * A total `Record` for the same reason as the two above: a status added to the enum
 * without a colour here is a build error.
 *
 * The two that share a tone with a shipment status - `delivered` with `delivered`,
 * `customs_hold` with `customs` - are the same states seen from the box's side, and
 * a delivery operator reading a manifest should not have to relearn the palette.
 * `damaged` is the only danger on this surface: a box is not late, it is broken,
 * and painting that the same warning as a hold would rank a write-off above cargo
 * that is merely stuck.
 */
const CONTAINER_TONES: Record<ContainerStatus, Tone> = {
  empty: 'neutral',
  loaded: 'info',
  in_transit: 'brand',
  customs_hold: 'warning',
  released: 'success',
  delivered: 'success',
  damaged: 'danger',
}

/**
 * A box's state, and whether it needs chasing.
 *
 * `attention` is the server's `needs_attention` and rides beside the status rather
 * than replacing it, for the same reason `ShipmentStatusBadge` keeps `late`
 * separate: a held box and a damaged one are both problems and they call for
 * different phone calls, so losing which one it is would cost the reader the only
 * useful thing on the row.
 */
export function ContainerStatusBadge({
  status,
  label,
  attention,
}: {
  status: ContainerStatus | null
  label: string | null
  attention: boolean
}) {
  if (!status) {
    return <Badge tone="neutral">—</Badge>
  }

  return (
    <span className="inline-flex items-center gap-1.5">
      <Badge tone={CONTAINER_TONES[status]} dot>
        {label ?? humanise(status)}
      </Badge>
      {attention && <Badge tone="danger">Needs attention</Badge>}
    </span>
  )
}

/**
 * Colour per customs status.
 *
 * `pending` and `approved` are the two worth arguing about. Pending is neutral
 * because it is the state every filing starts in and colouring it as a problem
 * would make a fresh install look like an outbreak. Approved takes the shipment
 * palette's treatment for a delivered box - a success - even though the API is
 * explicit that it is not the end, because the cargo genuinely is permitted to
 * leave and the follow-up is somebody else's screen.
 */
const CUSTOMS_TONES: Record<CustomsStatus, Tone> = {
  pending: 'neutral',
  submitted: 'info',
  under_review: 'warning',
  approved: 'success',
  rejected: 'danger',
  completed: 'success',
}

/**
 * A filing's state, and the two worklist answers beside it.
 *
 * `held` and `attention` are rendered separately and are not the same flag: a
 * filing that has not been made yet is held and needs nobody to chase, and a
 * refusal needs chasing and is not held. Collapsing them into one badge would make
 * the two screens' filters and their rows describe one list.
 */
export function CustomsStatusBadge({
  status,
  label,
  held,
  attention,
}: {
  status: CustomsStatus | null
  label: string | null
  held: boolean
  attention: boolean
}) {
  if (!status) {
    return <Badge tone="neutral">—</Badge>
  }

  return (
    <span className="inline-flex items-center gap-1.5">
      <Badge tone={CUSTOMS_TONES[status]} dot>
        {label ?? humanise(status)}
      </Badge>
      {attention ? (
        <Badge tone="danger">Needs attention</Badge>
      ) : held ? (
        <Badge tone="warning">Held</Badge>
      ) : null}
    </span>
  )
}

/**
 * Colour per delivery status.
 *
 * `scheduled` and `cancelled` are both neutral, and the reason is that both mean
 * "nothing is happening right now" rather than "nothing went wrong". A cancelled
 * booking painted as a danger would read as a failure, which is the one thing it is
 * not - somebody decided.
 */
const DELIVERY_TONES: Record<DeliveryStatus, Tone> = {
  scheduled: 'neutral',
  picking_up: 'info',
  in_transit: 'brand',
  delivered: 'success',
  failed: 'danger',
  cancelled: 'neutral',
}

/**
 * A delivery's state, and whether its slot has gone by.
 *
 * `overdue` is the server's answer rather than a comparison made here, because the
 * list is filtered on it and a row that disagreed with its own list would be worse
 * than either. It is a separate badge for the same reason as everywhere else here:
 * late is a fact about the clock, not another stage of the delivery.
 *
 * A failed attempt gets no overdue badge, and that is the API's decision rather
 * than this one - its slot came round and the driver attended, so the row needs a
 * new slot booked rather than chasing.
 */
export function DeliveryStatusBadge({
  status,
  label,
  overdue,
}: {
  status: DeliveryStatus | null
  label: string | null
  overdue: boolean
}) {
  if (!status) {
    return <Badge tone="neutral">—</Badge>
  }

  return (
    <span className="inline-flex items-center gap-1.5">
      <Badge tone={DELIVERY_TONES[status]} dot>
        {label ?? humanise(status)}
      </Badge>
      {overdue && <Badge tone="warning">Overdue</Badge>}
    </span>
  )
}

/** A dash for an absent value, matching the rest of the table. */
export function Text({ value }: { value: string | null | undefined }) {
  return value ? <span className="truncate">{value}</span> : <span className="text-ink-300">—</span>
}

/* -------------------------------------------------------------------------- */
/* Finance cells                                                               */
/* -------------------------------------------------------------------------- */

/**
 * An amount, right-aligned and in tabular figures.
 *
 * A finance column is read by comparing digits vertically, and the proportional
 * figures in the body font make two amounts of the same value look unequal, so
 * the currency sits in its own span and the number is tabular.
 */
export function MoneyCell({
  value,
  currency,
  align = 'right',
}: {
  value: string | null | undefined
  currency?: string | null
  align?: 'left' | 'right'
}) {
  return (
    <span className={align === 'right' ? 'block text-right tabular' : 'tabular'}>
      {formatMoney(value, currency)}
    </span>
  )
}

/**
 * A charge's state, and whether it has been billed.
 *
 * The three tones are the invoice's, minus the ones an invoice has and a line
 * cannot: a charge is never `partially_paid` and never `paid` - those are
 * statements about money, and a line is a fraction of a bill, so whether the
 * bill is settled is the invoice row's business and is shown there.
 *
 * `invoiced` rides beside the status rather than replacing it, for the same
 * reason the late and overdue markers do everywhere else: which bill a line is
 * on is a different fact from what stage it is at, and collapsing the two loses
 * the one an operator is looking for.
 */
const CHARGE_TONES: Record<ChargeStatus, Tone> = {
  draft: 'neutral',
  issued: 'info',
  void: 'neutral',
}

export function ChargeStatusBadge({
  status,
  label,
  invoiced,
  settled,
}: {
  status: ChargeStatus | null
  label: string | null
  invoiced: boolean
  settled: boolean
}) {
  if (!status) {
    return <Badge tone="neutral">—</Badge>
  }

  return (
    <span className="inline-flex items-center gap-1.5">
      <Badge tone={CHARGE_TONES[status]} dot>
        {label ?? humanise(status)}
      </Badge>
      {invoiced && <Badge tone={settled ? 'success' : 'info'}>{settled ? 'Settled' : 'Billed'}</Badge>}
    </span>
  )
}

/**
 * Colour per invoice status.
 *
 * `partially_paid` is a warning rather than a success. It is the state a
 * receivables clerk is trying to move *out* of, and colouring it as good news
 * puts it in the same visual class as the `paid` row next to it - so the one
 * invoice on a list that needs chasing is the one that looks finished.
 *
 * `void` is neutral: nobody chose to write an invoice off, and a danger badge
 * would read as a failure of something rather than a decision about it.
 */
const INVOICE_TONES: Record<InvoiceStatus, Tone> = {
  draft: 'neutral',
  issued: 'info',
  partially_paid: 'warning',
  paid: 'success',
  void: 'neutral',
}

/**
 * An invoice's state, and whether the money is late.
 *
 * `overdue` is the server's `is_overdue` rather than a date comparison made here,
 * for the same reason the delivery badge uses the server's: the list can be
 * filtered to exactly this set, and a row disagreeing with its own filter is
 * worse than either being wrong. An invoice with no due date never counts as
 * overdue, which is the server's call and not something this component may
 * second-guess.
 */
export function InvoiceStatusBadge({
  status,
  label,
  overdue,
}: {
  status: InvoiceStatus | null
  label: string | null
  overdue: boolean
}) {
  if (!status) {
    return <Badge tone="neutral">—</Badge>
  }

  return (
    <span className="inline-flex items-center gap-1.5">
      <Badge tone={INVOICE_TONES[status]} dot>
        {label ?? humanise(status)}
      </Badge>
      {overdue && <Badge tone="danger">Overdue</Badge>}
    </span>
  )
}

/**
 * A due date, annotated with how many days are left and whether it is the balance
 * that is late.
 *
 * Separate from the status badge for the reason above, and separate from
 * `ExpiryCell` because the two count in opposite directions and mean opposite
 * things to the reader: a licence is helped by having less time left, a bill is
 * hurt by it. An open-ended invoice has no deadline and reads as a dash rather
 * than as an overdue one.
 */
export function DueDateCell({ value, overdue }: { value: string | null; overdue: boolean }) {
  if (!value) {
    return <span className="text-ink-300">—</span>
  }

  const days = daysUntil(value)

  const suffix =
    days === null ? null : days === 0 ? 'due today' : days > 0 ? `${days}d left` : `${Math.abs(days)}d late`

  return (
    <CellStack
      primary={<span className="tabular">{formatDate(value)}</span>}
      secondary={
        suffix ? (
          <span
            className={
              overdue || (days !== null && days < 0)
                ? 'text-danger-600 font-medium'
                : days !== null && days <= 7
                  ? 'text-warning-700 font-medium'
                  : undefined
            }
          >
            {suffix}
          </span>
        ) : undefined
      }
    />
  )
}

/**
 * A payment's direction, which is the one thing about it that is not its amount.
 *
 * A receipt and a credit note are the same column with a sign that flips, so a
 * list of payments sorted by date shows a refund sitting among the incoming
 * money looking like a large receipt. The badge is what makes the direction
 * legible without having to remember which of the six methods moves the balance
 * which way.
 */
export function PaymentMethodBadge({
  method,
  label,
  isReceipt,
}: {
  method: PaymentMethod | null
  label: string | null
  isReceipt: boolean
}) {
  if (!method) {
    return <Badge tone="neutral">—</Badge>
  }

  return (
    <span className="inline-flex items-center gap-1.5">
      <Badge tone={method === 'adjustment' ? 'warning' : 'neutral'}>{label ?? humanise(method)}</Badge>
      {!isReceipt && <Badge tone="warning">Credit</Badge>}
    </span>
  )
}

/**
 * The signed amount a payment moved, with its direction.
 *
 * Rendered from the server's `is_receipt` rather than from the sign, because the
 * server never stores a negative amount: an adjustment arrives looking like a
 * positive number and is *subtracted* from what has been received. Showing
 * `+2,930.00` for a credit note would be a lie told by the column, and the
 * server is the only party that knows which way this one goes.
 */
export function PaymentAmountCell({
  value,
  currency,
  isReceipt,
}: {
  value: string | null | undefined
  currency?: string | null
  isReceipt: boolean
}) {
  const numeric = Number(value ?? '')
  const signed = Number.isFinite(numeric) ? (isReceipt ? numeric : -numeric) : null

  return (
    <span
      className={`block text-right tabular ${
        signed === null ? '' : signed < 0 ? 'text-warning-700' : 'text-success-700'
      }`}
    >
      {signed === null ? formatMoney(value, currency) : formatSignedMoney(signed, currency)}
    </span>
  )
}

/** The category an expense was booked under, and whether it eats margin. */
export function ExpenseCategoryBadge({
  category,
  label,
  billable,
  countsAgainstMargin,
}: {
  category: ExpenseCategory | null
  label: string | null
  billable: boolean
  countsAgainstMargin: boolean
}) {
  if (!category) {
    return <Badge tone="neutral">—</Badge>
  }

  return (
    <span className="inline-flex items-center gap-1.5">
      <Badge tone="neutral">{label ?? humanise(category)}</Badge>
      {/*
       * Billable but not against margin is duty and tax: the customer's money
       * passing through to an authority. The two are deliberately distinguishable,
       * because the pair is what separates a cost that was recharged from one that
       * was actually spent, and collapsing them into one word loses the only
       * reason the API sends both.
       */}
      {billable && (
        <Badge tone={countsAgainstMargin ? 'info' : 'warning'}>
          {countsAgainstMargin ? 'Margin' : 'Rebate only'}
        </Badge>
      )}
    </span>
  )
}

/** City, state and country, for the entity types that have an address. */
export function AddressCell({ record }: { record: { address: Address } }) {
  const { city, state, country_code } = record.address
  const place = [city, state].filter(Boolean).join(', ')

  return <CellStack primary={<Text value={place} />} secondary={country_code ?? undefined} />
}

export function ContactCell({ record }: { record: Contacted }) {
  return (
    <CellStack
      primary={<Text value={record.contact_email} />}
      secondary={record.contact_phone ?? undefined}
    />
  )
}

/** A relation that is expected to be filled in on some rows and not others. */
export function RelationCell({ primary, secondary }: { primary: string; secondary?: string }) {
  return <CellStack primary={primary} secondary={secondary} />
}

export function Unassigned() {
  return <span className="text-ink-300">Unassigned</span>
}

/**
 * A licence or medical expiry, annotated with how long is left.
 *
 * Thirty days is the warning threshold because that is roughly the notice a
 * renewal takes; anything at or past zero is an expiry rather than a due date,
 * so it reads as "overdue" or "today" instead of a bare countdown.
 */
export function ExpiryCell({ value }: { value: string | null }) {
  if (!value) {
    return <span className="text-ink-300">—</span>
  }

  const days = daysUntil(value)
  const overdue = days !== null && days < 0
  const soon = days !== null && days >= 0 && days <= 30

  const suffix =
    days === null ? null : overdue ? `${Math.abs(days)}d overdue` : days === 0 ? 'today' : `${days}d`

  return (
    <CellStack
      primary={<span className="tabular">{formatDate(value)}</span>}
      secondary={
        suffix ? (
          <span
            className={
              overdue ? 'text-danger-600' : soon ? 'text-warning-700 font-medium' : undefined
            }
          >
            {suffix}
          </span>
        ) : undefined
      }
    />
  )
}

/**
 * A document's expiry, annotated with how long is left.
 *
 * Visually the same shape as `ExpiryCell`, and deliberately not that function:
 * this one is handed `isExpired` and `needsAttention` rather than recomputing them,
 * because the warning window is `logistics.documents.expiry_warning_days` and the
 * client has no reason to hold a second copy of a number the server can change. A
 * badge, this cell and the `expiring` filter would otherwise each be free to read a
 * different threshold, and the day they disagreed the table would contradict the
 * count in the tab.
 *
 * A document with no expiry date is not an expired document - a bill of lading does
 * not expire and a set of audited accounts never has - so it renders as a dash
 * rather than as a countdown to nothing.
 */
export function DocumentExpiryCell({
  value,
  isExpired,
  needsAttention,
}: {
  value: string | null
  isExpired: boolean
  needsAttention: boolean
}) {
  if (!value) {
    return <span className="text-ink-300">—</span>
  }

  const days = daysUntil(value)
  const suffix = isExpired
    ? days === null
      ? 'expired'
      : `${Math.abs(days)}d ago`
    : days === 0
      ? 'today'
      : days === null
        ? null
        : `in ${days}d`

  return (
    <CellStack
      primary={<span className="tabular">{formatDate(value)}</span>}
      secondary={
        suffix ? (
          <span
            className={
              isExpired
                ? 'text-danger-600'
                : needsAttention
                  ? 'text-warning-700 font-medium'
                  : undefined
            }
          >
            {suffix}
          </span>
        ) : undefined
      }
    />
  )
}

/**
 * A document's current file, as a name and a size.
 *
 * Never a link. The bytes live on a private disk and the only route to them is an
 * authenticated download that checks a permission of its own, so a cell rendering
 * an `<a href>` would either point at a dead path or invite someone to publish a
 * public one. A file's *name* is metadata the register is searchable by and belongs
 * in the row; opening it is a row action, where the permission is checked.
 */
export function DocumentFileCell({
  name,
  version,
  size,
}: {
  name: string
  version: number
  size: number | null
}) {
  return (
    <CellStack
      primary={<span className="font-medium text-ink-900">{name}</span>}
      secondary={
        <span className="text-ink-500">
          v{version} · {size === null ? 'size unknown' : formatBytes(size)}
        </span>
      }
    />
  )
}
