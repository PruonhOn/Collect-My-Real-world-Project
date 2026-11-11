import { useCallback, useEffect, useState } from 'react'
import { Link, useParams, useNavigate } from 'react-router-dom'
import client from '../api/client'
import { useAuth } from '../context/AuthContext'
import { useToast } from '../context/ToastContext'
import { useLanguage } from '../context/LanguageContext'
import Spinner from '../components/Spinner'
import ErrorState from '../components/ErrorState'
import PaymentModal from '../components/PaymentModal'
import BookReviews from '../components/BookReviews'
import { formatPrice } from '../utils/format'

export default function BookDetail() {
  const { t } = useLanguage()
  const { id } = useParams()
  const [data, setData] = useState(null)
  const [access, setAccess] = useState({ state: 'unknown', source: null })
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [showPayment, setShowPayment] = useState(false)
  const { isAuthenticated } = useAuth()
  const { toast } = useToast()
  const navigate = useNavigate()

  const checkAccess = useCallback(async (bookId) => {
    try {
      const { data } = await client.get(`/library/${bookId}`)
      setAccess({ state: 'granted', source: data.source })
    } catch (err) {
      if (err.response?.status === 403) {
        setAccess({ state: 'none', source: null })
      } else if (err.response?.status === 401) {
        setAccess({ state: 'guest', source: null })
      }
    }
  }, [])

  useEffect(() => {
    setLoading(true)
    client
      .get(`/books/${id}`)
      .then(({ data }) => {
        setData(data)
        if (data.book.is_free || Number(data.book.price) === 0) {
          setAccess({ state: 'free', source: null })
        } else if (isAuthenticated) {
          checkAccess(data.book.id)
        } else {
          setAccess({ state: 'guest', source: null })
        }
      })
      .catch((err) => {
        setError(err.response?.status === 404 ? t('bookNotFound') : t('errLoadBook'))
      })
      .finally(() => setLoading(false))
  }, [id, isAuthenticated, checkAccess, t])

  const handlePurchase = () => {
    if (!isAuthenticated) {
      toast(t('loginToPurchase'), 'info')
      navigate(`/login?next=${encodeURIComponent(`/books/${id}`)}`)
      return
    }
    setShowPayment(true)
  }

  const payForBook = async (method) => {
    try {
      const { data } = await client.post(`/books/${id}/purchase`, {
        method,
        mock_approve: true,
      })
      if (data.checkout_url) {
        toast(t('redirectingGateway'), 'info')
        window.location.href = data.checkout_url
      } else if (data.activated) {
        setShowPayment(false)
        toast(t('paymentCompleted'), 'success')
        setAccess({ state: 'granted', source: 'owned' })
        navigate(`/reader/${id}`)
      } else if (data.status === 'pending') {
        setShowPayment(false)
        toast(t('paymentPendingApproval'), 'info')
      } else {
        toast(data.message || t('errPurchase'), 'error')
      }
    } catch (err) {
      toast(err.response?.data?.message || t('errPurchase'), 'error')
    }
  }

  if (loading) return <Spinner />
  if (error) return <ErrorState message={error} />
  if (!data) return null

  const { book, related } = data
  const isFree = book.is_free || Number(book.price) === 0

  const canRead = ['free', 'granted'].includes(access.state)

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <nav className="text-sm text-gray-500 mb-6">
        <Link to="/" className="hover:underline">{t('navHome')}</Link> /{' '}
        <Link to="/books" className="hover:underline">{t('navBooks')}</Link> /{' '}
        <span className="text-gray-700">{book.title}</span>
      </nav>

      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-0">
          <div className="bg-gradient-to-br from-indigo-100 to-purple-100 p-8 flex items-center justify-center md:border-r border-gray-100">
            {book.cover_image ? (
              <img
                src={`${import.meta.env.VITE_API_URL?.replace('/api/v1', '') || ''}/storage/${book.cover_image}`}
                alt={book.title}
                className="w-56 h-72 object-cover rounded-lg shadow-lg"
              />
            ) : (
              <div className="w-56 h-72 bg-white rounded-lg shadow-lg flex items-center justify-center">
                <span className="text-6xl">📖</span>
              </div>
            )}
          </div>
          <div className="md:col-span-2 p-8">
            <span className="inline-block bg-indigo-50 text-indigo-700 text-xs font-semibold px-3 py-1 rounded-full mb-3">
              {book.category?.name}
            </span>
            <h1 className="text-3xl font-bold text-gray-900">{book.title}</h1>
            {book.author && <p className="mt-1 text-gray-500">{t('by')} {book.author}</p>}

            <div className="flex items-center gap-4 mt-4 text-sm text-gray-500">
              <span>🌐 {book.language === 'km' ? t('khmer') : book.language}</span>
              <span>📄 {book.pages || 0} {t('pages')}</span>
              {isFree ? (
                <span className="bg-emerald-100 text-emerald-700 font-semibold px-3 py-1 rounded-full">
                  {t('free')}
                </span>
              ) : (
                <span className="text-xl font-bold text-gray-900">{formatPrice(book.price)}</span>
              )}
            </div>

            <div className="mt-6">
              <h2 className="text-lg font-semibold text-gray-900 mb-2">{t('description')}</h2>
              <p className="text-gray-600 whitespace-pre-line">{book.description || t('noDescriptionAvail')}</p>
            </div>

            <div className="mt-8 flex flex-col sm:flex-row gap-3">
              {canRead ? (
                <Link
                  to={`/reader/${book.id}`}
                  className="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg text-center hover:bg-indigo-700"
                >
                  {t('readNow')}
                </Link>
              ) : (
                <button
                  onClick={handlePurchase}
                  className="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700"
                >
                  {`${t('buy')} - ${formatPrice(book.price)}`}
                </button>
              )}
              <button
                onClick={() => navigate('/library')}
                className="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50"
              >
                {t('myLibrary')}
              </button>
            </div>

            {!isAuthenticated && !isFree && (
              <p className="mt-4 text-sm text-gray-500">
                {t('guestHint')}
              </p>
            )}
          </div>
        </div>
      </div>

      {related?.length > 0 && (
        <div className="mt-12">
          <h2 className="text-xl font-bold text-gray-900 mb-4">{t('relatedBooks')}</h2>
          <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
            {related.map((rb) => (
              <Link
                key={rb.id}
                to={`/books/${rb.id}`}
                className="bg-white rounded-xl border border-gray-100 p-4 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300"
              >
                <div className="text-4xl mb-2">📖</div>
                <h3 className="text-sm font-semibold text-gray-800 line-clamp-2">{rb.title}</h3>
                <p className="text-xs text-gray-400 mt-1">{rb.author}</p>
              </Link>
            ))}
          </div>
        </div>
      )}

      {showPayment && book && (
        <PaymentModal
          book={book}
          onPay={payForBook}
          onClose={() => setShowPayment(false)}
        />
      )}

      <BookReviews
        bookId={book.id}
        avgRating={data.ratings?.avg || 0}
        reviewsCount={data.ratings?.count || 0}
        canReview={canRead}
      />
    </div>
  )
}
