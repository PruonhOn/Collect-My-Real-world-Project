/**
 * Content pipeline helpers for manuscript imports.
 *
 * Imported books often still carry the editor's inline reference markers,
 * e.g. `[cite: 7]` or `[cite: 7, 9].`. Rendering those verbatim puts source
 * artifacts in front of the reader, so they are converted into a structured
 * citation token here and drawn as a superscript by the reader. Everything in
 * this module is pure so it can be unit tested without a DOM.
 */

const CITE_PATTERN = /\[\s*cite\s*:\s*([0-9]+(?:\s*[,;]\s*[0-9]+)*)\s*\]/gi

/** Punctuation that ends up after an inline marker in imported manuscripts. */
const TRAILING_PUNCTUATION = /^[.,;:!?។]/

/** Words that carry a citation but should not be separated by a space. */
function normalizeSpaces(value) {
  return value
    .replace(/[ \t]{2,}/g, ' ')
    .replace(/\s+([.,;:!?។])/g, '$1')
    .replace(/\(\s+/g, '(')
    .replace(/\s+\)/g, ')')
}

function parseIds(group) {
  return String(group)
    .split(/[,;]/)
    .map((part) => Number.parseInt(part.trim(), 10))
    .filter((id) => Number.isInteger(id) && id > 0)
}

function newPattern() {
  // A fresh instance keeps lastIndex state out of shared calls.
  return new RegExp(CITE_PATTERN.source, CITE_PATTERN.flags)
}

/**
 * Split text into ordered `text` and `cite` tokens.
 *
 * @param {string} input
 * @returns {Array<{type:'text', value:string}|{type:'cite', ids:number[]}>}
 */
export function tokenizeCitations(input) {
  const source = String(input ?? '')
  const tokens = []
  const pattern = newPattern()
  let cursor = 0
  let match

  while ((match = pattern.exec(source)) !== null) {
    const ids = parseIds(match[1])
    if (ids.length === 0) continue

    let text = source.slice(cursor, match.index)
    let end = match.index + match[0].length

    // `text [cite: 7].` -> `text.` + superscript, never a floating full stop
    // after the superscript. A citation also attaches to the word before it
    // rather than sitting behind a stray space.
    const trailing = source.slice(end).match(TRAILING_PUNCTUATION)
    text = text.replace(/\s+$/, '')
    if (trailing) {
      text += trailing[0]
      end += trailing[0].length
    }

    if (text) tokens.push({ type: 'text', value: text })
    tokens.push({ type: 'cite', ids })
    cursor = end
  }

  if (cursor < source.length) {
    tokens.push({ type: 'text', value: source.slice(cursor) })
  }

  return tokens
}

/**
 * Strip the editor's markers without losing words. Used for search indexes,
 * plain-text copy and the AI context.
 *
 * @param {string} input
 * @returns {string}
 */
export function stripCitations(input) {
  return normalizeSpaces(
    tokenizeCitations(input)
      .filter((token) => token.type === 'text')
      .map((token) => token.value)
      .join(''),
  ).trim()
}

/**
 * Plain-text representation with numbered markers, e.g. `Water boils.[7]`.
 * Kept for exports and for tests; the reader renders `tokenizeCitations`.
 *
 * @param {string} input
 * @returns {string}
 */
export function sanitizeCitations(input) {
  const out = tokenizeCitations(input)
    .map((token) => {
      if (token.type === 'text') return token.value
      return token.ids.map((id) => `[${id}]`).join('')
    })
    .join('')

  return out.replace(/[ \t]{2,}/g, ' ')
}

/**
 * Count the words a reader will actually see, ignoring markers and citations.
 *
 * @param {string} input
 * @returns {number}
 */
export function countWords(input) {
  const text = stripCitations(input)
  if (!text) return 0
  return text.split(/\s+/u).filter(Boolean).length
}
