/**
 * Re-fetch intervals for views that are read like a live board.
 *
 * The API is served by serverless functions, which cannot hold a socket open,
 * so there is no push channel to subscribe to: short-interval polling is the
 * only way a screen moves while it stays open. Intervals are per-surface
 * rather than global because a payload's size is what makes a rate polite.
 */
export const LIVE_REFRESH = {
  /** Activity feeds and badge counts: tiny payloads that change constantly. */
  fast: 15_000,
  /** Dashboards and worklists: larger payloads, tolerable staleness. */
  standard: 30_000,
} as const
