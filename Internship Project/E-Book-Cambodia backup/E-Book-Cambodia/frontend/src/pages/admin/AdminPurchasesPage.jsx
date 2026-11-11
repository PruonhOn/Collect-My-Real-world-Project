import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { adminGetPayments, adminApprovePayment } from '../../api'
import AdminNav from '../../components/admin/AdminNav'
import { useLanguage } from '../../context/LanguageContext'

const STATUS_STYLES = {
  pending: 'bg-amber-100 text-amber-700',
  completed: 'bg-emerald-100 text-emerald-700',
  failed: 'bg-red-100 text-red-600',
  cancelled: 'bg-gray-100 text-gray-600',
}

export default function AdminPurchasesPage() {
  const { t } = useLanguage()
  const queryClient = useQueryClient()
  const [status, setStatus] = useState('')

  const { data, isLoading } = useQuery({
    queryKey: ['admin-payments', status],
    queryFn: () => adminGetPayments({ status, per_page: 20 }),
  })

  const payments = data?.data?.data || []

  const approveMutation = useMutation({
    mutationFn: (id) => adminApprovePayment(id),
    onSuccess: () => queryClient.invalidateQueries(['admin-payments']),
  })

  const statusColor = (s) => STATUS_STYLES[s] || STATUS_STYLES.pending

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <AdminNav />

      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{t('adminPurchases')}</h1>
          <p className="text-gray-500 text-sm mt-1">{payments.length} {t('purchases')}</p>
        </div>
        <select
          value={status}
          onChange={(e) => setStatus(e.target.value)}
          className="px-4 py-2 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
          <option value="">All</option>
          <option value="pending">Pending</option>
          <option value="completed">Completed</option>
          <option value="failed">Failed</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>

      <div className="bg-white rounded-2xl shadow-sm overflow-hidden">
        {isLoading ? (
          <div className="p-8 text-center text-gray-500">{t('loading')}</div>
        ) : payments.length === 0 ? (
          <div className="p-8 text-center text-gray-500">{t('noBooks')}</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('name')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('title')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('amount')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('method')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('status')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('joined')}</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Action</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200">
                {payments.map((payment) => (
                  <tr key={payment.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4 text-sm text-gray-500">{payment.id}</td>
                    <td className="px-6 py-4">
                      <div className="text-sm font-medium text-gray-900">{payment.user?.name}</div>
                      <div className="text-xs text-gray-500">{payment.user?.email}</div>
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-600">{payment.purchase?.book?.title || '-'}</td>
                    <td className="px-6 py-4 text-sm font-medium text-gray-900">
                      {payment.currency === 'KHR' ? `${Number(payment.amount).toLocaleString()} ៛` : `$${payment.amount}`}
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-600">{payment.method || '-'}</td>
                    <td className="px-6 py-4">
                      <span className={`text-xs font-medium px-2 py-1 rounded-full ${statusColor(payment.status)}`}>
                        {payment.status}
                      </span>
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-500">
                      {new Date(payment.created_at).toLocaleDateString()}
                    </td>
                    <td className="px-6 py-4 text-right text-sm space-x-2 whitespace-nowrap">
                      {payment.status === 'pending' && (
                        <button
                          onClick={() => approveMutation.mutate(payment.id)}
                          className="text-emerald-600 hover:text-emerald-800 font-medium"
                        >
                          Approve
                        </button>
                      )}
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