import { Badge, type Tone } from '@/components/ui/Badge'

/**
 * Maps a domain status to a colour.
 *
 * Colour is the fastest signal an operator can read across a 50-row table, so
 * the mapping is centralised here rather than left to each page. An unmapped
 * status falls back to `neutral` instead of crashing, which keeps a newly
 * seeded status from breaking a listing.
 */
const SHIPMENT_TONES: Record<string, Tone> = {
  draft: 'neutral',
  confirmed: 'info',
  documentation: 'info',
  customs: 'purple',
  ready_for_pickup: 'purple',
  picked_up: 'brand',
  gate_in: 'brand',
  vessel_departed: 'brand',
  in_transit: 'brand',
  vessel_arrived: 'warning',
  customs_cleared: 'success',
  container_released: 'success',
  out_for_delivery: 'warning',
  delivered: 'success',
  cancelled: 'danger',
}

const BOOKING_TONES: Record<string, Tone> = {
  draft: 'neutral',
  requested: 'info',
  confirmed: 'success',
  cancelled: 'danger',
  converted: 'purple',
}

const INVOICE_TONES: Record<string, Tone> = {
  draft: 'neutral',
  issued: 'info',
  partially_paid: 'warning',
  paid: 'success',
  overdue: 'danger',
  cancelled: 'neutral',
}

const CUSTOMS_TONES: Record<string, Tone> = {
  pending: 'neutral',
  submitted: 'info',
  under_review: 'warning',
  approved: 'success',
  rejected: 'danger',
  completed: 'success',
}

const DELIVERY_TONES: Record<string, Tone> = {
  scheduled: 'neutral',
  picking_up: 'info',
  in_transit: 'brand',
  delivered: 'success',
  failed: 'danger',
  cancelled: 'neutral',
}

const EXPENSE_TONES: Record<string, Tone> = {
  pending: 'warning',
  approved: 'info',
  paid: 'success',
  rejected: 'danger',
}

const CONTAINER_TONES: Record<string, Tone> = {
  empty: 'neutral',
  loaded: 'info',
  in_transit: 'brand',
  customs_hold: 'warning',
  released: 'success',
  delivered: 'success',
  damaged: 'danger',
}

const MAPS: Record<string, Record<string, Tone>> = {
  shipment: SHIPMENT_TONES,
  booking: BOOKING_TONES,
  invoice: INVOICE_TONES,
  customs: CUSTOMS_TONES,
  delivery: DELIVERY_TONES,
  expense: EXPENSE_TONES,
  container: CONTAINER_TONES,
}

export type StatusDomain = keyof typeof MAPS

export function StatusBadge({
  domain,
  status,
  label,
  className,
}: {
  domain: StatusDomain
  status: string | null | undefined
  /** Overrides the generated label, e.g. for a translated or legacy value. */
  label?: string
  className?: string
}) {
  if (!status) {
    return (
      <Badge tone="neutral" className={className}>
        &mdash;
      </Badge>
    )
  }

  const key = status.toLowerCase().replace(/[\s-]+/g, '_')
  const tone = MAPS[domain][key] ?? 'neutral'

  return (
    <Badge tone={tone} dot className={className}>
      {label ?? key.replace(/_/g, ' ')}
    </Badge>
  )
}
