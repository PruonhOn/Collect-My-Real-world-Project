import { Link } from 'react-router-dom'
import WishlistButton from './WishlistButton'
import { useI18n } from '../../i18n'

function formatPrice(price, locale) {
  return new Intl.NumberFormat(locale === 'km' ? 'en-US' : locale, {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: price % 1 === 0 ? 0 : 2,
  }).format(price)
}

export default function BookCard({ book }) {
  const { t, locale } = useI18n()
  const isFree = book.type === 'FREE'
  const cover = book.cover_image || '/placeholder-book.svg'

  return (
    <Link
      to={`/books/${book.slug}`}
      className="card card-hover group block overflow-hidden !p-0"
    >
      <div className="relative aspect-[3/4] overflow-hidden bg-slate-100">
        <img
          src={cover}
          alt={book.title}
          loading="lazy"
          className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
        />
        <span
          className={`absolute top-3 left-3 badge shadow-sm ${
            isFree ? 'bg-emerald-600 text-white' : 'bg-indigo-600 text-white'
          }`}
        >
          {isFree ? t('book.free') : t('book.paid')}
        </span>
        <div className="absolute right-3 top-3">
          <WishlistButton bookId={book.id} active={book.is_wishlisted} />
        </div>
        <div className="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/25 via-transparent to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100" />
      </div>
      <div className="p-4">
        <h3 className="line-clamp-2 text-sm font-semibold text-slate-900 group-hover:text-indigo-700">
          {book.title}
        </h3>
        <p className="mt-1 truncate text-xs text-slate-500">
          {book.authors?.map((a) => a.name).join(', ')}
        </p>
        <div className="mt-3 flex items-end justify-between gap-2">
          <div>
            <p className="text-sm font-bold text-slate-900">
              {isFree ? t('book.free') : formatPrice(book.price, locale)}
            </p>
            {book.avg_rating != null && (
              <p className="mt-0.5 flex items-center gap-1 text-xs text-slate-500">
                <span className="text-amber-400">★</span>
                <span className="font-semibold text-slate-700">{book.avg_rating.toFixed(1)}</span>
                <span>({book.reviews_count ?? 0})</span>
              </p>
            )}
          </div>
          <span className="shrink-0 text-xs text-slate-400">
            {book.reader_count?.toLocaleString()} {t('book.readers')}
          </span>
        </div>
      </div>
    </Link>
  )
}