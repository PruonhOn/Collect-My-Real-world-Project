/**
 * Display formatting.
 *
 * Every helper here is total: an absent or unparseable value renders as an em
 * dash rather than "Invalid Date" or "NaN". A master-data table is dense with
 * optional columns, and a column of "undefined" tells an operator nothing about
 * whether the data is missing or the render is broken.
 */

export const EM_DASH = '—'

/** Parses an ISO-8601 timestamp. Returns null when it cannot be read. */
function parseTimestamp(value: string | null | undefined): Date | null {
  if (!value) return null
  const parsed = new Date(value)
  return Number.isNaN(parsed.getTime()) ? null : parsed
}

/**
 * Parses a date-only value (`Y-m-d`) as a *local* midnight.
 *
 * `new Date('2026-10-01')` is UTC midnight, which in any negative-offset zone
 * is the previous evening locally. Comparing that against a local "today" makes
 * a licence that expires today look expired a day early. Splitting the parts
 * avoids the timezone round-trip entirely.
 */
function parseDateOnly(value: string | null | undefined): Date | null {
  if (!value) return null

  const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(value)
  if (!match) return parseTimestamp(value)

  const [, year, month, day] = match
  return new Date(Number(year), Number(month) - 1, Number(day))
}

/** `01 Oct 2026`. Accepts both a timestamp and a date-only value. */
export function formatDate(value: string | null | undefined): string {
  const parsed = parseDateOnly(value)
  return parsed ? parsed.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : EM_DASH
}

/** `01 Oct 2026, 14:32`. For audit columns where the clock matters. */
export function formatDateTime(value: string | null | undefined): string {
  const parsed = parseTimestamp(value)
  return parsed
    ? parsed.toLocaleString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      })
    : EM_DASH
}

/** Whole numbers with thousands separators, e.g. `1,264`. */
export function formatNumber(value: number | string | null | undefined): string {
  if (value === null || value === undefined || value === '') return EM_DASH

  const numeric = typeof value === 'number' ? value : Number(value)
  if (!Number.isFinite(numeric)) return EM_DASH

  return numeric.toLocaleString('en-US')
}

/**
 * A file size, e.g. `412 KB`.
 *
 * Binary units, and the count on the label is rounded rather than exact: this is
 * there to let somebody tell a scanned bill of lading from a photograph of it, and
 * exact bytes would be a longer string for no extra judgement. The division is on
 * 1024 while the label says KB, which is the usual lie and the right one - a "KB"
 * that means 1000 would put a 20 MB upload's limit at 20,971,520 instead of
 * 20,000,000 and make the boundary untestable against the number the user was told.
 *
 * Null when the server could not read the size off disk, which is not the same as
 * zero and reads as its own case rather than as an empty file.
 */
export function formatBytes(value: number | null | undefined): string {
  if (value === null || value === undefined || !Number.isFinite(value)) return EM_DASH
  if (value === 0) return '0 B'

  const units = ['B', 'KB', 'MB', 'GB']
  const exponent = Math.min(Math.floor(Math.log(value) / Math.log(1024)), units.length - 1)
  const scaled = value / 1024 ** exponent
  const digits = exponent === 0 || scaled >= 100 ? 0 : 1

  return `${scaled.toFixed(digits)} ${units[exponent]}`
}

/** One or two decimals, e.g. `1.26`. For coordinates. */
export function formatDecimal(value: number | null | undefined, digits = 2): string {
  if (value === null || value === undefined || !Number.isFinite(value)) return EM_DASH
  return value.toLocaleString('en-US', { minimumFractionDigits: digits, maximumFractionDigits: digits })
}

/**
 * An amount of money, e.g. `AED 2,930.00`.
 *
 * The API sends money as a decimal *string* - `decimal(15,4)` in the database, and
 * a float cannot hold 2,930.00 or most values a port charges without rounding -
 * so this parses rather than assuming a number, and total like everything else
 * here: an absent or unparseable value is an em dash.
 *
 * Two decimals, always. The stored scale is four and three of those are the VAT
 * fraction on a rate of 4.75%, so the fourth decimal is arithmetic rather than
 * something anybody reads off an invoice. Rounding is left to the display
 * formatter and the server is never asked to re-round a figure it has already
 * stored: the string is what the ledger says and this is only how it is shown.
 */
export function formatMoney(value: string | number | null | undefined, currency?: string | null): string {
  if (value === null || value === undefined || value === '') return EM_DASH

  const numeric = typeof value === 'number' ? value : Number(value)
  if (!Number.isFinite(numeric)) return EM_DASH

  // `style: 'currency'` would symbol the amount, but a lettered currency is
  // unambiguous where a symbol is not - `AED 2,930.00` and `USD 2,930.00` are
  // distinguishable, `€2,930.00` and `¥2,930.00` less so, and a finance module
  // that mixes AED with USD is the normal case rather than the exotic one.
  const amount = numeric.toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })

  return currency ? `${currency.toUpperCase()} ${amount}` : amount
}

/**
 * A signed amount, for a column that holds credits as well as debits.
 *
 * An adjustment against an invoice is stored positive and subtracted by the
 * server, so it arrives looking like a receipt while moving the balance the wrong
 * way. Rendering it with a leading minus here - rather than the digit alone - is
 * what stops a clerk reading a credit note as money in.
 */
export function formatSignedMoney(value: string | number | null | undefined, currency?: string | null): string {
  const numeric = typeof value === 'number' ? value : Number(value ?? '')

  if (value === null || value === undefined || value === '' || !Number.isFinite(numeric)) {
    return formatMoney(value, currency)
  }

  const formatted = formatMoney(Math.abs(numeric), currency)

  return numeric < 0 ? `-${formatted}` : `+${formatted}`
}

/**
 * Whole days from today until a date-only value. Negative once it has passed.
 *
 * Rounded through the date components rather than by dividing a millisecond
 * delta, so daylight-saving shifts cannot produce 0.97 days rounding to 1.
 */
export function daysUntil(value: string | null | undefined): number | null {
  const target = parseDateOnly(value)
  if (!target) return null

  const today = new Date()
  const startOfToday = new Date(today.getFullYear(), today.getMonth(), today.getDate())

  return Math.round((target.getTime() - startOfToday.getTime()) / 86_400_000)
}

/**
 * How long ago something happened, in words: `4 min ago`, `2 days ago`.
 *
 * A tracking worklist is read by asking one question of every row - how long since
 * we heard from this consignment - and an operator working out "is 06 Sep" against
 * today's date in their head is doing arithmetic the screen could have done. The
 * absolute timestamp is still available in the row's tooltip-free second line
 * where the column wants both; this is the part that gets scanned.
 *
 * Total like everything else here: an absent or unparseable value is an em dash,
 * and a timestamp in the future - clock skew between a carrier's feed and ours, or
 * a test - reads as `just now` rather than as a negative duration nobody can
 * place. `now` is injectable so a caller, and a test, can pin it.
 */
export function formatRelativeTime(value: string | null | undefined, now: Date = new Date()): string {
  const parsed = parseTimestamp(value)
  if (!parsed) return EM_DASH

  const seconds = Math.round((now.getTime() - parsed.getTime()) / 1000)

  if (seconds < 45) return 'just now'

  const units: Array<[Intl.RelativeTimeFormatUnit, number]> = [
    ['year', 365 * 86_400],
    ['month', 30 * 86_400],
    ['day', 86_400],
    ['hour', 3_600],
    ['minute', 60],
  ]

  const formatter = new Intl.RelativeTimeFormat('en-GB', { numeric: 'auto' })

  for (const [unit, size] of units) {
    if (seconds >= size) {
      return formatter.format(-Math.floor(seconds / size), unit)
    }
  }

  return 'just now'
}

/** Joins the non-empty parts of an address into one line. */
export function formatAddressLine(parts: (string | null | undefined)[]): string {
  const present = parts.map((part) => part?.trim()).filter((part): part is string => Boolean(part))
  return present.length > 0 ? present.join(', ') : EM_DASH
}

/** Turns `box_truck` or `boxTruck` into `Box truck`. */
export function humanise(value: string | null | undefined): string {
  if (!value) return EM_DASH

  const spaced = value.replace(/[_-]+/g, ' ').trim()
  if (spaced === '') return EM_DASH

  return spaced.charAt(0).toUpperCase() + spaced.slice(1)
}
