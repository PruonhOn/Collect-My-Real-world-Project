import { Link } from 'react-router-dom'
import { useLanguage } from '../context/LanguageContext'
import { coverUrl } from '../api'
import { useMembership } from '../hooks/useMembership'
import { getBookAccess } from '../utils/membership'
import VipBookBadge from './membership/VipBookBadge'
import LockIcon from './membership/LockIcon'

const PLACEHOLDER_COLORS = [
  'from-indigo-500 to-purple-600',
  'from-blue-500 to-cyan-600',
  'from-emerald-500 to-teal-600',
  'from-rose-500 to-pink-600',
  'from-amber-500 to-orange-600',
  'from-sky-500 to-indigo-600',
]

const NEW_BOOK_WINDOW_DAYS = 60
const NEW_BOOK_CUTOFF = Date.now() - NEW_BOOK_WINDOW_DAYS * 24 * 60 * 60 * 1000

function compactNumber(value) {
  const number = Number(value || 0)
  if (number >= 1000000) return `${(number / 1000000).toFixed(1)}M`
  if (number >= 1000) return `${(number / 1000).toFixed(1)}k`
  return String(number)
}

export default function BookCard({ book }) {
  const { t } = useLanguage()
  const { isVip } = useMembership()

  const colorIndex = book.id % PLACEHOLDER_COLORS.length
  const gradient = PLACEHOLDER_COLORS[colorIndex]
  const rating = Number(book.rating || book.reviews_avg_rating || 0)

  const isNew =
    !!book.created_at &&
    new Date(book.created_at).getTime() > NEW_BOOK_CUTOFF

  const comingSoon = !!book.is_coming_soon
  const isPurchased = !!book.is_purchased
  // UI hint only — the reader still asks the API before serving a page.
  const access = getBookAccess(book, { isVip })
  const isLocked = !comingSoon && !access.canRead
  // The amber VIP badge already marks VIP-only books, so the lock chip on the
  // price is reserved for ordinary paid books this viewer cannot open.
  const isPremiumLocked = access.state === 'premium'

  const badgeClass = 'inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-md shadow-sm'

  return (
    <Link
      to={`/books/${book.id}`}
      className="group bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-xl border border-gray-100/80 transition-all duration-300 hover:-translate-y-1 flex flex-col"
    >
      <div className={`relative aspect-[3/4] overflow-hidden bg-gradient-to-br ${gradient}`}>
        {coverUrl(book) ? (
          <img
            src={coverUrl(book)}
            alt={book.title}
            className="w-full h-full object-contain bg-white p-1.5 transition-transform duration-500 group-hover:scale-105"
            loading="lazy"
          />
        ) : (
          <div className={`w-full h-full bg-gradient-to-br ${gradient} flex items-center justify-center p-4`}>
            <div className="text-white text-center">
              <div className="text-sm font-bold leading-snug font-serif line-clamp-5">{book.title}</div>
              {book.author && <div className="mt-2 text-[10px] opacity-80 line-clamp-1">{book.author}</div>}
            </div>
          </div>
        )}

        <div className="absolute top-2 left-2">
          {isPurchased ? (
            <span className={`${badgeClass} bg-emerald-600 text-white`}>
              <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="3" d="M5 13l4 4L19 7" />
              </svg>
              {t('purchased')}
            </span>
          ) : isPremiumLocked ? (
            <span className={`${badgeClass} bg-indigo-600 text-white`} title={t('premiumLabel')}>
              <LockIcon className="w-3 h-3" />
              {`$${Number(book.price || 0).toFixed(2)}`}
            </span>
          ) : (
            <span
              className={`${badgeClass} ${
                book.is_free
                  ? 'bg-emerald-500 text-white'
                  : 'bg-white/95 text-indigo-700'
              }`}
            >
              {book.is_free ? t('free') : `$${Number(book.price || 0).toFixed(2)}`}
            </span>
          )}
        </div>

        {(book.is_vip_only || isNew || comingSoon) && (
          <div className="absolute top-2 right-2">
            {comingSoon ? (
              <span className={`${badgeClass} bg-violet-600 text-white`}>{t('comingSoon')}</span>
            ) : book.is_vip_only ? (
              <VipBookBadge />
            ) : (
              <span className={`${badgeClass} bg-rose-500 text-white`}>{t('brandNew')}</span>
            )}
          </div>
        )}

        {isLocked && (
          <div className="absolute inset-0 bg-gray-950/25 group-hover:bg-gray-950/10 transition-colors" aria-hidden="true" />
        )}

        <div className="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/50 to-transparent pt-8 pb-2 px-2 opacity-0 sm:opacity-0 group-hover:opacity-100 transition-opacity duration-300">
          <span
            className={`inline-flex items-center gap-1.5 bg-white/95 text-[11px] font-semibold px-3 py-1.5 rounded-full shadow ${
              isLocked ? 'text-indigo-700' : isPurchased ? 'text-emerald-600' : 'text-indigo-700'
            }`}
          >
            {comingSoon ? (
              <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            ) : isLocked ? (
              <LockIcon className="w-3.5 h-3.5" />
            ) : isPurchased ? (
              <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
              </svg>
            ) : (
              <svg className="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                <path d="M10 12.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5zM10 3a7 7 0 00-7 7c0 3.86 5 9 7 9s7-5.14 7-9a7 7 0 00-7-7z" />
              </svg>
            )}
            {comingSoon
              ? t('comingSoon')
              : isLocked
                ? t('premiumLabel')
                : isPurchased
                  ? t('readNow')
                  : t('readPreview')}
          </span>
        </div>
      </div>

      <div className="p-3 flex flex-col flex-1">
        <h3 className="font-semibold text-gray-900 text-[13px] leading-snug line-clamp-2 group-hover:text-indigo-600 transition-colors">
          {book.title}
        </h3>
        {book.author && <p className="mt-1 text-[11px] text-gray-500 truncate">{book.author}</p>}
        {(book.category?.display_name || book.category?.name) && (
          <p className="mt-0.5 text-[10px] font-medium text-indigo-500/80 uppercase tracking-wide truncate">
            {book.category.display_name || book.category.name}
          </p>
        )}

        {book.reading_progress?.last_page > 0 && (
          <div className="mt-2">
            <div className="h-1 w-full bg-gray-100 rounded-full overflow-hidden">
              <div
                className="h-full bg-emerald-500 rounded-full"
                style={{
                  width: `${Math.min(Math.max(Number(book.reading_progress.percent) || 0, 0), 100)}%`,
                }}
              />
            </div>
            <div className="mt-1 flex items-center justify-between text-[10px]">
              <span className="text-emerald-600 font-semibold">
                {t('readProgress')} {book.reading_progress.last_page}/
                {book.reading_progress.total_pages || book.pages}
              </span>
              <span className="text-gray-400">{Math.round(Number(book.reading_progress.percent) || 0)}%</span>
            </div>
          </div>
        )}

        <div className="mt-auto pt-2 flex items-center justify-between text-[11px] text-gray-500">
          {rating > 0 ? (
            <span className="flex items-center gap-1">
              <svg className="w-3 h-3 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
              </svg>
              {rating.toFixed(1)}
            </span>
          ) : (
            <span />
          )}
          <span className="flex items-center gap-2">
            {book.pages > 0 && <span>{book.pages} {t('pages')}</span>}
            <span
              className="inline-flex items-center gap-1"
              title={`${Number(book.view_count || 0).toLocaleString()} ${t('views')}`}
              aria-label={`${Number(book.view_count || 0).toLocaleString()} ${t('views')}`}
            >
              <svg className="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
              {compactNumber(book.view_count || 0)}
            </span>
          </span>
        </div>
      </div>
    </Link>
  )
}