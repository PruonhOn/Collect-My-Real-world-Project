import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { adminGetReviews, adminUpdateReviewStatus, adminDeleteReview } from '../../api'
import AdminNav from '../../components/admin/AdminNav'
import { useLanguage } from '../../context/LanguageContext'

const STATUS_STYLES = {
  approved: 'bg-emerald-100 text-emerald-700',
  pending: 'bg-amber-100 text-amber-700',
  rejected: 'bg-red-100 text-red-600',
}

export default function AdminReviewsPage() {
  const { t } = useLanguage()
  const queryClient = useQueryClient()
  const [status, setStatus] = useState('')

  const { data, isLoading } = useQuery({
    queryKey: ['admin-reviews', status],
    queryFn: () => adminGetReviews({ status, per_page: 50 }),
  })

  const reviews = data?.data?.data || []

  const statusMutation = useMutation({
    mutationFn: ({ id, status }) => adminUpdateReviewStatus(id, status),
    onSuccess: () => queryClient.invalidateQueries(['admin-reviews']),
  })

  const deleteMutation = useMutation({
    mutationFn: (id) => adminDeleteReview(id),
    onSuccess: () => queryClient.invalidateQueries(['admin-reviews']),
  })

  const handleDelete = (review) => {
    if (window.confirm(`${t('confirmDelete')}?`)) {
      deleteMutation.mutate(review.id)
    }
  }

  const statusColor = (s) => STATUS_STYLES[s] || STATUS_STYLES.pending

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <AdminNav />

      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <h1 className="text-2xl font-bold text-gray-900">{t('reviewsManagement')}</h1>
        <select
          value={status}
          onChange={(e) => setStatus(e.target.value)}
          className="px-4 py-2 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
          <option value="">All</option>
          <option value="pending">Pending</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
        </select>
      </div>

      <div className="bg-white rounded-2xl shadow-sm overflow-hidden">
        {isLoading ? (
          <div className="p-8 text-center text-gray-500">{t('loading')}</div>
        ) : reviews.length === 0 ? (
          <div className="p-8 text-center text-gray-500">No reviews yet</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('name')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('title')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('rating')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Comment</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('status')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{t('delete')}</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200">
                {reviews.map((review) => (
                  <tr key={review.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4 text-sm font-medium text-gray-900">{review.user?.name}</td>
                    <td className="px-6 py-4 text-sm text-gray-600">{review.book?.title}</td>
                    <td className="px-6 py-4 text-amber-500">{'★'.repeat(review.rating)}</td>
                    <td className="px-6 py-4 text-sm text-gray-600 max-w-xs truncate">{review.comment || '-'}</td>
                    <td className="px-6 py-4">
                      <select
                        value={review.status}
                        onChange={(e) => statusMutation.mutate({ id: review.id, status: e.target.value })}
                        className={`text-xs font-medium px-2 py-1 rounded-full border-0 ${statusColor(review.status)} focus:outline-none`}
                      >
                        <option value="pending">pending</option>
                        <option value="approved">approved</option>
                        <option value="rejected">rejected</option>
                      </select>
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-500">
                      {new Date(review.created_at).toLocaleDateString()}
                    </td>
                    <td className="px-6 py-4 text-right text-sm">
                      <button onClick={() => handleDelete(review)} className="text-red-600 hover:text-red-800">
                        {t('delete')}
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  )
}