import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { adminGetVipTestCards, adminRevokeVipTestCard } from '../../api'
import { useLanguage } from '../../context/LanguageContext'

export default function AdminVipTestCardsPage() {
  const { t } = useLanguage()
  const [page, setPage] = useState(1)
  const [now] = useState(() => Date.now())
  const queryClient = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['admin-vip-test-cards', page],
    refetchInterval: 10000,
    queryFn: () => adminGetVipTestCards({ per_page: 15, page }),
  })

  const revokeMutation = useMutation({
    mutationFn: (purchaseId) => adminRevokeVipTestCard(purchaseId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-vip-test-cards'] })
    },
    onError: (error) => {
      alert(error.response?.data?.message || 'Revoke failed')
    },
  })

  const stats = data?.data?.stats
  const purchases = data?.data?.purchases?.data || []
  const pagination = data?.data?.purchases
  const lastPage = pagination?.last_page || 1

  const statCards = stats
    ? [
        { label: t('vipTotal'), value: stats.total, tint: 'text-gray-700 bg-gray-100' },
        { label: 'Paid', value: stats.paid, tint: 'text-emerald-700 bg-emerald-50' },
        { label: 'Active now', value: stats.active, tint: 'text-amber-700 bg-amber-50' },
      ]
    : []

  const formatDateTime = (value) =>
    value ? new Date(value).toLocaleString() : '—'

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{t('adminVipTestCards')}</h1>
          <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-100 text-amber-700 text-xs font-bold mt-2">
            TEST ONLY - $0.01 - 5 HOURS
          </span>
          <p className="text-gray-500 text-sm mt-2">
            VIP test-card subscriptions paid through the real Bakong KHQR flow. Not included in
            production revenue reports.
          </p>
        </div>
      </div>

      {statCards.length > 0 && (
        <div className="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
          {statCards.map((stat) => (
            <div key={stat.label} className="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
              <p className="text-xl font-bold text-gray-900">{stat.value}</p>
              <p className="text-xs text-gray-500">{stat.label}</p>
            </div>
          ))}
        </div>
      )}

      <div className="bg-white rounded-2xl shadow-sm overflow-hidden">
        {isLoading ? (
          <div className="p-8 text-center text-gray-500">{t('loading')}</div>
        ) : purchases.length === 0 ? (
          <div className="p-8 text-center text-gray-500">No test-card purchases yet.</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('name')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('email')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Purchased at</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Activated at</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expires at</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Action</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200">
                {purchases.map((purchase) => {
                  const user = purchase.user
                  const payment = purchase.payment
                  const active =
                    !!user?.vip_until && new Date(user.vip_until).getTime() > now
                  return (
                    <tr key={purchase.id} className="hover:bg-gray-50">
                      <td className="px-6 py-4 text-sm text-gray-500">{purchase.id}</td>
                      <td className="px-6 py-4">
                        <div className="flex items-center gap-3">
                          <div className="w-9 h-9 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center font-bold">
                            {user?.name?.charAt(0)?.toUpperCase()}
                          </div>
                          <div className="text-sm font-medium text-gray-900">{user?.name || '—'}</div>
                        </div>
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-600">{user?.email || '—'}</td>
                      <td className="px-6 py-4">
                        <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ${
                          payment?.status === 'completed'
                            ? 'bg-emerald-50 text-emerald-700'
                            : payment?.status === 'failed'
                              ? 'bg-red-50 text-red-600'
                              : 'bg-gray-100 text-gray-500'
                        }`}>
                          {(payment?.status || '—').toUpperCase()}
                        </span>
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-600">{formatDateTime(purchase.created_at)}</td>
                      <td className="px-6 py-4 text-sm text-gray-600">{formatDateTime(purchase.activated_at)}</td>
                      <td className="px-6 py-4 text-sm text-gray-600">
                        {formatDateTime(user?.vip_until)}
                        {active && <span className="ml-2 text-[10px] font-bold text-emerald-600">ACTIVE</span>}
                      </td>
                      <td className="px-6 py-4 text-right">
                        <button
                          onClick={() => revokeMutation.mutate(purchase.id)}
                          disabled={revokeMutation.isPending || !active}
                          className="px-3 py-1.5 text-xs font-medium rounded-lg border border-red-200 text-red-600 hover:bg-red-50 disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                          Revoke
                        </button>
                      </td>
                    </tr>
                  )
                })}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {lastPage > 1 && (
        <div className="mt-6 flex items-center justify-center gap-4">
          <button
            disabled={page <= 1}
            onClick={() => setPage((p) => Math.max(1, p - 1))}
            className="px-4 py-2 text-sm border border-gray-200 rounded-xl disabled:opacity-40 hover:bg-gray-50"
          >
            {t('previous')}
          </button>
          <span className="text-sm text-gray-600">Page {page} of {lastPage}</span>
          <button
            disabled={page >= lastPage}
            onClick={() => setPage((p) => Math.min(lastPage, p + 1))}
            className="px-4 py-2 text-sm border border-gray-200 rounded-xl disabled:opacity-40 hover:bg-gray-50"
          >
            {t('next')}
          </button>
        </div>
      )}
    </div>
  )
}