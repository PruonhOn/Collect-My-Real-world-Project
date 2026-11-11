import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import client from '../api/client'
import { useAuth } from '../context/AuthContext'
import { useToast } from '../context/ToastContext'
import { useLanguage } from '../context/LanguageContext'
import EmptyState from './EmptyState'

function Stars({ value }) {
  return (
    <span className="inline-flex items-center gap-0.5" aria-label={`${value}/5`} data-testid="stars">
      {[1, 2, 3, 4, 5].map((n) => (
        <span key={n} className={n <= Math.round(value) ? 'text-amber-400' : 'text-gray-300'}>
          ★
        </span>
      ))}
    </span>
  )
}

export default function BookReviews({ bookId, avgRating, reviewsCount, canReview }) {
  const { t } = useLanguage()
  const { isAuthenticated, user } = useAuth()
  const { toast } = useToast()

  const [reviews, setReviews] = useState([])
  const [loading, setLoading] = useState(true)
  const [rating, setRating] = useState(0)
  const [comment, setComment] = useState('')
  const [submitting, setSubmitting] = useState(false)

  const load = () => {
    setLoading(true)
    client
      .get(`/books/${bookId}/reviews`)
      .then(({ data }) => {
        const list = Array.isArray(data) ? data : data.reviews?.data ?? []
        setReviews(list)
      })
      .catch(() => setReviews([]))
      .finally(() => setLoading(false))
  }

  useEffect(() => {
    load()
    setRating(0)
    setComment('')
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [bookId])

  const handleSubmit = async (e) => {
    e.preventDefault()
    if (!rating) {
      toast(t('ratingRequired'), 'warning')
      return
    }
    setSubmitting(true)
    try {
      await client.post(`/books/${bookId}/reviews`, { rating, comment })
      toast(t('reviewSaved'), 'success')
      setComment('')
      setRating(0)
      load()
    } catch (err) {
      toast(err.response?.data?.message || t('errSaveReview'), 'error')
    } finally {
      setSubmitting(false)
    }
  }

  const handleDelete = async (reviewId) => {
    try {
      await client.delete(`/reviews/${reviewId}`)
      toast(t('reviewDeleted'), 'success')
      load()
    } catch (err) {
      toast(err.response?.data?.message || t('errSaveReview'), 'error')
    }
  }

  return (
    <div className="mt-12">
      <div className="flex items-center justify-between flex-wrap gap-3">
        <h2 className="text-xl font-bold text-gray-900">{t('reviews')}</h2>
        {reviewsCount > 0 && (
          <div className="flex items-center gap-2 text-sm text-gray-600">
            <Stars value={avgRating || 0} />
            <span className="font-semibold text-gray-900">{(avgRating || 0).toFixed(1)} / 5</span>
            <span>({t('reviewsCountSuffix', { count: reviewsCount })})</span>
          </div>
        )}
      </div>

      <div className="mt-6 space-y-4">
        {isAuthenticated && canReview && (
          <form
            onSubmit={handleSubmit}
            className="bg-white rounded-xl border border-gray-100 p-5 shadow-sm"
          >
            <div className="flex items-center gap-1 mb-3">
              {[1, 2, 3, 4, 5].map((n) => (
                <button
                  key={n}
                  type="button"
                  onClick={() => setRating(n)}
                  aria-label={`${n} star`}
                  className={`text-2xl transition-colors ${
                    n <= rating ? 'text-amber-400' : 'text-gray-300 hover:text-amber-300'
                  }`}
                >
                  ★
                </button>
              ))}
              <span className="ml-2 text-sm text-gray-500">{t('writeReview')}</span>
            </div>
            <textarea
              value={comment}
              onChange={(e) => setComment(e.target.value)}
              rows={3}
              placeholder={t('commentOptional')}
              className="w-full border border-gray-200 rounded-lg p-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
            <button
              type="submit"
              disabled={submitting}
              className="mt-3 px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 disabled:opacity-50"
            >
              {submitting ? '...' : t('submitReview')}
            </button>
          </form>
        )}

        {!isAuthenticated && canReview && (
          <p className="text-sm text-gray-500">
            <Link
              to={`/login?next=${encodeURIComponent(`/books/${bookId}`)}`}
              className="text-indigo-600 hover:underline"
            >
              {t('loginToReview')}
            </Link>
          </p>
        )}

        {!canReview && (
          <p className="text-sm text-gray-500 bg-white border border-gray-100 rounded-xl p-4 shadow-sm">
            {t('accessToReview')}
          </p>
        )}

        {loading ? (
          <p className="text-gray-400 text-sm">{'...'}</p>
        ) : reviews.length === 0 ? (
          <EmptyState title={t('noReviewsYet')} message={t('noReviewsMsg')} />
        ) : (
          <div className="space-y-3">
            {reviews.map((review) => (
              <div
                key={review.id}
                className="bg-white rounded-xl border border-gray-100 p-5 shadow-sm"
              >
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <div className="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-sm">
                      {(review.user?.name || user?.name || 'U').charAt(0)}
                    </div>
                    <div>
                      <p className="text-sm font-semibold text-gray-800">{review.user?.name || t('anonymous')}</p>
                      <Stars value={review.rating} />
                    </div>
                  </div>
                  {(user?.role === 'admin' || review.user_id === user?.id) && (
                    <button
                      onClick={() => handleDelete(review.id)}
                      className="text-xs text-red-500 hover:underline"
                    >
                      {t('deleteReview')}
                    </button>
                  )}
                </div>
                {review.comment && (
                  <p className="mt-3 text-gray-600 text-sm whitespace-pre-line">{review.comment}</p>
                )}
                {review.created_at && (
                  <p className="mt-2 text-xs text-gray-400">
                    {new Date(review.created_at).toLocaleDateString()}
                  </p>
                )}
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}