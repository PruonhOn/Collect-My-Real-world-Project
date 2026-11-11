import { useCallback, useEffect, useRef, useState } from 'react'

const clamp = (value, min, max) => Math.min(max, Math.max(min, value))

/**
 * Horizontal, column-based pagination.
 *
 * The page is a CSS multi-column box with a fixed height. The browser fills a
 * column until it is full, then starts the next one, so chapters flow instead of
 * being cut into arbitrary 250-word chunks. We only have to measure the last
 * block to know how many columns exist, then translate a page number into a
 * scroll offset.
 *
 * @param {object} params
 * @param {import('react').RefObject<HTMLElement>} params.viewportRef Horizontal scroller.
 * @param {import('react').RefObject<HTMLElement>} params.contentRef Multi-column element.
 * @param {boolean} params.enabled False while loading or in scroll mode.
 * @param {number} params.visibleCount 1 on phones/tablets, 2 for a desktop spread.
 * @param {string|number} params.contentKey Changes whenever the laid-out text does.
 * @param {number} [params.gap] Gap between columns in px.
 * @param {number} [params.initialPage] Saved page for the previous layout.
 * @param {number} [params.initialTotal] Page count the saved page belonged to.
 */
export function useColumnPagination({
  viewportRef,
  contentRef,
  enabled,
  visibleCount,
  contentKey,
  gap = 64,
  initialPage = 1,
  initialTotal = 0,
}) {
  const [page, setPage] = useState(1)
  const [pageCount, setPageCount] = useState(1)
  const [ready, setReady] = useState(false)

  const pageRef = useRef(1)
  const pageCountRef = useRef(1)
  const restoredRef = useRef(false)
  const behaviorRef = useRef('auto')
  const fontsSettledRef = useRef(
    typeof document === 'undefined' ||
      !document.fonts ||
      document.fonts.status === 'loaded',
  )
  const interactedRef = useRef(false)

  useEffect(() => {
    pageRef.current = page
  }, [page])

  const scrollToPage = useCallback(
    (next, behavior = 'auto') => {
      const viewport = viewportRef.current
      if (!viewport) return
      const target = clamp(next, 1, pageCountRef.current)
      const step = (viewport.clientWidth + gap) * visibleCount
      viewport.scrollTo({
        left: (target - 1) * step,
        behavior,
      })
    },
    [gap, viewportRef, visibleCount],
  )

  const goToPage = useCallback(
    (next, options = {}) => {
      const target = clamp(Number(next) || 1, 1, pageCountRef.current)
      interactedRef.current = true
      behaviorRef.current = options.instant ? 'auto' : 'smooth'
      if (target === pageRef.current) {
        scrollToPage(target, behaviorRef.current)
        return
      }
      setPage(target)
    },
    [scrollToPage],
  )

  const goNext = useCallback(() => goToPage(pageRef.current + 1), [goToPage])
  const goPrev = useCallback(() => goToPage(pageRef.current - 1), [goToPage])

  // Keep the scroll position in sync with the page number. An instant restore
  // avoids animating through the whole book on first load.
  useEffect(() => {
    if (!enabled || !ready) return
    scrollToPage(page, behaviorRef.current)
    behaviorRef.current = 'auto'
  }, [enabled, page, ready, scrollToPage])

  const measure = useCallback(() => {
    const viewport = viewportRef.current
    const content = contentRef.current
    if (!viewport || !content) return

    const columnStep = (viewport.clientWidth + gap) / Math.max(1, visibleCount)
    // scrollWidth is laid out in CSS pixels, so it stays correct when the
    // reader zooms the book with a transform. Measuring the last block's
    // bounding rect would inflate the column count by the zoom factor.
    const columns =
      columnStep > 0
        ? Math.max(1, Math.ceil(viewport.scrollWidth / columnStep))
        : 1

    const count = Math.max(1, Math.ceil(columns / Math.max(1, visibleCount)))
    pageCountRef.current = count
    setPageCount(count)

    // Map the saved page by ratio. A reader who was halfway through on a phone
    // lands halfway through on a desktop instead of on an unrelated page.
    // Wait for web fonts before restoring: fallback fonts can produce a very
    // different page count and would clamp the saved position to page 1.
    const canRestore = fontsSettledRef.current || !document.fonts
    if (!restoredRef.current && canRestore && !interactedRef.current) {
      restoredRef.current = true
      const savedPage = Math.max(1, Number(initialPage) || 1)
      const total = Number(initialTotal) || 0
      let restored
      if (total > 1) {
        const ratio = clamp((savedPage - 1) / (total - 1), 0, 1)
        restored = clamp(Math.round(ratio * (count - 1)) + 1, 1, count)
      } else {
        restored = clamp(savedPage, 1, count)
      }
      pageRef.current = restored
      setPage(restored)
      behaviorRef.current = 'auto'
    } else if (!restoredRef.current) {
      setPage((current) => {
        const next = clamp(current, 1, count)
        pageRef.current = next
        return next
      })
    } else {
      setPage((current) => {
        const next = clamp(current, 1, count)
        pageRef.current = next
        return next
      })
    }

    setReady(true)
  }, [contentRef, gap, initialPage, initialTotal, viewportRef, visibleCount])

  // Re-measure when the text, viewport, type settings or web fonts change.
  useEffect(() => {
    if (!enabled) return undefined

    const viewport = viewportRef.current
    let firstFrame = 0
    let secondFrame = 0

    const schedule = () => {
      cancelAnimationFrame(firstFrame)
      cancelAnimationFrame(secondFrame)
      firstFrame = requestAnimationFrame(() => {
        secondFrame = requestAnimationFrame(measure)
      })
    }

    schedule()

    const observer = new ResizeObserver(schedule)
    if (viewport) observer.observe(viewport)
    window.addEventListener('resize', schedule)
    window.addEventListener('orientationchange', schedule)

    const settle = () => {
      fontsSettledRef.current = true
      schedule()
    }

    if (document.fonts?.ready) {
      document.fonts.ready.then(settle).catch(() => {})
    }
    const settleTimer = window.setTimeout(settle, 800)

    return () => {
      cancelAnimationFrame(firstFrame)
      cancelAnimationFrame(secondFrame)
      window.clearTimeout(settleTimer)
      observer.disconnect()
      window.removeEventListener('resize', schedule)
      window.removeEventListener('orientationchange', schedule)
    }
  }, [contentKey, enabled, measure, viewportRef])

  // Scrolling with a trackpad, touch or the scrollbar updates the page number.
  useEffect(() => {
    const viewport = viewportRef.current
    if (!enabled || !viewport) return undefined

    let frame = 0
    const onScroll = () => {
      cancelAnimationFrame(frame)
      frame = requestAnimationFrame(() => {
        const step = (viewport.clientWidth + gap) * Math.max(1, visibleCount)
        if (step <= 0) return
        const next = clamp(
          Math.round(viewport.scrollLeft / step) + 1,
          1,
          pageCountRef.current,
        )
        if (next !== pageRef.current) {
          interactedRef.current = true
          pageRef.current = next
          setPage(next)
        }
      })
    }

    viewport.addEventListener('scroll', onScroll, { passive: true })
    return () => {
      cancelAnimationFrame(frame)
      viewport.removeEventListener('scroll', onScroll)
    }
  }, [enabled, gap, viewportRef, visibleCount])

  return { page, pageCount, ready, goToPage, goNext, goPrev }
}
