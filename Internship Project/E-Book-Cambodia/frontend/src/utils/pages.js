export function splitContentIntoPages(content, wordsPerPage = 250) {
  const text = String(content || '').trim()
  if (!text) return []

  const pages = text.split(/\n{1,}\s*={3,}\s*\n{1,}/u).filter((p) => p.trim().length > 0)

  if (pages.length > 1) return pages

  const words = text.split(/\s+/u).filter(Boolean)
  let current = []

  for (const word of words) {
    current.push(word)
    if (current.length >= wordsPerPage) {
      pages.push(current.join(' '))
      current = []
    }
  }

  if (current.length > 0) {
    pages.push(current.join(' '))
  }

  return pages
}