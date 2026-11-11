/**
 * Client-side helpers for building a Table of Contents when a PDF has no
 * embedded bookmarks.
 *
 * The backend reads a document's real PDF outline (the reliable source). When
 * that is empty - which is typical for scanned files and many exported
 * documents - the editor can fall back to a heuristic scan of the page text
 * for heading-like lines. A file where no extractable text is found at all is
 * reported as "scanned", so the UI can tell the admin to add entries by hand
 * instead of implying a silent failure.
 */

const MIN_TEXT_CHARS = 20
const MAX_SCAN_PAGES = 40
const MAX_HEADINGS = 120

const HEADING_PATTERNS = [
  /^(chapter|section|part|unit|lesson|appendix|contents|introduction|conclusion|preface|glossary|references|index|bibliography)\b/i,
  /^(บทที่|บท|บทนำ|ภาคผนวก|สารบัญ|บทเรียน|หน่วย|ส่วนที่)\s*\S/,
  /^[0-9]+(\.[0-9]+)*\.?\s+\S/,
]

/**
 * Extract heading-like entries from a PDF's text layer.
 *
 * @param {Uint8Array} bytes raw PDF bytes
 * @param {object} options
 * @param {function} options.getDocument pdf.js getDocument (injected so this
 *   module has no hard pdfjs-dist dependency and stays easy to test)
 * @returns {Promise<{headings: Array<{title: string, page: number, depth: number}>, scanned: boolean, textFound: boolean}>}
 */
export async function extractHeadingsFromPdf(bytes, { getDocument } = {}) {
  if (typeof getDocument !== 'function') {
    return { headings: [], scanned: true, textFound: false }
  }

  let doc = null
  try {
    doc = await getDocument({ data: bytes }).promise
    const pageLimit = Math.min(doc.numPages, MAX_SCAN_PAGES)
    const headings = []
    let textFound = false

    for (let pageNumber = 1; pageNumber <= pageLimit; pageNumber += 1) {
      const page = await doc.getPage(pageNumber)
      let textContent
      try {
        textContent = await page.getTextContent()
      } catch {
        continue
      }

      const lines = groupIntoLines(textContent?.items || [])
      const pageText = lines.join(' ').trim()

      if (pageText.length >= MIN_TEXT_CHARS) {
        textFound = true
      }

      if (lines.length === 0) continue

      const bodySize = medianFontSize(textContent?.items || [])

      for (const line of lines) {
        const title = cleanTitle(line)
        if (!title || title.length > 120) continue
        if (!looksLikeHeading(line, bodySize)) continue

        headings.push({ title, page: pageNumber, depth: depthFor(title) })
        if (headings.length >= MAX_HEADINGS) break
      }

      if (headings.length >= MAX_HEADINGS) break
      page.cleanup?.()
    }

    return { headings, scanned: !textFound, textFound }
  } catch {
    return { headings: [], scanned: true, textFound: false }
  } finally {
    void doc?.destroy?.()
  }
}

function groupIntoLines(items) {
  const lines = []
  let current = ''
  let lastY = null

  for (const item of items) {
    const text = typeof item?.str === 'string' ? item.str : ''
    const y = item?.transform?.[5]

    if (lastY !== null && y !== null && Math.abs(y - lastY) > 2) {
      if (current.trim()) lines.push(current.trim())
      current = ''
    }

    current += text
    if (item?.hasEOL) {
      if (current.trim()) lines.push(current.trim())
      current = ''
      lastY = null
    } else {
      lastY = y ?? lastY
    }
  }

  if (current.trim()) lines.push(current.trim())

  return lines
}

function medianFontSize(items) {
  const sizes = items
    .map((item) => Math.abs(item?.transform?.[3] ?? item?.transform?.[0] ?? 0))
    .filter((size) => size > 0)
    .sort((a, b) => a - b)

  if (sizes.length === 0) return 0

  return sizes[Math.floor(sizes.length / 2)]
}

function looksLikeHeading(line, bodySize) {
  const text = line.trim()
  if (!text) return false

  if (HEADING_PATTERNS.some((pattern) => pattern.test(text))) return true

  const height = Number(line.height || 0)
  if (bodySize > 0 && height >= bodySize * 1.25 && text.length <= 80) return true

  return false
}

function depthFor(title) {
  const match = title.match(/^([0-9]+(?:\.[0-9]+)*)/)
  if (!match) return 1

  return Math.min(match[1].split('.').length, 4)
}

function cleanTitle(line) {
  return line
    .replace(/^[\s•·▪◦*-]+/, '')
    .replace(/\s+/g, ' ')
    .replace(/\s*\.{2,}\s*\d+\s*$/, '')
    .trim()
}

export const HEADING_MAX_PAGES = MAX_SCAN_PAGES
