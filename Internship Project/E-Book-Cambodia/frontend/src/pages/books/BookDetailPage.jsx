import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useParams, Link, useNavigate } from 'react-router-dom'
import { getBook, recordBookView, purchaseBook, coverUrl, avatarUrl } from '../../api'
import BookGrid from '../../components/BookGrid'
import BakongPaymentModal from '../../components/payment/BakongPaymentModal'
import VipBookBadge from '../../components/membership/VipBookBadge'
import VipUpgradeCard from '../../components/membership/VipUpgradeCard'
import VipExpiryNote from '../../components/membership/VipExpiryNote'
import CrownIcon from '../../components/membership/CrownIcon'
import LockIcon from '../../components/membership/LockIcon'
import { useAuth } from '../../context/AuthContext'
import { useLanguage } from '../../context/LanguageContext'
import { useMembership } from '../../hooks/useMembership'
import { getBookAccess } from '../../utils/membership'
import { useState, useEffect, useRef, useCallback } from 'react'

export default function BookDetailPage() {
  const { id } = useParams()
  const { t, locale } = useLanguage()
  const { isAuthenticated } = useAuth()
  const { isVip } = useMembership()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [purchasing, setPurchasing] = useState(false)
  const [purchaseResult, setPurchaseResult] = useState(null)
  const recordedBookId = useRef(null)

  const { data: bookData, isLoading } = useQuery({
    queryKey: ['book', id, locale],
    refetchInterval: 30000,
    queryFn: () => getBook(id),
    enabled: !!id,
  })

  const res = bookData?.data
  const book = res?.book
  const relatedBooks = res?.related || []
  const ratings = res?.ratings
  const reviews = res?.reviews || []
  const bookId = book?.id
  // Authoritative answer comes from the backend. Guests only get an `access`
  // block once signed in, so fall back to the public free/VIP flags.
  const canRead = !!book && (res?.access
    ? res.access.can_read === true
    : !!book.is_free && !book.is_vip_only)
  const needsVip = !!book?.is_vip_only && !isVip
  const accessHint = getBookAccess(book, { isVip })

  // A paid book may offer a free-page gate (free preview or readtest). When a
  // non-buyer can read the leading pages without paying, they get a trial
  // button that opens the reader; the reader itself stops them at the limit.
  const gateMode = book?.preview_mode && book.preview_mode !== 'none' ? book.preview_mode : null
  const hasPreviewGate = !!gateMode && Number(book?.preview_pages || 0) > 0
  const canTrialRead = !!book && hasPreviewGate && !canRead && !needsVip && !book.is_coming_soon
  const trialLabel = gateMode === 'readtest' ? t('readtestNow') : t('readPreview')

  useEffect(() => {
    if (!bookId || recordedBookId.current === bookId) return
    recordedBookId.current = bookId

    recordBookView(bookId)
      .then((result) => {
        queryClient.setQueriesData(
          { queryKey: ['book', String(bookId)] },
          (current) => {
            if (!current?.data?.book) return current
            return {
              ...current,
              data: {
                ...current.data,
                book: {
                  ...current.data.book,
                  view_count: result.data.view_count,
                },
              },
            }
          },
        )
      })
      .catch(() => {})
  }, [bookId, queryClient])

  const purchaseMutation = useMutation({
    mutationFn: () => purchaseBook(book.id, { method: 'bakong' }),
    onSuccess: (result) => {
      setPurchasing(false)
      setPurchaseResult(result.data)
    },
    onError: (error) => {
      setPurchasing(false)
      alert(error.response?.data?.message || t('purchaseFailed'))
    },
  })

  const handleBakongPaid = useCallback(() => {
    queryClient.invalidateQueries({ queryKey: ['book', id] })
    setPurchaseResult((prev) => (prev ? { ...prev, activated: true, status: 'completed' } : prev))
  }, [queryClient, id])

  if (isLoading) {
    return <div className="max-w-7xl mx-auto px-4 py-10 text-center">{t('loading')}</div>
  }

  if (!book) {
    return (
      <div className="max-w-7xl mx-auto px-4 py-16 text-center">
        <h1 className="text-2xl font-bold text-gray-900 mb-2">{t('pageNotFound')}</h1>
        <Link to="/" className="text-indigo-600 font-medium">
          {t('backToHome')}
        </Link>
      </div>
    )
  }

  const handleBuy = () => {
    if (!isAuthenticated) {
      navigate('/login')
      return
    }
    setPurchasing(true)
    purchaseMutation.mutate()
  }

  const handleRead = () => {
    // A trial reader may enter without an account; the reader gates them at
    // the free-page limit. Anything else that needs full access still has to
    // sign in first.
    if (!isAuthenticated && !canTrialRead && (needsVip || !book.is_free)) {
      navigate('/login')
      return
    }
    navigate(`/reader/${book.id}`)
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      {/* Single full-width stack by default; the side-by-side cover/details
          split only earns its place once there is room for it at lg. */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Cover */}
        <div className="w-full lg:col-span-1">
            {/* Sticking only at lg: a full-width phone cover stuck to the top
                would sit on top of the title and CTA while scrolling. */}
            <div className="lg:sticky lg:top-[7rem]">
            <div className="relative aspect-[3/4] rounded-2xl overflow-hidden shadow-lg bg-gradient-to-br from-indigo-500 to-purple-600">
              {coverUrl(book) ? (
                <img src={coverUrl(book)} alt={book.title} className="block w-full h-full object-contain bg-white p-1.5" />
              ) : (
                <div className="aspect-[3/4] w-full flex items-center justify-center p-6">
                  <div className="text-white text-center font-serif text-xl font-bold leading-snug">
                    {book.title}
                    {book.author && <div className="mt-3 text-sm opacity-80">{book.author}</div>}
                  </div>
                </div>
              )}
              <div className="absolute top-3 right-3 flex items-center gap-2">
                {book.is_coming_soon && (
                  <span className="text-xs font-bold px-3 py-1.5 rounded-lg bg-violet-600 text-white">
                    {t('comingSoon')}
                  </span>
                )}
                {book.is_vip_only && <VipBookBadge size="md" />}
                <span
                  className={`inline-flex items-center gap-1 text-xs font-bold px-3 py-1.5 rounded-lg ${
                    book.is_free ? 'bg-emerald-500 text-white' : 'bg-indigo-600 text-white'
                  }`}
                >
                  {!book.is_free && accessHint.state === 'premium' && !canRead && (
                    <LockIcon className="w-3.5 h-3.5" />
                  )}
                  {book.is_free ? t('free') : `$${book.price}`}
                </span>
              </div>
            </div>

            {/* Active VIP members get a quiet navy-and-gold status strip so the
                premium tier is visible without a single popup. */}
            {isVip && (
              <div className="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 rounded-xl border border-amber-300/40 bg-gradient-to-r from-indigo-950 via-indigo-900 to-indigo-950 px-4 py-3">
                <span className="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-amber-400 text-indigo-950 shrink-0">
                  <CrownIcon className="w-3.5 h-3.5" />
                </span>
                <span className="text-xs font-bold text-amber-200 uppercase tracking-wide">
                  {t('vipMember')}
                </span>
                <VipExpiryNote className="text-xs text-indigo-200/80" />
              </div>
            )}

            <div className="mt-4 space-y-2">
              {book.is_coming_soon ? (
                <button
                  disabled
                  className="w-full px-6 py-3 bg-gray-200 text-gray-500 font-semibold rounded-xl cursor-not-allowed"
                >
                  {t('comingSoon')}
                </button>
              ) : canRead ? (
                <button
                  onClick={handleRead}
                  className="w-full px-6 py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700"
                >
                  {t('readNow')}
                </button>
              ) : needsVip ? (
                <>
                  <button
                    onClick={() => (isAuthenticated ? navigate('/vip') : navigate('/login'))}
                    className="w-full px-6 py-3 bg-amber-400 text-amber-950 font-semibold rounded-xl hover:bg-amber-500"
                  >
                    {t('becomeVip')}
                  </button>
                  <p className="text-xs text-gray-500 text-center leading-relaxed">
                    {t('vipOnlyNotice')}
                  </p>
                </>
              ) : (
                <>
                  {canTrialRead && (
                    <button
                      onClick={handleRead}
                      className="w-full px-6 py-3 bg-emerald-500 text-white font-semibold rounded-xl hover:bg-emerald-600"
                    >
                      {trialLabel}
                    </button>
                  )}
                  <button
                    onClick={handleBuy}
                    disabled={purchasing}
                    className="w-full px-6 py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 disabled:opacity-60"
                  >
                    {purchasing ? t('loading') : t('buyNow')}
                  </button>
                </>
              )}
            </div>
          </div>
        </div>

        {/* Details */}
        <div className="w-full lg:col-span-2">
          <nav className="text-sm text-gray-500 mb-4">
            <Link to="/" className="hover:text-indigo-600">
              {t('home')}
            </Link>
            <span className="mx-2">/</span>
            <Link to="/books" className="hover:text-indigo-600">
              {t('books')}
            </Link>
            {book.category && (
              <>
                <span className="mx-2">/</span>
                <Link to={`/categories/${book.category.id}`} className="hover:text-indigo-600">
                  {book.category.display_name || book.category.name}
                </Link>
              </>
            )}
          </nav>

          <h1 className="text-3xl md:text-4xl font-bold text-gray-900 mb-2">{book.title}</h1>

          {book.author && <div className="text-indigo-600 font-medium">{book.author}</div>}

          <div className="flex items-center gap-4 mt-3 text-sm text-gray-600">
            {ratings && ratings.count > 0 && (
              <span className="flex items-center gap-1">
                <svg className="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
                {ratings.avg}
                <span className="text-gray-400">({ratings.count})</span>
              </span>
            )}
            {book.pages > 0 && (
              <span>
                {book.pages} {t('pages')}
              </span>
            )}
            {book.file_size > 0 && <span>{(book.file_size / (1024 * 1024)).toFixed(1)} MB</span>}
          </div>

          {book.description && (
            <div className="mt-6">
              <h2 className="text-lg font-semibold text-gray-900 mb-2">{t('description')}</h2>
              <p className="text-gray-600 leading-relaxed whitespace-pre-line">{book.description}</p>
            </div>
          )}

          {/* One quiet upgrade path for a locked book, placed below the
              description so it never competes with the Become VIP button. */}
          {needsVip && <VipUpgradeCard compact className="mt-6" />}

          {(book.publisher || book.subject || book.education_level || book.grade || book.publication_date) && (
            <div className="mt-6 grid sm:grid-cols-2 gap-4">
              {book.publisher && (
                <div>
                  <div className="text-xs font-semibold text-gray-400 uppercase">{t('publisher')}</div>
                  <div className="text-gray-800">{book.publisher}</div>
                </div>
              )}
              {book.subject && (
                <div>
                  <div className="text-xs font-semibold text-gray-400 uppercase">{t('subject')}</div>
                  <div className="text-gray-800">{book.subject}</div>
                </div>
              )}
              {book.education_level && (
                <div>
                  <div className="text-xs font-semibold text-gray-400 uppercase">{t('educationLevel')}</div>
                  <div className="text-gray-800">{book.education_level}</div>
                </div>
              )}
              {book.grade && (
                <div>
                  <div className="text-xs font-semibold text-gray-400 uppercase">{t('grade')}</div>
                  <div className="text-gray-800">{book.grade}</div>
                </div>
              )}
              {book.publication_date && (
                <div>
                  <div className="text-xs font-semibold text-gray-400 uppercase">{t('publicationDate')}</div>
                  <div className="text-gray-800">{book.publication_date}</div>
                </div>
              )}
            </div>
          )}

          <div className="mt-4 text-sm text-gray-400">
            {Number(book.view_count || 0).toLocaleString()} {t('views')}
          </div>

          {/* Reviews */}
          {reviews.length > 0 && (
            <div className="mt-8">
              <h2 className="text-lg font-semibold text-gray-900 mb-4">
                {t('rating')} ({reviews.length})
              </h2>
              <div className="space-y-4">
                {reviews.map((review) => (
                  <div key={review.id} className="bg-white rounded-xl p-4 shadow-sm">
                    <div className="flex items-center justify-between mb-2">
                      <div className="flex items-center gap-2">
                        {avatarUrl(review.user) ? (
                          <img src={avatarUrl(review.user)} alt="" className="w-8 h-8 rounded-full" />
                        ) : (
                          <div className="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm font-bold">
                            {review.user?.name?.charAt(0)?.toUpperCase()}
                          </div>
                        )}
                        <span className="font-medium text-gray-800">{review.user?.name}</span>
                      </div>
                      <span className="text-amber-500">{"★".repeat(review.rating)}</span>
                    </div>
                    {review.comment && <p className="text-gray-600 text-sm">{review.comment}</p>}
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      </div>

      {relatedBooks.length > 0 && (
        <div className="mt-12">
          <h2 className="text-2xl font-bold text-gray-900 mb-6">{t('relatedBooks')}</h2>
          <BookGrid books={relatedBooks} />
        </div>
      )}

      {/* Purchase Result Modal */}
      {purchaseResult?.bakong?.qr ? (
        <BakongPaymentModal
          result={purchaseResult}
          onPaid={handleBakongPaid}
          onClosed={() => setPurchaseResult(null)}
          footer={
            <button
              onClick={() => navigate(`/reader/${book.id}`)}
              className="flex-1 px-4 py-2.5 bg-emerald-500 text-white rounded-xl hover:bg-emerald-600 font-medium"
            >
              {t('readNow')}
            </button>
          }
        />
      ) : purchaseResult && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-2xl p-8 max-w-md w-full">
            <h2 className="text-xl font-bold text-gray-900 mb-4">
              {purchaseResult.activated ? t('paymentSuccess') : t('completePayment')}
            </h2>
            <div className="bg-indigo-50 rounded-xl p-4 text-indigo-700 text-sm">
              {purchaseResult.message || t('demoNotice')}
            </div>

            {purchaseResult.activated ? (
              <button
                type="button"
                className="mt-5 w-full px-4 py-3 bg-emerald-500 text-white font-semibold rounded-xl flex items-center justify-center gap-2 cursor-default"
                aria-label={t('paymentSuccess')}
              >
                <svg className="w-5 h-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                  <path
                    fillRule="evenodd"
                    d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z"
                    clipRule="evenodd"
                  />
                </svg>
                {t('paymentSuccess')}
              </button>
            ) : (
              <div className="mt-5 flex items-center justify-center gap-3 text-gray-600">
                <svg
                  className="w-5 h-5 text-indigo-500 animate-spin"
                  viewBox="0 0 24 24"
                  fill="none"
                  aria-hidden="true"
                >
                  <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                  <path
                    className="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                  />
                </svg>
                <span className="text-sm font-medium">{t('waitingPayment')}</span>
              </div>
            )}

            <div className="mt-6 flex gap-3">
              <button
                onClick={() => setPurchaseResult(null)}
                className="flex-1 px-4 py-2.5 border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50"
              >
                {t('cancel')}
              </button>
              {purchaseResult.activated && (
                <button
                  onClick={() => navigate(`/reader/${book.id}`)}
                  className="flex-1 px-4 py-2.5 bg-emerald-500 text-white rounded-xl hover:bg-emerald-600 font-medium"
                >
                  {t('readNow')}
                </button>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  )
}