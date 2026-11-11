import { stripCitations } from './citations.js'

/**
 * Plain-text book content is authored as a sequence of sections separated by a
 * line containing three or more equals signs. Older imports have no separator
 * at all, so headings such as "មេរៀនទី ១" or "Chapter 2" are used as a fallback.
 *
 * The reader never re-flows the raw string itself: parsing produces stable
 * sections that pagination, the table of contents, search and the AI assistant
 * all share.
 */

const SEPARATOR = /\n[ \t]*={3,}[ \t]*\n/
const HEADING_START = /^(មេរៀនទី|ជំពូក|Chapter|Lesson|Unit|Part|Section)(?=\s|$)/i
const TITLE_PUNCTUATION = /[.។]$/
const MAX_TITLE_LENGTH = 120

function normalizeText(content) {
  return String(content || '').replace(/\r\n?/g, '\n').trim()
}

function isHeadingLine(line) {
  const value = line.trim()
  return (
    value.length > 0 &&
    value.length <= MAX_TITLE_LENGTH &&
    HEADING_START.test(value)
  )
}

function looksLikeTitle(line) {
  const value = line.trim()
  return (
    value.length > 0 &&
    value.length <= MAX_TITLE_LENGTH &&
    !TITLE_PUNCTUATION.test(value)
  )
}

/** Split a raw content string into section strings, keeping source page order. */
function splitSectionChunks(text) {
  if (SEPARATOR.test(text)) {
    return text.split(SEPARATOR)
  }

  const lines = text.split('\n')
  const starts = []
  lines.forEach((line, index) => {
    if (!isHeadingLine(line)) return
    // A heading owns its own line, separated from the previous paragraph.
    if (index === 0 || lines[index - 1].trim() === '') starts.push(index)
  })

  if (!starts.length) return [text]

  const chunks = []
  if (starts[0] > 0) chunks.push(lines.slice(0, starts[0]).join('\n'))
  starts.forEach((start, index) => {
    const end = starts[index + 1] ?? lines.length
    chunks.push(lines.slice(start, end).join('\n'))
  })
  return chunks
}

/**
 * Turn inline markdown-ish text into renderable blocks. This is intentionally a
 * small subset (headings, lists, quotes, display maths, paragraphs) rather than
 * a full Markdown parser: book content is prose, and keeping the parser tiny
 * means nothing can inject HTML.
 *
 * @param {string} source
 * @returns {Array<object>}
 */
export function parseBlocks(source) {
  const lines = String(source || '').split('\n')
  const blocks = []
  let paragraph = []
  let list = null

  const flushParagraph = () => {
    if (!paragraph.length) return
    blocks.push({ type: 'p', text: paragraph.join(' ') })
    paragraph = []
  }

  const flushList = () => {
    if (!list) return
    blocks.push(list)
    list = null
  }

  const flushAll = () => {
    flushParagraph()
    flushList()
  }

  for (let index = 0; index < lines.length; index += 1) {
    const line = lines[index].trim()

    if (!line) {
      flushAll()
      continue
    }

    if (line.startsWith('$$')) {
      flushAll()
      let math = line.slice(2)
      if (math.endsWith('$$')) {
        math = math.slice(0, -2)
      } else {
        const parts = [math]
        let cursor = index + 1
        while (cursor < lines.length && !lines[cursor].trim().endsWith('$$')) {
          parts.push(lines[cursor])
          cursor += 1
        }
        if (cursor < lines.length) {
          parts.push(lines[cursor].trim().slice(0, -2))
        }
        math = parts.join('\n')
        index = cursor
      }
      if (math.trim()) blocks.push({ type: 'math', text: math.trim() })
      continue
    }

    const heading = line.match(/^(#{1,4})\s+(.+?)\s*#*$/)
    if (heading) {
      flushAll()
      blocks.push({
        type: 'heading',
        level: Math.min(3, heading[1].length + 1),
        text: heading[2],
      })
      continue
    }

    if (/^([-*_])\1{2,}$/.test(line)) {
      flushAll()
      blocks.push({ type: 'hr' })
      continue
    }

    const bullet = line.match(/^[-*•]\s+(.+)$/)
    if (bullet) {
      flushParagraph()
      if (!list || list.type !== 'ul') {
        flushList()
        list = { type: 'ul', items: [] }
      }
      list.items.push(bullet[1])
      continue
    }

    const ordered = line.match(/^\d+[.)]\s+(.+)$/)
    if (ordered) {
      flushParagraph()
      if (!list || list.type !== 'ol') {
        flushList()
        list = { type: 'ol', items: [], start: Number(line.match(/^\d+/)[0]) || 1 }
      }
      list.items.push(ordered[1])
      continue
    }

    const quote = line.match(/^>\s?(.*)$/)
    if (quote) {
      flushAll()
      const quoted = [quote[1]]
      let cursor = index + 1
      while (cursor < lines.length) {
        const next = lines[cursor].trim()
        const match = next.match(/^>\s?(.*)$/)
        if (!match) break
        quoted.push(match[1])
        cursor += 1
      }
      index = cursor - 1
      blocks.push({ type: 'quote', text: quoted.join(' ') })
      continue
    }

    flushList()
    paragraph.push(line)
  }

  flushAll()
  return blocks
}

/** @returns {Array<{title: string, blocks: object[], page: number}>} */
export function parseSections(content, { bookTitle = '' } = {}) {
  const text = normalizeText(content)
  if (!text) return []

  return splitSectionChunks(text)
    .map((chunk) => chunk.trim())
    .filter(Boolean)
    .map((chunk, index) => {
      const lines = chunk.split('\n')
      const first = lines[0].trim().replace(/^#{1,4}\s+/, '')
      const rest = lines.slice(1).join('\n').trim()

      let title = ''
      let body = chunk

      if (rest && looksLikeTitle(first)) {
        title = first
        body = rest
      } else if (rest && isHeadingLine(first)) {
        title = first
        body = rest
      }

      const blocks = parseBlocks(body)

      return {
        index,
        page: index + 1,
        title: title || (index === 0 && bookTitle ? bookTitle : ''),
        blocks,
      }
    })
}

/** Plain text for search, AI context and word counts. */
export function sectionText(section) {
  if (!section || !Array.isArray(section.blocks)) return ''
  return section.blocks
    .map((block) => {
      if (block.type === 'ul' || block.type === 'ol') {
        return block.items.map((item) => `• ${item}`).join('\n')
      }
      if (block.type === 'hr') return ''
      return block.text || ''
    })
    .filter(Boolean)
    .join('\n\n')
}

export function sectionWordCount(section) {
  return sectionText(section).split(/\s+/).filter(Boolean).length
}

const REFERENCE_TITLE =
  /^(references?|bibliography|works cited|ឯកសារយោង|បញ្ជីឯកសារយោង)$/i
const NUMBERED_ENTRY = /^\s*(?:\[(\d+)\]|(\d+)\s*[.)]|\((\d+)\))\s*(.+)$/

/**
 * Build a citation-number -> reference-text map from a References/Bibliography
 * section. Books without such a section still get clickable citation markers,
 * but the popover has nothing better than "source unavailable" to show.
 *
 * @returns {Map<number, string>}
 */
export function extractReferences(sections) {
  const references = new Map()
  let active = false

  for (const section of sections || []) {
    const sectionTitle = String(section?.title || '').trim()
    if (REFERENCE_TITLE.test(sectionTitle)) active = true
    else if (active && sectionTitle) active = false
    if (!active) continue

    for (const block of section.blocks || []) {
      if (block.type === 'heading') {
        if (REFERENCE_TITLE.test(String(block.text || '').trim())) active = true
        continue
      }

      if (block.type === 'ul' || block.type === 'ol') {
        block.items.forEach((item, index) => {
          const match = String(item).match(NUMBERED_ENTRY)
          if (match) {
            references.set(
              Number(match[1] || match[2] || match[3]),
              String(match[4]).trim(),
            )
          } else if (block.type === 'ol') {
            references.set(
              (Number(block.start) || 1) + index,
              String(item).trim(),
            )
          }
        })
        continue
      }

      if (block.type === 'p') {
        const text = String(block.text || '')
        // Several "[1] … [2] …" entries can end up in one paragraph because
        // they do not have a blank line between them.
        const marked = [...text.matchAll(/\[(\d+)\]\s*([\s\S]*?)(?=\s*\[\d+\]|$)/g)]
        if (marked.length) {
          marked.forEach((match) => {
            const value = match[2].trim()
            if (value) references.set(Number(match[1]), value)
          })
          continue
        }

        const match = text.match(NUMBERED_ENTRY)
        if (match) {
          references.set(
            Number(match[1] || match[2] || match[3]),
            String(match[4]).trim(),
          )
        }
      }
    }
  }

  return references
}

/**
 * Find all occurrences of a query across sections. Matching is
 * case-insensitive and citation markers are hidden from both the haystack and
 * the snippet so results read like the page.
 *
 * @returns {Array<{section: number, page: number, title: string, snippet: string}>}
 */
export function searchSections(sections, query, limit = 40) {
  const needle = String(query || '').trim()
  if (!needle) return []
  const needleLower = needle.toLocaleLowerCase()
  const results = []

  for (const section of sections) {
    const text = stripCitations(sectionText(section)).replace(/\s+/g, ' ')
    const haystack = text.toLocaleLowerCase()
    let from = 0

    while (results.length < limit) {
      const at = haystack.indexOf(needleLower, from)
      if (at === -1) break

      const start = Math.max(0, at - 70)
      const end = Math.min(text.length, at + needle.length + 90)
      const prefix = start > 0 ? '…' : ''
      const suffix = end < text.length ? '…' : ''

      results.push({
        section: section.index,
        page: section.page,
        title: section.title,
        snippet: `${prefix}${text.slice(start, end).trim()}${suffix}`,
      })

      from = at + Math.max(needle.length, 1)
    }

    if (results.length >= limit) break
  }

  return results
}


