/**
 * Reader annotations (highlights + notes).
 *
 * Highlights are anchored by the selected quote plus the section they came
 * from, which survives re-pagination on another screen size (the quote is
 * re-found in the section text when the chapter is drawn). They are stored on
 * the device; bookmarks, by contrast, use the existing authenticated bookmark
 * API. Keeping annotations local avoids sending free-form study notes to the
 * server until a dedicated notes endpoint exists.
 */

export const HIGHLIGHT_COLORS = [
  { id: 'yellow', label: 'Yellow', value: '#fde68a' },
  { id: 'green', label: 'Green', value: '#bbf7d0' },
  { id: 'blue', label: 'Blue', value: '#bfdbfe' },
  { id: 'pink', label: 'Pink', value: '#fbcfe8' },
]

export const HIGHLIGHT_COLOR_IDS = HIGHLIGHT_COLORS.map((color) => color.id)

const KEY_PREFIX = 'pi-reader-annotations'
const MAX_QUOTE = 600
const MAX_NOTE = 4000

export function annotationStorageKey(userId, bookId) {
  return `${KEY_PREFIX}:${userId || 'guest'}:${bookId}`
}

function newId() {
  if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
    return crypto.randomUUID()
  }
  return `a-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 9)}`
}

/** @returns {Array<object>} */
export function loadAnnotations(userId, bookId) {
  if (typeof window === 'undefined' || !bookId) return []
  try {
    const raw = window.localStorage.getItem(annotationStorageKey(userId, bookId))
    if (!raw) return []
    const parsed = JSON.parse(raw)
    return Array.isArray(parsed) ? parsed.filter((item) => item && typeof item.quote === 'string') : []
  } catch {
    return []
  }
}

export function saveAnnotations(userId, bookId, list) {
  if (typeof window === 'undefined' || !bookId) return
  try {
    window.localStorage.setItem(annotationStorageKey(userId, bookId), JSON.stringify(list))
  } catch {
    // Local storage may be unavailable; annotations are then session-only.
  }
}

/**
 * @param {string[]|string} quote - selected text; a Range can return several
 *   text fragments, which are joined with a single space.
 */
export function createAnnotation({ quote, color = 'yellow', note = '', section = 0, page = 1 }) {
  const cleanQuote = String(quote || '').replace(/\s+/g, ' ').trim().slice(0, MAX_QUOTE)
  if (!cleanQuote) return null

  return {
    id: newId(),
    kind: note ? 'note' : 'highlight',
    quote: cleanQuote,
    color: HIGHLIGHT_COLOR_IDS.includes(color) ? color : 'yellow',
    note: String(note || '').trim().slice(0, MAX_NOTE),
    section: Number.isInteger(section) ? section : 0,
    page: Number.isInteger(page) ? page : 1,
    createdAt: new Date().toISOString(),
  }
}

export function addAnnotation(userId, bookId, annotation) {
  if (!annotation) return loadAnnotations(userId, bookId)
  const list = loadAnnotations(userId, bookId)
  const next = [annotation, ...list]
  saveAnnotations(userId, bookId, next)
  return next
}

export function removeAnnotation(userId, bookId, id) {
  const next = loadAnnotations(userId, bookId).filter((item) => item.id !== id)
  saveAnnotations(userId, bookId, next)
  return next
}

export function updateAnnotation(userId, bookId, id, patch) {
  const next = loadAnnotations(userId, bookId).map((item) => {
    if (item.id !== id) return item
    const note = patch.note !== undefined ? String(patch.note).trim().slice(0, MAX_NOTE) : item.note
    return {
      ...item,
      ...patch,
      note,
      kind: note ? 'note' : 'highlight',
    }
  })
  saveAnnotations(userId, bookId, next)
  return next
}
