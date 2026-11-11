/**
 * Page preloading engine for the book reader.
 *
 * The reader already holds the whole PDF in memory, so "preloading" a page
 * really means rendering it to a bitmap. Rendering is CPU bound, so kicking
 * off every page of the window at once (the previous behaviour) starved the
 * main thread and made the page-flip animation stutter.
 *
 * This scheduler keeps the reading experience smooth by:
 *   - rendering pages in priority order (nearest to the reader first),
 *   - capping how many renders may run at the same time,
 *   - producing a cheap low-resolution "draft" of the far pages first so a
 *     leaf is never empty, then upgrading them to full quality in the
 *     background,
 *   - cancelling renders the reader has already moved away from,
 *   - keeping memory bounded with an LRU window.
 *
 * The engine is deliberately renderer agnostic: the caller supplies a
 * `render(pageNumber, { quality, signal })` function that resolves with a URL
 * for the rendered page.
 */

export const PRELOAD_AHEAD = 4
export const PRELOAD_BEHIND = 2
export const PRELOAD_FULL_WINDOW = 2
export const PRELOAD_MAX_CONCURRENT = 2
export const PRELOAD_MAX_FULL = 18
export const PRELOAD_MAX_DRAFT = 20

export const QUALITY_FULL = 'full'
export const QUALITY_DRAFT = 'draft'

function createSignal() {
  return {
    cancelled: false,
    onCancel: null,
    cancel() {
      if (this.cancelled) return
      this.cancelled = true
      const fn = this.onCancel
      this.onCancel = null
      if (fn) {
        try {
          fn()
        } catch {
          /* the renderer is already gone - nothing to cancel */
        }
      }
    },
  }
}

function touch(map, key, value) {
  map.delete(key)
  map.set(key, value)
}

export function createPagePreloader(options = {}) {
  const {
    render,
    onFull = () => {},
    onDraft = () => {},
    onEvict = () => {},
    onDemote = () => {},
    onMetric = () => {},
    ahead = PRELOAD_AHEAD,
    behind = PRELOAD_BEHIND,
    fullWindow = PRELOAD_FULL_WINDOW,
    maxConcurrent = PRELOAD_MAX_CONCURRENT,
    maxFull = PRELOAD_MAX_FULL,
    maxDraft = PRELOAD_MAX_DRAFT,
  } = options

  const full = new Map()
  const draft = new Map()
  const queued = new Map()
  const running = new Map()
  const waiters = new Map()

  let active = 1
  let total = 0
  let aheadLimit = Math.max(1, ahead)
  let behindLimit = Math.max(0, behind)
  let fullWindowLimit = Math.max(0, fullWindow)
  let paused = false
  let disposed = false

  const report = (event) => {
    try {
      onMetric(event)
    } catch {
      /* metrics must never break reading */
    }
  }

  const dropJob = (n, quality, reason = 'replaced') => {
    const key = `${n}:${quality}`
    const wasQueued = queued.delete(key)
    const run = running.get(key)
    if (run) {
      run.signal.cancel()
      running.delete(key)
    }
    if (wasQueued || run) {
      report({ type: 'job_cancelled', page: n, quality, reason })
    }
  }

  const withinWindow = (n) => Math.abs(n - active) <= aheadLimit + behindLimit + 1

  const prune = () => {
    for (const [key, job] of queued) {
      if (!withinWindow(job.n)) {
        queued.delete(key)
        report({ type: 'job_cancelled', page: job.n, quality: job.quality, reason: 'window' })
      }
    }
    for (const [key, run] of running) {
      if (!withinWindow(run.job.n)) {
        run.signal.cancel()
        running.delete(key)
        report({ type: 'job_cancelled', page: run.job.n, quality: run.job.quality, reason: 'window' })
      }
    }
  }

  const enqueue = (n, quality, attempts = 0) => {
    const key = `${n}:${quality}`
    if (queued.has(key) || running.has(key)) {
      report({ type: 'duplicate_avoided', page: n, quality })
      return
    }
    if (quality === QUALITY_FULL) {
      if (full.has(n)) {
        report({ type: 'duplicate_avoided', page: n, quality })
        return
      }
      dropJob(n, QUALITY_DRAFT, 'upgraded')
    } else if (full.has(n) || draft.has(n)) {
      report({ type: 'duplicate_avoided', page: n, quality })
      return
    }
    queued.set(key, { key, n, quality, attempts })
  }

  // The visible page is always the highest priority. Full-quality pages win
  // over drafts at the same distance, and pages ahead of the reader win over
  // pages behind it.
  const jobScore = (job) => {
    const distance = Math.abs(job.n - active)
    const ahead = job.n >= active ? 0 : 2
    const quality = job.quality === QUALITY_FULL ? 0 : 10
    return distance * 100 + ahead * 10 + quality
  }

  const pickNext = () => {
    let best = null
    let bestScore = Infinity
    for (const job of queued.values()) {
      const score = jobScore(job)
      if (score < bestScore) {
        bestScore = score
        best = job
      }
    }
    return best
  }

  const notify = (n, quality) => {
    const set = waiters.get(n)
    if (!set) return
    for (const waiter of [...set]) {
      if (waiter.need === QUALITY_FULL && quality !== QUALITY_FULL) continue
      waiter.fn(true)
    }
  }

  const evict = () => {
    while (draft.size > maxDraft) {
      const oldest = draft.keys().next().value
      const url = draft.get(oldest)
      draft.delete(oldest)
      onEvict(oldest, QUALITY_DRAFT, url)
    }
    while (full.size > maxFull) {
      let victim = null
      for (const n of full.keys()) {
        if (Math.abs(n - active) <= Math.max(1, fullWindowLimit)) continue
        victim = n
        break
      }
      if (victim === null) break
      const url = full.get(victim)
      full.delete(victim)
      onEvict(victim, QUALITY_FULL, url)
    }
  }

  const start = (job) => {
    const signal = createSignal()
    const startedAt = typeof performance !== 'undefined' ? performance.now() : Date.now()
    running.set(job.key, { job, signal })
    report({
      type: 'render_start',
      page: job.n,
      quality: job.quality,
      active,
      queued: queued.size,
      running: running.size,
    })
    let pending
    try {
      pending = Promise.resolve(render(job.n, { quality: job.quality, signal }))
    } catch (err) {
      pending = Promise.reject(err)
    }
    pending
      .then((result) => {
        const url = typeof result === 'string' ? result : result?.url
        const cached = typeof result === 'object' && result?.cached === true
        if (disposed || signal.cancelled || !url) return
        if (job.quality === QUALITY_FULL) {
          if (draft.has(job.n)) {
            const stale = draft.get(job.n)
            draft.delete(job.n)
            onEvict(job.n, QUALITY_DRAFT, stale)
          }
          touch(full, job.n, url)
          onFull(job.n, url)
        } else {
          if (full.has(job.n)) return
          touch(draft, job.n, url)
          onDraft(job.n, url)
        }
        const durationMs = (typeof performance !== 'undefined' ? performance.now() : Date.now()) - startedAt
        report({ type: 'render_end', page: job.n, quality: job.quality, durationMs, cached })
        report({ type: 'preload_success', page: job.n, quality: job.quality, cached })
        notify(job.n, job.quality)
        evict()
      })
      .catch(() => {
        if (disposed || signal.cancelled) return
        const durationMs = (typeof performance !== 'undefined' ? performance.now() : Date.now()) - startedAt
        report({ type: 'preload_error', page: job.n, quality: job.quality, durationMs })
        // A single transient failure (decode hiccup, network hiccup for ranged
        // loading) should not leave a blank page forever. Retry once, but only
        // while the page is still inside the reading window.
        if (job.attempts < 1 && withinWindow(job.n)) {
          setTimeout(() => {
            if (disposed || paused || !withinWindow(job.n)) return
            enqueue(job.n, job.quality, job.attempts + 1)
            pump()
          }, 120)
        }
      })
      .then(() => {
        running.delete(job.key)
        if (!disposed) pump()
      })
  }

  // When the reader jumps, let the visible page overtake stale background
  // work immediately instead of waiting for the old renders to finish.
  const preemptForVisiblePage = () => {
    if (running.size < maxConcurrent) return
    const urgent = [...queued.values()].some((job) => Math.abs(job.n - active) <= 1)
    if (!urgent) return
    const victims = [...running.entries()]
      .filter(([, run]) => Math.abs(run.job.n - active) > 1)
      .sort((a, b) => Math.abs(b[1].job.n - active) - Math.abs(a[1].job.n - active))
    for (const [key, run] of victims) {
      if (running.size < maxConcurrent) break
      run.signal.cancel()
      running.delete(key)
      report({ type: 'job_cancelled', page: run.job.n, quality: run.job.quality, reason: 'preempted' })
    }
  }

  function pump() {
    if (disposed || paused) return
    preemptForVisiblePage()
    while (running.size < maxConcurrent && queued.size > 0) {
      const job = pickNext()
      if (!job) break
      queued.delete(job.key)
      start(job)
    }
  }

  const plan = () => {
    if (disposed || !total) return
    prune()
    const lo = Math.max(1, active - behindLimit)
    const hi = Math.min(total, active + aheadLimit)
    for (let n = lo; n <= hi; n += 1) {
      const distance = Math.abs(n - active)
      if (distance <= fullWindowLimit) enqueue(n, QUALITY_FULL)
      else enqueue(n, QUALITY_DRAFT)
    }
    pump()
  }

  const setWindow = (next = {}) => {
    if (disposed) return
    const previousActive = active
    if (typeof next.active === 'number' && next.active > 0) active = next.active
    if (typeof next.total === 'number' && next.total > 0) total = next.total
    if (typeof next.ahead === 'number') aheadLimit = Math.max(1, next.ahead)
    if (typeof next.behind === 'number') behindLimit = Math.max(0, next.behind)
    if (typeof next.fullWindow === 'number') fullWindowLimit = Math.max(0, next.fullWindow)
    if (typeof next.paused === 'boolean') paused = next.paused
    plan()
    // A large jump makes all old background renders useless. Cancel them now
    // so the new visible page gets the render slots immediately.
    if (previousActive !== active && Math.abs(previousActive - active) > 1) {
      preemptForVisiblePage()
      pump()
    }
  }

  const setPaused = (value) => {
    paused = !!value
    if (!paused) pump()
  }

  const reset = ({ keepVisible = false } = {}) => {
    for (const run of running.values()) {
      run.signal.cancel()
      report({ type: 'job_cancelled', page: run.job.n, quality: run.job.quality, reason: 'reset' })
    }
    running.clear()
    for (const job of queued.values()) {
      report({ type: 'job_cancelled', page: job.n, quality: job.quality, reason: 'reset' })
    }
    queued.clear()
    for (const [n, url] of [...draft]) {
      draft.delete(n)
      onEvict(n, QUALITY_DRAFT, url)
    }
    for (const [n, url] of [...full]) {
      full.delete(n)
      if (keepVisible) {
        // Keep the old bitmap on screen as a draft while the page is
        // re-rendered at the new resolution - an empty leaf is worse than a
        // slightly stale one.
        touch(draft, n, url)
        onDemote(n, url)
      } else {
        onEvict(n, QUALITY_FULL, url)
      }
    }
    waiters.clear()
    evict()
  }

  const dispose = () => {
    reset()
    disposed = true
  }

  const isReady = (n) => full.has(n) || draft.has(n)
  const hasFull = (n) => full.has(n)
  const hasDraft = (n) => draft.has(n)

  const whenReady = (n, timeoutMs = 350, need = 'any') => {
    if (need === QUALITY_FULL ? hasFull(n) : isReady(n)) return Promise.resolve(true)
    return new Promise((resolve) => {
      let settled = false
      const finish = (ok) => {
        if (settled) return
        settled = true
        clearTimeout(timer)
        const set = waiters.get(n)
        if (set) {
          set.delete(waiter)
          if (!set.size) waiters.delete(n)
        }
        resolve(ok)
      }
      const timer = setTimeout(() => finish(false), timeoutMs)
      const waiter = { need, fn: finish }
      const set = waiters.get(n) || new Set()
      set.add(waiter)
      waiters.set(n, set)
    })
  }

  const whenFull = (n, timeoutMs = 350) => whenReady(n, timeoutMs, QUALITY_FULL)

  const stats = () => ({
    active,
    total,
    ahead: aheadLimit,
    behind: behindLimit,
    fullWindow: fullWindowLimit,
    paused,
    full: full.size,
    draft: draft.size,
    queued: queued.size,
    running: running.size,
  })

  return {
    setWindow,
    setPaused,
    reset,
    dispose,
    isReady,
    hasFull,
    hasDraft,
    whenReady,
    whenFull,
    stats,
    get ahead() { return aheadLimit },
    get total() { return total },
  }
}
