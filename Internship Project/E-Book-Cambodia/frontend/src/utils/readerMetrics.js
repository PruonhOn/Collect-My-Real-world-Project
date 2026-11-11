// Lightweight performance counters for the PDF reader.
//
// The reader is used by many readers on many devices, so this intentionally
// keeps only bounded samples (never grows without limit) and never sends data
// anywhere. Inspect the live numbers from the console with:
//   window.__PI_READER_METRICS__.get()

const MAX_SAMPLES = 120

const empty = () => ({
  renderCount: 0,
  renderFailures: 0,
  cacheHits: 0,
  cacheMisses: 0,
  preloadSuccess: 0,
  preloadFailures: 0,
  cancelled: 0,
  duplicateAvoided: 0,
  pageLoadSamples: [],
  renderSamples: [],
})

let metrics = empty()

function pushSample(list, value) {
  if (!Number.isFinite(value) || value < 0) return
  list.push(Math.round(value * 10) / 10)
  if (list.length > MAX_SAMPLES) list.shift()
}

function summarize(samples) {
  if (!samples.length) return { count: 0, avgMs: 0, p95Ms: 0, maxMs: 0 }
  const sorted = [...samples].sort((a, b) => a - b)
  const total = sorted.reduce((sum, value) => sum + value, 0)
  return {
    count: sorted.length,
    avgMs: Math.round((total / sorted.length) * 10) / 10,
    p95Ms: sorted[Math.min(sorted.length - 1, Math.floor(sorted.length * 0.95))],
    maxMs: sorted[sorted.length - 1],
  }
}

export function recordMetric(event) {
  if (!event || !event.type) return
  switch (event.type) {
    case 'render_end':
      metrics.renderCount += 1
      pushSample(metrics.renderSamples, event.durationMs)
      if (event.cached) metrics.cacheHits += 1
      break
    case 'render_error':
      metrics.renderFailures += 1
      break
    case 'cache_hit':
      metrics.cacheHits += 1
      break
    case 'cache_miss':
      metrics.cacheMisses += 1
      break
    case 'preload_success':
      metrics.preloadSuccess += 1
      break
    case 'preload_error':
      metrics.preloadFailures += 1
      break
    case 'job_cancelled':
      metrics.cancelled += 1
      break
    case 'duplicate_avoided':
      metrics.duplicateAvoided += 1
      break
    case 'visible_ready':
      pushSample(metrics.pageLoadSamples, event.durationMs)
      break
    default:
      break
  }
}

export function getReaderMetrics() {
  return {
    renderCount: metrics.renderCount,
    renderFailures: metrics.renderFailures,
    cacheHits: metrics.cacheHits,
    cacheMisses: metrics.cacheMisses,
    preloadSuccess: metrics.preloadSuccess,
    preloadFailures: metrics.preloadFailures,
    cancelled: metrics.cancelled,
    duplicateAvoided: metrics.duplicateAvoided,
    visiblePageLoad: summarize(metrics.pageLoadSamples),
    renderDuration: summarize(metrics.renderSamples),
  }
}

export function resetReaderMetrics() {
  metrics = empty()
}

if (typeof window !== 'undefined') {
  window.__PI_READER_METRICS__ = {
    get: getReaderMetrics,
    reset: resetReaderMetrics,
  }
}

export default { recordMetric, getReaderMetrics, resetReaderMetrics }
