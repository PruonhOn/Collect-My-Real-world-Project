import { useLanguage } from '../context/LanguageContext'

const STYLES = {
  draft: 'bg-gray-100 text-gray-700',
  pending: 'bg-amber-100 text-amber-700',
  approved: 'bg-blue-100 text-blue-700',
  rejected: 'bg-red-100 text-red-700',
  published: 'bg-emerald-100 text-emerald-700',
}

/**
 * The editorial status of a book, shown wherever a book's state matters:
 * the author dashboard, the author's own book list and the admin review queue.
 *
 * Renders from `status`, falling back to the legacy `is_published` flag so a
 * book that predates the workflow still gets a sensible label.
 */
export default function BookStatusBadge({ book, className = '' }) {
  const { t } = useLanguage()

  const status = book?.status || (book?.is_published ? 'published' : 'draft')

  return (
    <span
      className={`inline-block px-2.5 py-1 rounded-full text-xs font-medium ${
        STYLES[status] || STYLES.draft
      } ${className}`}
    >
      {t(`status_${status}`)}
    </span>
  )
}
