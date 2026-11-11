import { Link } from 'react-router-dom'
import { useLanguage } from '../context/LanguageContext'
import { coverUrl } from '../api'

const PLACEHOLDER_COLORS = [
  'from-indigo-500 to-purple-600',
  'from-blue-500 to-cyan-600',
  'from-emerald-500 to-teal-600',
  'from-rose-500 to-pink-600',
  'from-amber-500 to-orange-600',
  'from-sky-500 to-indigo-600',
]

export default function BookCard({ book }) {
  const { t } = useLanguage()

  const colorIndex = book.id % PLACEHOLDER_COLORS.length
  const gradient = PLACEHOLDER_COLORS[colorIndex]

  return (
    <Link
      to={`/books/${book.id}`}
      className="group bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-all hover:-translate-y-0.5"
    >
      <div className="relative aspect-[3/4] overflow-hidden bg-gradient-to-br ${gradient}">
        {coverUrl(book) ? (
          <img
            src={coverUrl(book)}
            alt={book.title}
            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
            loading="lazy"
          />
        ) : (
          <div className={`w-full h-full bg-gradient-to-br ${gradient} flex items-center justify-center p-4`}>
            <div className="text-white text-center">
              <div className="text-xl font-bold leading-snug font-serif">{book.title}</div>
              {book.author && <div className="mt-2 text-xs opacity-80">{book.author}</div>}
            </div>
          </div>
        )}

        <div className="absolute top-2 right-2">
          <span
            className={`text-xs font-bold px-2 py-1 rounded-lg ${
              book.is_free ? 'bg-emerald-500 text-white' : 'bg-indigo-600 text-white'
            }`}
          >
            {book.is_free ? t('free') : `$${book.price}`}
          </span>
        </div>
      </div>

      <div className="p-4">
        <h3 className="font-semibold text-gray-900 text-sm leading-snug line-clamp-2 group-hover:text-indigo-600">
          {book.title}
        </h3>
        {book.author && <p className="mt-1 text-xs text-gray-500">{book.author}</p>}
        <div className="mt-2 flex items-center justify-between text-xs text-gray-500">
          <span className="flex items-center gap-1">
            {book.rating > 0 && (
              <>
                <svg className="w-3.5 h-3.5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
                {book.rating}
              </>
            )}
            {book.pages > 0 && <span>{book.pages} {t('pages')}</span>}
          </span>
        </div>
      </div>
    </Link>
  )
}