/**
 * Money helpers. Every monetary value handled by the API is an integer in the
 * minor unit of the currency (e.g. cents). Formatting happens in the browser
 * only; the backend always stays authoritative.
 */

export function formatMoney(amount, currency = 'USD', options = {}) {
  const value = Number(amount ?? 0) / 100

  try {
    return new Intl.NumberFormat(undefined, {
      style: 'currency',
      currency,
      minimumFractionDigits: options.decimals ?? 2,
      maximumFractionDigits: options.decimals ?? 2,
    }).format(value)
  } catch {
    return `${currency} ${value.toFixed(2)}`
  }
}

/**
 * Parse a user supplied amount into minor units.
 */
export function toMinorUnits(value) {
  const parsed = typeof value === 'number' ? value : parseFloat(String(value ?? '').replace(/[^0-9.-]/g, ''))

  if (Number.isNaN(parsed)) {
    return 0
  }

  return Math.round(parsed * 100)
}

/**
 * Convert minor units back into a decimal string for an input field.
 */
export function fromMinorUnits(amount) {
  return (Number(amount ?? 0) / 100).toFixed(2)
}

export function formatDate(value, options = {}) {
  if (!value) {
    return '—'
  }

  const date = new Date(value)

  if (Number.isNaN(date.getTime())) {
    return '—'
  }

  return new Intl.DateTimeFormat(undefined, {
    dateStyle: options.dateStyle ?? 'medium',
    ...(options.withTime ? { timeStyle: 'short' } : {}),
  }).format(date)
}

export function formatDateTime(value) {
  return formatDate(value, { withTime: true })
}

export function formatPercent(value, decimals = 0) {
  const number = Number(value ?? 0)

  return `${number.toFixed(decimals)}%`
}

export function titleCase(value) {
  if (!value) {
    return ''
  }

  return String(value)
    .replace(/[_-]/g, ' ')
    .replace(/\b\w/g, (character) => character.toUpperCase())
}

export function fullName(entity) {
  if (!entity) {
    return '—'
  }

  if (entity.name) {
    return entity.name
  }

  const first = entity.first_name || ''
  const last = entity.last_name || ''

  return `${first} ${last}`.trim() || entity.email || '—'
}
