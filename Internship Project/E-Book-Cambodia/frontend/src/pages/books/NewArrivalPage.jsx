import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { useLanguage } from '../../context/LanguageContext'
import VipBookBadge from '../../components/membership/VipBookBadge'
import { getBooks, coverUrl } from '../../api'

const PLACEHOLDER_COLORS = [
  'from-indigo-500 to-purple-600',
  'from-blue-500 to-cyan-600',
  'from-emerald-500 to-teal-600',
  'from-rose-500 to-pink-600',
  'from-amber-500 to-orange-600',
  'from-sky-500 to-indigo-600',
]

function compactNumber(value) {
  const num = Number(value || 0)
  if (num >= 1000000) return `${(num / 1000000).toFixed(1)}M`
  if (num >= 1000) return `${(num / 1000).toFixed(1)}k`
  return String(num)
}

function gradientFor(book) {
  return PLACEHOLDER_COLORS[Number(book.id) % PLACEHOLDER_COLORS.length]
}

function formatDate(value, locale) {
  if (!value) return null
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return null
  const tag = {
    km: 'km-KH',
    en: 'en-US',
    zh: 'zh-CN',
    fr: 'fr-FR',
  }
  return date.toLocaleDateString(tag[locale] || 'en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

function NewArrivalCard({ book }) {
  const { t, locale } = useLanguage()
  const gradient = gradientFor(book)
  const rating = Number(book.rating || book.reviews_avg_rating || 0)
  const views = Number(book.view_count || 0)
  const published = book.published_at || book.publication_date || book.created_at
  const publishedLabel = formatDate(published, locale)
  const category = book.category?.display_name || book.category?.name || book.subject || ''

  return (
    <Link
      to={`/books/${book.id}`}
      className="group flex flex-col bg-white rounded-2xl overflow-hidden border border-gray-100 shadow-sm transition-all duration-300 hover:shadow-xl hover:-translate-y-1 hover:border-indigo-100"
    >
      {/* Cover */}
      <div className={`relative aspect-[3/4] overflow-hidden bg-gradient-to-br ${gradient}`}>
        {coverUrl(book) ? (
          <img
            src={coverUrl(book)}
            alt={book.title}
            loading="lazy"
            className="w-full h-full object-contain bg-white p-1.5 transition-transform duration-500 group-hover:scale-105"
          />
        ) : (
          <div className={`w-full h-full bg-gradient-to-br ${gradient} flex items-center justify-center p-4`}>
            <div className="text-white text-center">
              <div className="text-sm font-bold leading-snug font-serif line-clamp-5">{book.title}</div>
              {book.author && <div className="mt-2 text-[10px] opacity-80 line-clamp-1">{book.author}</div>}
            </div>
          </div>
        )}

        {/* NEW badge */}
        {book.is_coming_soon ? (
          <span className="absolute top-2 left-2 inline-flex items-center gap-1 bg-violet-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-md shadow-sm">
            {t('comingSoon')}
          </span>
        ) : (
          <span className="absolute top-2 left-2 inline-flex items-center gap-1 bg-rose-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-md shadow-sm">
            <svg className="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
              <path fillRule="evenodd" d="M5 2a2 2 0 00-2 2v14l3.5-2 3.5 2 3.5-2 3.5 2V4a2 2 0 00-2-2H5zm4.707 3.707a1 1 0 00-1.414 0L5 9l-.293.293a1 1 0 000 1.414l.293.293 3.293 3.293a1 1 0 001.414 0L12.914 10l3.086-3.086a1 1 0 000-1.414L16 5.293 12.707 2H10.8zm-3 7.707L6 8.707a1 1 0 010-1.414l.707-.707a1 1 0 011.414 0l.586.586 3.086 3.086a1 1 0 00-1.414 1.414l-3.672-3.672z" clipRule="evenodd" />
            </svg>
            {t('brandNew')}
          </span>
        )}

        {/* Price / free / VIP */}
        <span className="absolute top-2 right-2 inline-flex items-center gap-1 text-[10px] font-bold rounded-md shadow-sm">
          {book.is_vip_only ? (
            <VipBookBadge />
          ) : book.is_free ? (
            <span className="inline-flex items-center gap-1 bg-emerald-500 text-white px-2 py-0.5 rounded-md">🆓 {t('free')}</span>
          ) : (
            <span className="inline-flex items-center gap-1 bg-white/95 text-indigo-700 px-2 py-0.5 rounded-md">
              ${Number(book.price || 0).toFixed(2)}
            </span>
          )}
        </span>
      </div>

      {/* Body */}
      <div className="p-3 sm:p-4 flex flex-col flex-1">
        {category && (
          <p className="text-[10px] font-semibold text-indigo-500 uppercase tracking-wide truncate">{category}</p>
        )}
        <h3 className="mt-1 font-semibold text-gray-900 text-sm leading-snug line-clamp-2 group-hover:text-indigo-600 transition-colors">
          {book.title}
        </h3>
        {book.author && <p className="mt-1 text-[11px] text-gray-500 truncate">{book.author}</p>}

        {/* Rating + views */}
        <div className="mt-2.5 flex items-center gap-3 text-[11px] text-gray-500">
          <span className="flex items-center gap-1">
            <svg className="w-3.5 h-3.5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
            </svg>
            {rating > 0 ? rating.toFixed(1) : <span className="text-gray-400">{t('noRating')}</span>}
          </span>
          <span
            className="flex items-center gap-1"
            title={`${views.toLocaleString()} ${t('views')}`}
            aria-label={`${views.toLocaleString()} ${t('views')}`}
          >
            <svg className="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            {compactNumber(views)}
          </span>
        </div>

        <div className="mt-auto pt-3">
          {publishedLabel && (
            <p className="flex items-center gap-1 text-[11px] text-gray-400 mb-2">
              <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              {t('published')} {publishedLabel}
            </p>
          )}
          <span
            className={`inline-flex items-center justify-center w-full gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-200 ${
              book.is_coming_soon
                ? 'bg-gray-100 text-gray-500'
                : book.is_free
                  ? 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm shadow-indigo-600/20'
                  : 'bg-indigo-50 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white'
            }`}
          >
            {book.is_coming_soon ? (
              <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            ) : book.is_free ? (
              <svg className="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4zm4 10.93A7.001 7.001 0 0017 8a1 1 0 10-2 0A5 5 0 015 8a1 1 0 00-2 0 7.001 7.001 0 006 6.93V17H6a1 1 0 100 2h8a1 1 0 100-2h-3v-2.07z" />
              </svg>
            ) : (
              <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6v6m0-10L10 14" />
              </svg>
            )}
            {book.is_coming_soon ? t('comingSoon') : book.is_free ? t('readNow') : t('viewDetails')}
          </span>
        </div>
      </div>
    </Link>
  )
}

function NewArrivalCardSkeleton() {
  return (
    <div className="animate-pulse">
      <div className="aspect-[3/4] bg-gray-200 rounded-2xl" />
      <div className="h-2.5 bg-gray-200 rounded mt-3 w-1/2" />
      <div className="h-4 bg-gray-200 rounded mt-2 w-3/4" />
      <div className="h-3 bg-gray-200 rounded mt-1.5 w-1/3" />
      <div className="h-8 bg-gray-200 rounded-xl mt-3" />
    </div>
  )
}

export default function NewArrivalPage() {
  const { t, locale } = useLanguage()

  const { data, isLoading } = useQuery({
    queryKey: ['books', 'new-arrivals', locale],
    refetchInterval: 30000,
    queryFn: () => getBooks({ sort: 'latest', per_page: 12 }),
  })
  const books = data?.data?.data || []

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
        <div className="flex items-start gap-3 min-w-0">
          <span className="hidden sm:flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-600 text-white text-2xl shadow-lg shadow-indigo-600/20 shrink-0">
            🚀
          </span>
          <div className="min-w-0">
            <span className="inline-flex items-center gap-1.5 text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-100 rounded-full px-3 py-1">
              <span aria-hidden="true">✨</span>
              {t('brandNew')}
            </span>
            <h1 className="mt-1.5 text-2xl sm:text-3xl font-bold text-gray-900">{t('newArrivalTitle')}</h1>
            <p className="mt-2 text-sm sm:text-base text-gray-500">{t('newArrivalSub')}</p>
          </div>
        </div>
        <Link
          to="/books"
          className="inline-flex items-center justify-center gap-1.5 shrink-0 px-4 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold shadow-sm shadow-indigo-600/20 hover:bg-indigo-700 hover:-translate-y-0.5 transition-all active:scale-95"
        >
          {t('allBooks')} <span aria-hidden="true">→</span>
        </Link>
      </div>

      {/* Grid */}
      {isLoading ? (
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-5">
          {[...Array(8)].map((_, i) => (
            <NewArrivalCardSkeleton key={i} />
          ))}
        </div>
      ) : books.length > 0 ? (
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-5">
          {books.map((book) => (
            <NewArrivalCard key={book.id} book={book} />
          ))}
        </div>
      ) : (
        <div className="rounded-3xl border-2 border-dashed border-gray-200 bg-white/60 py-16 px-6 text-center">
          <div className="mx-auto w-16 h-16 rounded-2xl bg-indigo-50 text-indigo-400 flex items-center justify-center text-3xl">
            📚
          </div>
          <h3 className="mt-4 text-base font-semibold text-gray-700">{t('noBooks')}</h3>
          <p className="mt-1 text-sm text-gray-400">{t('newArrivalEmpty')}</p>
        </div>
      )}
    </div>
  )
}