import { Link } from 'react-router-dom'
import { useLanguage } from '../context/LanguageContext'

function formatPrice(price) {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(price)
}

export default function BookCard({ book }) {
  const { t } = useLanguage()
  const free = book.is_free || Number(book.price) === 0

  return (
    <Link
      to={`/books/${book.id}`}
      className="group bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1 border border-gray-100 flex flex-col transition-all duration-300 ease-out"
    >
      <div className="relative aspect-[3/4] bg-gradient-to-br from-indigo-100 via-purple-50 to-purple-100 overflow-hidden">
        {book.cover_image ? (
          <img
            src={`${import.meta.env.VITE_API_URL?.replace('/api/v1', '') || ''}/storage/${book.cover_image}`}
            alt={book.title}
            loading="lazy"
            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out"
          />
        ) : (
          <div className="w-full h-full flex items-center justify-center">
            <span className="text-4xl font-bold text-indigo-300 group-hover:scale-110 transition-transform duration-500">📖</span>
          </div>
        )}
        {free && (
          <span className="absolute top-2 left-2 bg-emerald-600 text-white text-xs font-semibold px-2 py-1 rounded-full shadow-sm">
            {t('free')}
          </span>
        )}
      </div>
      <div className="p-4 flex flex-col flex-1">
        <span className="text-xs text-indigo-600 font-medium uppercase tracking-wide">
          {book.category?.name || t('navBooks')}
        </span>
        <h3 className="mt-1 font-semibold text-gray-900 line-clamp-2 group-hover:text-indigo-700 transition-colors">
          {book.title}
        </h3>
        {book.author && <p className="mt-1 text-sm text-gray-500">{book.author}</p>}
        <div className="mt-auto pt-3 flex items-center justify-between">
          {free ? (
            <span className="text-sm font-semibold text-emerald-600">{t('free')}</span>
          ) : (
            <span className="text-sm font-bold text-gray-900">{formatPrice(book.price)}</span>
          )}
          <span className="text-xs text-gray-400">{book.pages || 0} {t('pages')}</span>
        </div>
      </div>
    </Link>
  )
}
