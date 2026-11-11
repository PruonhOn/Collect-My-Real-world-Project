import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { useState } from 'react'
import api from '../../api/client'
import { useAuth } from '../../context/AuthContext'
import { useI18n } from '../../i18n'

const inputClass =
  'w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15 focus:outline-none'

function Stars({ value, onChange, size = 20 }) {
  const [hover, setHover] = useState(null)
  const active = onChange ? (hover ?? value) : value

  return (
    <div className="flex items-center gap-1" role="radiogroup" aria-label="Rating">
      {[1, 2, 3, 4, 5].map((n) => (
        <button
          type="button"
          key={n}
          disabled={!onChange}
          onClick={() => onChange?.(n)}
          onMouseEnter={() => onChange && setHover(n)}
          onMouseLeave={() => onChange && setHover(null)}
          className={onChange ? 'transition hover:scale-110' : 'cursor-default'}
          aria-label={`${n} star`}
        >
          <svg
            width={size}
            height={size}
            viewBox="0 0 24 24"
            fill={n <= active ? 'currentColor' : 'none'}
            stroke="currentColor"
            strokeWidth="1.5"
            className={n <= active ? 'text-amber-400' : 'text-slate-300'}
          >
            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
          </svg>
        </button>
      ))}
    </div>
  )
}

function ReviewForm({ bookId, myReview, onSaved }) {
  const { t } = useI18n()
  const queryClient = useQueryClient()
  const [rating, setRating] = useState(myReview?.rating ?? 0)
  const [comment, setComment] = useState(myReview?.comment ?? '')

  const reset = () => {
    setRating(myReview?.rating ?? 0)
    setComment(myReview?.comment ?? '')
  }

  const save = useMutation({
    mutationFn: () =>
      myReview
        ? api.put(`/reviews/${myReview.id}`, { rating, comment })
        : api.post('/reviews', { book_id: bookId, rating, comment }),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['book-reviews', bookId] })
      reset()
      onSaved?.()
    },
  })

  const remove = useMutation({
    mutationFn: () => api.delete(`/reviews/${myReview.id}`),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['book-reviews', bookId] })
      onSaved?.()
    },
  })

  return (
    <div className="rounded-2xl border border-slate-200/80 bg-slate-50 p-5">
      <h3 className="font-semibold text-slate-900">
        {myReview ? t('review.yourReview') : t('review.submit')}
      </h3>
      <div className="mt-3">
        <Stars value={rating} onChange={setRating} />
      </div>
      <textarea
        value={comment}
        onChange={(e) => setComment(e.target.value)}
        rows="3"
        placeholder={t('review.commentPlaceholder')}
        className={`${inputClass} mt-3`}
      />
      <div className="mt-3 flex gap-2">
        <button
          type="button"
          disabled={rating === 0 || save.isPending}
          onClick={() => save.mutate()}
          className="btn-primary shrink-0"
        >
          {save.isPending ? t('common.loading') : myReview ? t('review.update') : t('review.submit')}
        </button>
        {myReview && (
          <button
            type="button"
            onClick={() => {
              if (window.confirm(t('admin.confirmDelete'))) remove.mutate()
            }}
            className="btn-danger"
          >
            {t('review.delete')}
          </button>
        )}
      </div>
      {save.isSuccess && (
        <p className="mt-2 text-sm font-medium text-emerald-600">
          {myReview ? t('review.updated') : t('review.success')}
        </p>
      )}
      {save.isError && <p className="mt-2 text-sm font-medium text-red-600">{t('common.error')}</p>}
    </div>
  )
}

export default function Reviews({ bookId, bookType, canAccess }) {
  const { t, locale } = useI18n()
  const { isAuthenticated } = useAuth()
  const [page, setPage] = useState(1)

  const { data, isLoading, isError } = useQuery({
    queryKey: ['book-reviews', bookId, page, locale],
    queryFn: async () => {
      const res = await api.get(`/books/${bookId}/reviews`, { params: { page, per_page: 5 } })
      return res.data.data
    },
  })

  const canWrite = isAuthenticated && canAccess
  const isFreeBook = bookType === 'FREE'

  return (
    <div className="mt-12">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <h2 className="text-xl font-bold text-slate-900">{t('review.title')}</h2>
          {data?.book?.avg_rating != null && (
            <div className="mt-2 flex items-center gap-2 rounded-full bg-white px-3 py-1.5 ring-1 ring-slate-200">
              <Stars value={Math.round(data.book.avg_rating)} size={16} />
              <span className="text-sm font-bold text-slate-900">{data.book.avg_rating.toFixed(1)}</span>
              <span className="text-sm text-slate-500">
                ({data.book.reviews_count} {t('review.count')})
              </span>
            </div>
          )}
        </div>

        {isAuthenticated && !canWrite && (
          <p className="max-w-xs text-right text-xs text-slate-500">
            {isFreeBook ? t('review.accessRequired') : t('review.accessRequired')}
          </p>
        )}
      </div>

      <div className="mt-6 grid gap-6 lg:grid-cols-[1fr_340px]">
        <div className="space-y-4">
          {isLoading && <p className="text-sm text-slate-500">{t('common.loading')}</p>}
          {isError && <p className="text-sm text-red-600">{t('common.error')}</p>}

          {data?.reviews?.length === 0 && !isLoading && (
            <p className="text-sm text-slate-500">{t('review.noReviews')}</p>
          )}

          {data?.reviews?.map((review) => (
            <div key={review.id} className="card p-5">
              <div className="flex items-center justify-between">
                <span className="flex items-center gap-2.5 font-semibold text-slate-900">
                  <span className="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white">
                    {review.user?.name?.charAt(0).toUpperCase() || '?'}
                  </span>
                  {review.user?.name}
                </span>
                <span className="text-xs text-slate-400">
                  {new Date(review.created_at).toLocaleDateString(locale === 'km' ? 'en-US' : locale)}
                </span>
              </div>
              <div className="mt-3">
                <Stars value={review.rating} size={14} />
              </div>
              {review.comment && <p className="mt-2 text-sm leading-relaxed text-slate-600">{review.comment}</p>}
            </div>
          ))}

          {data?.pagination?.last_page > 1 && (
            <div className="flex items-center justify-center gap-2 pt-2">
              <button
                type="button"
                disabled={page <= 1}
                onClick={() => setPage((p) => p - 1)}
                className="btn-secondary px-3 py-1.5"
              >
                ←
              </button>
              <span className="text-sm font-medium text-slate-600">
                {data.pagination.current_page} / {data.pagination.last_page}
              </span>
              <button
                type="button"
                disabled={page >= data.pagination.last_page}
                onClick={() => setPage((p) => p + 1)}
                className="btn-secondary px-3 py-1.5"
              >
                →
              </button>
            </div>
          )}
        </div>

        <div>
          {!isAuthenticated && (
            <Link
              to="/login"
              className="block rounded-2xl border border-dashed border-slate-300 p-4 text-center text-sm font-semibold text-indigo-600 transition hover:bg-slate-50"
            >
              {t('review.loginCta')}
            </Link>
          )}
          {canWrite && <ReviewForm bookId={bookId} myReview={data?.my_review} />}
        </div>
      </div>
    </div>
  )
}