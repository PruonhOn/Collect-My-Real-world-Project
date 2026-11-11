import { useLanguage } from '../context/LanguageContext'

export default function Pagination({ meta, onPageChange }) {
  const { t } = useLanguage()
  if (!meta || meta.last_page <= 1) return null
  return (
    <div className="flex items-center justify-center gap-3 mt-8">
      <button
        disabled={!meta.prev_page_url}
        onClick={() => onPageChange(meta.current_page - 1)}
        className="inline-flex items-center gap-1.5 px-4 py-2 border border-gray-300 rounded-xl text-sm font-medium disabled:opacity-40 hover:bg-gray-50 hover:border-gray-400"
      >
        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
        </svg>
        {t('prev')}
      </button>
      <span className="text-sm text-gray-600 px-2">
        {t('pageLabel')} {meta.current_page} / {meta.last_page}
      </span>
      <button
        disabled={!meta.next_page_url}
        onClick={() => onPageChange(meta.current_page + 1)}
        className="inline-flex items-center gap-1.5 px-4 py-2 border border-gray-300 rounded-xl text-sm font-medium disabled:opacity-40 hover:bg-gray-50 hover:border-gray-400"
      >
        {t('next')}
        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
        </svg>
      </button>
    </div>
  )
}
