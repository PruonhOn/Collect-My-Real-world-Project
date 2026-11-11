import { useMutation, useQuery } from '@tanstack/react-query'
import { Link, useNavigate, useParams } from 'react-router-dom'
import api from '../api/client'
import Loading from '../components/common/Loading'
import ErrorState from '../components/common/ErrorState'
import Reviews from '../components/books/Reviews'
import WishlistButton from '../components/books/WishlistButton'
import { useAuth } from '../context/AuthContext'
import { useI18n } from '../i18n'

function formatPrice(price, locale) {
  return new Intl.NumberFormat(locale === 'km' ? 'en-US' : locale, {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: price % 1 === 0 ? 0 : 2,
  }).format(price)
}

export default function BookDetail() {
  const { slug } = useParams()
  const { t, locale } = useI18n()
  const { isAuthenticated } = useAuth()
  const navigate = useNavigate()
  const cover = '/placeholder-book.svg'

  const { data, isLoading, isError } = useQuery({
    queryKey: ['book', slug, locale],
    queryFn: async () => {
      const res = await api.get(`/books/${slug}`)
      return res.data.data
    },
  })

  const { data: vipStatus } = useQuery({
    queryKey: ['vip-status'],
    enabled: isAuthenticated,
    queryFn: async () => {
      const res = await api.get('/vip/status')
      return res.data.data
    },
  })

  const buy = useMutation({
    mutationFn: async (bookId) => {
      const res = await api.post('/orders', { book_ids: [bookId] })
      return res.data.data
    },
    onSuccess: (order) => {
      navigate(`/checkout/${order.order_number}`)
    },
  })

  if (isLoading) return <Loading full />
  if (isError) return <ErrorState />

  const book = data
  const isFree = book.type === 'FREE'
  const isVip = Boolean(vipStatus?.is_vip)

  let actionLabel = null
  let actionHref = null
  let actionOnClick = null
  if (isFree) {
    actionLabel = t('book.readForFree')
    actionHref = `/read/${book.slug}`
  } else if (isVip) {
    actionLabel = t('book.goToReader')
    actionHref = `/read/${book.slug}`
  } else if (isAuthenticated) {
    actionLabel = t('book.buyFor', { price: formatPrice(book.price, locale) })
    actionOnClick = () => buy.mutate(book.id)
  } else {
    actionLabel = t('common.buy')
    actionHref = '/login'
  }

  const ActionTag = actionOnClick ? 'button' : Link

  return (
    <div className="page max-w-5xl">
      <Link
        to="/books"
        className="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-indigo-600"
      >
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
          <path d="M19 12H5M12 19l-7-7 7-7" />
        </svg>
        {t('book.backToList')}
      </Link>

      <div className="mt-6 grid gap-10 md:grid-cols-[300px_1fr]">
        <div>
          <div className="overflow-hidden rounded-3xl border border-slate-200 bg-slate-100 shadow-xl shadow-slate-900/10">
            <img src={book.cover_image || cover} alt={book.title} className="aspect-[3/4] w-full object-cover" />
          </div>

          <div className="mt-5">
            <ActionTag
              type={actionOnClick ? 'button' : undefined}
              {...(actionOnClick
                ? { disabled: buy.isPending, onClick: actionOnClick }
                : { to: actionHref })}
              className="btn-primary block w-full px-5 py-3.5 text-center"
            >
              {buy.isPending ? t('common.loading') : actionLabel}
            </ActionTag>

            <div className="mt-3 flex items-center justify-center">
              <WishlistButton bookId={book.id} active={book.is_wishlisted} size={16} />
            </div>

            {!isFree && isAuthenticated && !isVip && (
              <Link
                to="/pricing"
                className="mt-3 block rounded-xl border border-dashed border-slate-300 px-4 py-2.5 text-center text-sm font-semibold text-indigo-600 transition hover:bg-slate-50"
              >
                {t('reader.vipCta')}
              </Link>
            )}
          </div>
        </div>

        <div>
          <div className="flex flex-wrap items-center gap-2">
            <span className={`badge ${isFree ? 'bg-emerald-100 text-emerald-700' : 'bg-indigo-100 text-indigo-700'}`}>
              {isFree ? t('book.free') : t('book.paid')}
            </span>
            {book.categories?.map((c) => (
              <span key={c.id} className="badge bg-slate-100 text-slate-600">{c.name}</span>
            ))}
          </div>

          <h1 className="mt-4 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">{book.title}</h1>
          <p className="mt-2 text-slate-600">
            {t('book.byAuthor')}{' '}
            {book.authors?.map((a, i) => (
              <span key={a.id}>
                {i > 0 && ', '}
                <Link to={`/authors/${a.slug}`} className="font-semibold text-indigo-600 transition hover:text-indigo-700">
                  {a.name}
                </Link>
              </span>
            ))}
          </p>

          {book.avg_rating != null && (
            <div className="mt-3 inline-flex items-center gap-2 rounded-full bg-white py-1.5 pr-4 pl-2.5 ring-1 ring-slate-200">
              <span className="text-amber-400">★</span>
              <span className="text-sm font-bold text-slate-900">{book.avg_rating.toFixed(1)}</span>
              <span className="text-sm text-slate-500">
                ({book.reviews_count ?? 0} {t('review.count')})
              </span>
            </div>
          )}

          <div className="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3">
            <div className="card px-4 py-3">
              <p className="text-xs font-medium tracking-wide text-slate-400 uppercase">{t('book.category')}</p>
              <p className="mt-1 truncate text-sm font-semibold text-slate-800">
                {book.categories?.length ? book.categories.map((c) => c.name).join(', ') : '—'}
              </p>
            </div>
            <div className="card px-4 py-3">
              <p className="text-xs font-medium tracking-wide text-slate-400 uppercase">{t('book.language')}</p>
              <p className="mt-1 text-sm font-semibold text-slate-800">{book.language?.toUpperCase()}</p>
            </div>
            <div className="card px-4 py-3 col-span-2 sm:col-span-1">
              <p className="text-xs font-medium tracking-wide text-slate-400 uppercase">{t('book.readers')}</p>
              <p className="mt-1 text-sm font-semibold text-slate-800">
                {book.reader_count > 0 ? book.reader_count.toLocaleString() : '0'}
              </p>
            </div>
          </div>

          <div className="card mt-6 p-6">
            <h2 className="text-lg font-bold text-slate-900">{t('book.description')}</h2>
            <p className="mt-3 leading-relaxed text-slate-600">{book.description}</p>
          </div>

          {book.available_locales?.length > 1 && (
            <div className="mt-6">
              <h3 className="text-sm font-semibold text-slate-900">{t('book.availableIn')}</h3>
              <div className="mt-2 flex flex-wrap gap-2">
                {book.available_locales.map((l) => (
                  <span
                    key={l}
                    className={`rounded-xl px-3.5 py-1.5 text-xs font-bold ${
                      l === book.locale
                        ? 'bg-indigo-600 text-white'
                        : 'bg-slate-100 text-slate-600'
                    }`}
                  >
                    {l.toUpperCase()}
                  </span>
                ))}
              </div>
            </div>
          )}
        </div>
      </div>

      <Reviews bookId={book.id} bookType={book.type} canAccess={isFree || isVip || Boolean(book.has_access)} />
    </div>
  )
}