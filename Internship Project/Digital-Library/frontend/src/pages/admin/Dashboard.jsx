import { useEffect, useState } from 'react'
import client from '../../api/client'
import Spinner from '../../components/Spinner'
import ErrorState from '../../components/ErrorState'
import { useLanguage } from '../../context/LanguageContext'

export default function Dashboard() {
  const { t } = useLanguage()
  const [data, setData] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  useEffect(() => {
    client
      .get('/admin/dashboard')
      .then(({ data }) => setData(data))
      .catch(() => setError(t('errLoadDashboard')))
      .finally(() => setLoading(false))
  }, [t])

  if (loading) return <Spinner />
  if (error) return <ErrorState message={error} />
  if (!data) return null

  const { stats, revenue_by_month: revenue, recent_payments: payments, recent_users: users } = data

  const cards = [
    { labelKey: 'dUsers', value: stats.users, icon: '👥', color: 'bg-blue-50 text-blue-600' },
    { labelKey: 'dBooks', value: stats.books, icon: '📚', color: 'bg-indigo-50 text-indigo-600' },
    { labelKey: 'dCourses', value: stats.courses, icon: '🎓', color: 'bg-purple-50 text-purple-600' },
    { labelKey: 'dCategories', value: stats.categories, icon: '🗂️', color: 'bg-amber-50 text-amber-600' },
    { labelKey: 'dPurchases', value: stats.purchases, icon: '🛒', color: 'bg-emerald-50 text-emerald-600' },
    { labelKey: 'dActiveSharing', value: stats.active_sharing, icon: '🔗', color: 'bg-teal-50 text-teal-600' },
    { labelKey: 'dTotalRevenue', value: `$${Number(stats.total_revenue).toLocaleString()}`, icon: '💰', color: 'bg-green-50 text-green-600' },
    { labelKey: 'dMonthlyRevenue', value: `$${Number(stats.monthly_revenue).toLocaleString()}`, icon: '📈', color: 'bg-orange-50 text-orange-600' },
  ]

  const statusText = {
    completed: t('statusCompleted'),
    pending: t('statusPending'),
    failed: t('statusFailed'),
    cancelled: t('statusCancelled'),
  }

  return (
    <div>
      <h1 className="text-2xl font-bold text-gray-900 mb-6">{t('dashboards')}</h1>

      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        {cards.map((card) => (
          <div key={card.labelKey} className="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div className={`w-10 h-10 rounded-lg flex items-center justify-center text-xl ${card.color}`}>
              {card.icon}
            </div>
            <div className="mt-3 text-2xl font-bold text-gray-900">{card.value}</div>
            <div className="text-sm text-gray-500">{t(card.labelKey)}</div>
          </div>
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {/* Revenue chart */}
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
          <h2 className="font-semibold text-gray-900 mb-4">{t('dMonthlyRevenue')}</h2>
          <div className="flex items-end gap-3 h-48">
            {revenue.map((r) => {
              const max = Math.max(...revenue.map((x) => x.total), 1)
              const h = Math.max((r.total / max) * 100, 2)
              return (
                <div key={r.month} className="flex-1 flex flex-col items-center gap-1">
                  <span className="text-xs text-gray-600 font-medium">${Math.round(r.total)}</span>
                  <div className="w-full bg-indigo-500 rounded-t-lg" style={{ height: `${h}%` }} />
                  <span className="text-xs text-gray-400">{r.month.slice(2)}</span>
                </div>
              )
            })}
          </div>
        </div>

        {/* Recent users */}
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
          <h2 className="font-semibold text-gray-900 mb-4">{t('recentUsers')}</h2>
          <div className="space-y-3">
            {users.map((u) => (
              <div key={u.id} className="flex items-center gap-3">
                <span className="w-9 h-9 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold uppercase">
                  {u.name[0]}
                </span>
                <div className="min-w-0">
                  <p className="font-medium text-gray-900 truncate">{u.name}</p>
                  <p className="text-xs text-gray-500 truncate">{u.email}</p>
                </div>
                <span className="ml-auto text-xs text-gray-400">
                  {new Date(u.created_at).toLocaleDateString()}
                </span>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Recent payments */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h2 className="font-semibold text-gray-900 mb-4">{t('recentPayments')}</h2>
        {payments.length === 0 ? (
          <p className="text-sm text-gray-400">{t('noPayments')}</p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="text-left text-xs font-semibold text-gray-500 uppercase">
                  <th className="pb-2">{t('thUser')}</th>
                  <th className="pb-2">{t('thReference')}</th>
                  <th className="pb-2">{t('thAmount')}</th>
                  <th className="pb-2">{t('thStatus')}</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {payments.map((p) => (
                  <tr key={p.id}>
                    <td className="py-2 text-sm text-gray-800">{p.user?.name}</td>
                    <td className="py-2 text-sm font-mono text-gray-600">{p.reference}</td>
                    <td className="py-2 text-sm font-semibold">${Number(p.amount).toFixed(2)}</td>
                    <td className="py-2">
                      <span className={`px-2 py-1 text-xs font-semibold rounded-full ${
                        p.status === 'completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'
                      }`}>
                        {statusText[p.status] || p.status}
                      </span>
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
