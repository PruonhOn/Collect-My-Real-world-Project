import BookCard from './BookCard'
import { useLanguage } from '../context/LanguageContext'

export default function BookGrid({ books, loading, emptyMessage }) {
  const { t } = useLanguage()
  const safeBooks = Array.isArray(books) ? books : []

  if (loading) {
    return (
      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
        {[...Array(8)].map((_, i) => (
          <div key={i} className="animate-pulse">
            <div className="aspect-[3/4] bg-gray-200 rounded-xl" />
            <div className="h-4 bg-gray-200 rounded mt-3 w-3/4" />
            <div className="h-3 bg-gray-200 rounded mt-2 w-1/2" />
          </div>
        ))}
      </div>
    )
  }

  if (safeBooks.length === 0) {
    return (
      <div className="text-center py-16">
        <svg className="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
        </svg>
        <p className="text-gray-500">{emptyMessage || t('noBooks')}</p>
      </div>
    )
  }

  return (
    <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
      {safeBooks.map((book) => (
        <BookCard key={book.id} book={book} />
      ))}
    </div>
  )
}