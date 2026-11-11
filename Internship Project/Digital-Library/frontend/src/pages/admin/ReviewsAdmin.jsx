import { useEffect, useState } from 'react'
import client from '../../api/client'
import { useLanguage } from '../../context/LanguageContext'
import { useToast } from '../../context/ToastContext'
import Spinner from '../../components/Spinner'
import ErrorState from '../../components/ErrorState'
import EmptyState from '../../components/EmptyState'
import Pagination from '../../components/Pagination'

const STATUS = ['pending', 'approved', 'rejected']

function Stars({ value }) {
  return (
    <span className="inline-flex gap-0.5">
      {[1, 2, 3, 4, 5].map((n) => (
        <span key={n} className={n <= value ? 'text-amber-400' : 'text-gray-200'}>★</span>
      ))}
    </span>
  )
}

export default function ReviewsAdmin() {
  const { t } = useLanguage()
  const { toast } = useToast()
  const [reviews, setReviews] = useState([])
  const [meta, setMeta] = useState({})
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [page, setPage] = useState(1)
  const [status, setStatus] = useState('pending')
  const [search, setSearch] = useState('')

  const load = () => {
    setLoading(true)
    const params = new URLSearchParams({ page })
    if (status) params.set('status', status)
    if (search) params.set('search', search)

    client
      .get(`/admin/reviews?${params.toString()}`)
      .then(({ data }) => {
        setReviews(data.data || [])
        setMeta(data.meta || {})
      })
      .catch(() => setError(t('errLoadReviewsAdmin')))
      .finally(() => setLoading(false))
  }

  useEffect(() => {
    const delay = setTimeout(load, search ? 300 : 0)
    return () => clearTimeout(delay)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [page, status, search])

  const updateStatus = async (review, nextStatus) => {
    try {
      await client.patch(`/admin/reviews/${review.id}`, { status: nextStatus })
      toast(nextStatus === 'approved' ? t('reviewApproved') : t('reviewRejected'), 'success')
      load()
    } catch {
      toast(t('errUpdateReview'), 'error')
    }
  }

  const handleDelete = async (review) => {
    try {
      await client.delete(`/admin/reviews/${review.id}`)
      toast(t('reviewDeleted'), 'success')
      load()
    } catch {
      toast(t('errUpdateReview'), 'error')
    }
  }

  return (
    <div>
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
        <h1 className="text-2xl font-bold text-gray-900">{t('reviewsAdminTitle')}</h1>
        <input
          value={search}
          onChange={(e) => {
            setSearch(e.target.value)
            setPage(1)
          }}
          placeholder={t('searchPlaceholder')}
          className="px-3 py-2 border border-gray-300 rounded-lg text-sm w-full sm:w-64"
        />
      </div>

      <div className="flex gap-2 mb-4">
        {STATUS.map((s) => (
          <button
            key={s}
            onClick={() => {
              setStatus(s)
              setPage(1)
            }}
            className={`px-4 py-1.5 rounded-full text-sm font-medium ${
              status === s ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'
            }`}
          >
            {s === 'pending' ? t('statusPending') : s === 'approved' ? t('statusApproved') : t('statusRejected')}
          </button>
        ))}
      </div>

      {error ? (
        <ErrorState message={error} />
      ) : loading ? (
        <Spinner />
      ) : reviews.length === 0 ? (
        <EmptyState title={t('noReviewsAdmin')} />
      ) : (
        <>
          <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{t('thBook')}</th>
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{t('thUser')}</th>
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{t('thRating')}</th>
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{t('thComment')}</th>
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{t('thStatus')}</th>
                    <th className="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{t('actions')}</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {reviews.map((review) => (
                    <tr key={review.id} className="hover:bg-gray-50">
                      <td className="px-4 py-3 text-sm font-medium text-gray-800">{review.book?.title || '-'}</td>
                      <td className="px-4 py-3 text-sm text-gray-600">{review.user?.name || '-'}</td>
                      <td className="px-4 py-3"><Stars value={review.rating} /></td>
                      <td className="px-4 py-3 text-sm text-gray-600 max-w-xs truncate">{review.comment || '-'}</td>
                      <td className="px-4 py-3">
                        <span className={`px-2 py-1 text-xs font-semibold rounded-full ${
                          review.status === 'approved'
                            ? 'bg-emerald-100 text-emerald-700'
                            : review.status === 'rejected'
                              ? 'bg-red-100 text-red-700'
                              : 'bg-amber-100 text-amber-700'
                        }`}>
                          {review.status === 'approved' ? t('statusApproved') : review.status === 'rejected' ? t('statusRejected') : t('statusPending')}
                        </span>
                      </td>
                      <td className="px-4 py-3 text-right whitespace-nowrap">
                        {review.status !== 'approved' && (
                          <button
                            onClick={() => updateStatus(review, 'approved')}
                            className="text-xs font-semibold text-emerald-600 hover:underline mr-3"
                          >
                            {t('approve')}
                          </button>
                        )}
                        {review.status !== 'rejected' && (
                          <button
                            onClick={() => updateStatus(review, 'rejected')}
                            className="text-xs font-semibold text-red-500 hover:underline mr-3"
                          >
                            {t('reject')}
                          </button>
                        )}
                        <button
                          onClick={() => handleDelete(review)}
                          className="text-xs font-semibold text-gray-500 hover:underline"
                        >
                          {t('delete')}
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
          <Pagination meta={meta} onPageChange={setPage} />
        </>
      )}
    </div>
  )
}