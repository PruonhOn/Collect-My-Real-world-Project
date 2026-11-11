import { useMutation, useQuery } from '@tanstack/react-query'
import { useParams, Link, useNavigate } from 'react-router-dom'
import { getBook, purchaseBook, coverUrl, avatarUrl } from '../../api'
import BookGrid from '../../components/BookGrid'
import { useAuth } from '../../context/AuthContext'
import { useLanguage } from '../../context/LanguageContext'
import { useState } from 'react'

export default function BookDetailPage() {
  const { id } = useParams()
  const { t } = useLanguage()
  const { isAuthenticated } = useAuth()
  const navigate = useNavigate()
  const [purchasing, setPurchasing] = useState(false)
  const [purchaseResult, setPurchaseResult] = useState(null)

  const { data, isLoading } = useQuery({
    queryKey: ['book', id],
    queryFn: () => getBook(id),
    enabled: !!id,
  })

  const res = data?.data
  const book = res?.book
  const relatedBooks = res?.related || []
  const ratings = res?.ratings
  const reviews = res?.reviews || []
  const canRead = !!book && (book.is_free || res?.access?.can_read === true)

  const purchaseMutation = useMutation({
    mutationFn: () => purchaseBook(book.id, { method: 'mock', mock_approve: true }),
    onSuccess: (result) => {
      setPurchasing(false)
      setPurchaseResult(result.data)
    },
    onError: (error) => {
      setPurchasing(false)
      alert(error.response?.data?.message || 'Purchase failed')
    },
  })

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
    if (!isAuthenticated && !book.is_free) {
      navigate('/login')
      return
    }
    navigate(`/reader/${book.id}`)
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
        {/* Cover */}
        <div className="md:col-span-1">
          <div className="sticky top-24">
            <div className="relative aspect-[3/4] rounded-2xl overflow-hidden shadow-lg bg-gradient-to-br from-indigo-500 to-purple-600">
              {coverUrl(book) ? (
                <img src={coverUrl(book)} alt={book.title} className="w-full h-full object-cover" />
              ) : (
                <div className="w-full h-full flex items-center justify-center p-6">
                  <div className="text-white text-center font-serif text-xl font-bold leading-snug">
                    {book.title}
                    {book.author && <div className="mt-3 text-sm opacity-80">{book.author}</div>}
                  </div>
                </div>
              )}
              <div className="absolute top-3 right-3 flex items-center gap-2">
                {book.is_vip_only && (
                  <span className="text-xs font-bold px-3 py-1.5 rounded-lg bg-amber-400 text-amber-900">
                    VIP
                  </span>
                )}
                <span
                  className={`text-xs font-bold px-3 py-1.5 rounded-lg ${
                    book.is_free ? 'bg-emerald-500 text-white' : 'bg-indigo-600 text-white'
                  }`}
                >
                  {book.is_free ? t('free') : `$${book.price}`}
                </span>
              </div>
            </div>

            <div className="mt-4 space-y-2">
              {canRead ? (
                <button
                  onClick={handleRead}
                  className="w-full px-6 py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700"
                >
                  {t('readNow')}
                </button>
              ) : (
                <button
                  onClick={handleBuy}
                  disabled={purchasing}
                  className="w-full px-6 py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 disabled:opacity-60"
                >
                  {purchasing ? t('loading') : t('buyNow')}
                </button>
              )}
            </div>
          </div>
        </div>

        {/* Details */}
        <div className="md:col-span-2">
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
                  {book.category.name}
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

          {book.view_count > 0 && (
            <div className="mt-4 text-sm text-gray-400">
              {book.view_count.toLocaleString()} {t('views')}
            </div>
          )}

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
      {purchaseResult && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-2xl p-8 max-w-md w-full">
            <h2 className="text-xl font-bold text-gray-900 mb-4">
              {purchaseResult.activated ? t('paymentSuccess') : t('completePayment')}
            </h2>
            {purchaseResult.bakong?.qr ? (
              <>
                <img src={purchaseResult.bakong.qr} alt="Bakong QR" className="mx-auto w-64 h-64 rounded-xl" />
                <p className="mt-4 text-sm text-gray-500 text-center">
                  {purchaseResult.bakong.amount} {purchaseResult.bakong.currency} -{' '}
                  {purchaseResult.bakong.merchant_name}
                </p>
              </>
            ) : (
              <div className="bg-indigo-50 rounded-xl p-4 text-indigo-700 text-sm">
                {purchaseResult.message || t('demoNotice')}
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
                  className="flex-1 px-4 py-2.5 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700"
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