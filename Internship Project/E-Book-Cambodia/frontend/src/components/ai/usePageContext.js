import { useCallback, useEffect, useMemo, useRef, useState } from 'react'

/**
 * Turns the page the reader is currently looking at into something the AI
 * assistant can reason about.
 *
 * Two sources, in priority order:
 *
 *  1. The PDF text layer, read straight from pdf.js. Cheap, exact, and it
 *     works offline.
 *  2. A freshly rendered image of the page, which the backend's vision model
 *     reads (OCR). This is the fallback that makes scanned PDFs work, and it
 *     never asks the reader to select text by hand.
 *
 * The extractor only ever returns content the reader can already see. It has
 * no access to file URLs, storage keys or the book file itself.
 */

/** Below this many characters a text layer is treated as a scanned page. */
const MIN_USABLE_TEXT_CHARS = 24

/** Target width in pixels for the OCR capture. Large enough for small print. */
const OCR_TARGET_WIDTH = 1600
const OCR_MAX_WIDTH = 2400
const OCR_MAX_PIXELS = 6_000_000
const OCR_QUALITY = 0.9

/** Extracted pages kept in memory. Bounded because OCR captures are large. */
const MAX_CACHED_PAGES = 8

/**
 * Scripts written without spaces between words. Runs in these scripts are
 * concatenated directly; everything else gets a space.
 */
const NO_SPACE_SCRIPTS = [/\p{Script=Khmer}/u, /\p{Script=Han}/u]

/** Whether two adjacent characters belong to a no-space script. */
function joinsWithoutSpace(left, right) {
  return NO_SPACE_SCRIPTS.some((script) => script.test(left) && script.test(right))
}

/**
 * Join pdf.js text items into readable text.
 *
 * pdf.js splits a page into positioned glyph runs, so items must be stitched
 * back together. Line breaks come from the items' own `hasEOL` flag, which is
 * what preserves headings and lists, and script detection decides whether two
 * adjacent runs need a space - Khmer and Chinese are written without them,
 * everything else is not.
 */
export function textItemsToString(items) {
  if (!Array.isArray(items)) return ''

  const lines = []
  let current = ''

  items.forEach((item) => {
    if (typeof item?.str !== 'string') return

    const next = item.str
    if (current !== '' && !/\s$/.test(current) && !/^\s/.test(next)
      && !joinsWithoutSpace(current.slice(-1), next.slice(0, 1))) {
      current += ' '
    }

    current += next

    if (item.hasEOL) {
      lines.push(current.trim())
      current = ''
    }
  })

  if (current.trim()) lines.push(current.trim())

  return lines
    .filter((line) => line.length > 0)
    .join('\n')
    .replace(/[ \t]{2,}/g, ' ')
    .trim()
}

/**
 * Render one PDF page to a data URL suitable for OCR.
 */
async function renderPageImage(pdfDoc, pageNumber) {
  const page = await pdfDoc.getPage(pageNumber)
  const base = page.getViewport({ scale: 1 })

  const scale = Math.min(
    OCR_MAX_WIDTH / base.width,
    Math.sqrt(OCR_MAX_PIXELS / (base.width * base.height)),
    Math.max(OCR_TARGET_WIDTH / base.width, 1)
  )

  const viewport = page.getViewport({ scale })
  const canvas = document.createElement('canvas')
  canvas.width = Math.ceil(viewport.width)
  canvas.height = Math.ceil(viewport.height)

  const context = canvas.getContext('2d', { alpha: false })
  if (!context) return null

  // Scans are often photographed on a dark surface; a white backdrop keeps
  // the page legible for the model.
  context.fillStyle = '#ffffff'
  context.fillRect(0, 0, canvas.width, canvas.height)

  await page.render({ canvasContext: context, viewport }).promise

  try {
    return canvas.toDataURL('image/jpeg', OCR_QUALITY)
  } catch {
    return null
  }
}

/**
 * @param {object} params
 * @param {object|null} params.pdfDoc  Loaded pdf.js document, when reading a PDF.
 * @param {boolean} params.isPdf       Whether the book is a PDF.
 * @param {string[]} params.pages      Text pages, when the book is stored as text.
 * @param {number} params.pageNumber   The page the reader is on.
 */
export function usePageContext({ pdfDoc, isPdf, pages, pageNumber }) {
  const target = Math.max(1, Math.floor(pageNumber) || 1)

  // Text books need no work at all: the page content is already in memory, so
  // it is derived during render rather than fetched in an effect.
  const textContext = useMemo(() => {
    if (isPdf) return null
    const text = Array.isArray(pages) ? pages[target - 1] ?? '' : ''
    return text.trim()
      ? { text, image: null, source: 'text_layer' }
      : { text: null, image: null, source: 'none' }
  }, [isPdf, pages, target])

  // Extracted pages are kept so flipping back to a page is instant. Held in
  // state rather than a ref so both the context and the "still working" flag
  // can be derived during render instead of costing extra render passes.
  const [pageCache, setPageCache] = useState(() => new Map())
  const reqId = useRef(0)

  const extract = useCallback(
    async (doc, pageNo) => {
      const page = await doc.getPage(pageNo)
      const content = await page.getTextContent()
      const text = textItemsToString(content?.items)

      if (text.length >= MIN_USABLE_TEXT_CHARS) {
        return { text, image: null, source: 'text_layer' }
      }

      return { text: null, image: await renderPageImage(doc, pageNo), source: 'ocr' }
    },
    []
  )

  const cachePage = useCallback((pageNo, value) => {
    setPageCache((prev) => {
      const next = new Map(prev)
      // Re-insert to mark the page as most recently used, then drop the
      // least recently used entries. OCR images are large, so the cache
      // stays deliberately small.
      next.delete(pageNo)
      next.set(pageNo, value)
      while (next.size > MAX_CACHED_PAGES) {
        next.delete(next.keys().next().value)
      }
      return next
    })
  }, [])

  // Keyed by page, so a result can never leak into a page the reader is not on.
  const cachedContext = isPdf ? pageCache.get(target) ?? null : null

  useEffect(() => {
    if (!isPdf || !pdfDoc) return undefined

    // Already extracted: nothing to do, and no state to touch.
    if (pageCache.has(target)) return undefined

    let cancelled = false
    const id = reqId.current + 1
    reqId.current = id

    extract(pdfDoc, target)
      .then((result) => {
        if (cancelled || reqId.current !== id) return
        cachePage(target, result)
      })
      .catch(() => {
        if (cancelled || reqId.current !== id) return
        // Cached too, so a page that could not be read is reported once
        // instead of being retried on every unrelated page turn.
        cachePage(target, { text: null, image: null, source: 'none' })
      })

    return () => {
      cancelled = true
    }
  }, [pdfDoc, isPdf, target, extract, cachePage, pageCache])

  if (!isPdf) {
    return { context: textContext, isPreparing: false }
  }

  // Preparing means work is in flight: a document is open and this page has
  // neither produced context yet nor been read before.
  const isPreparing = !!pdfDoc && !cachedContext

  return { context: cachedContext, isPreparing }
}
