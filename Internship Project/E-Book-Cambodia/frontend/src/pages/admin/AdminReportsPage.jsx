import { useQuery } from '@tanstack/react-query'
import { adminGetDashboard, avatarUrl } from '../../api'
import { useLanguage } from '../../context/LanguageContext'

function monthLabel(ym) {
  const [y, m] = ym.split('-')
  return new Date(y, m - 1, 1).toLocaleDateString(undefined, { month: 'short' })
}

function formatMoney(value) {
  const amount = Number(value) || 0
  return `$${amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
}

export default function AdminReportsPage() {
  const { t } = useLanguage()

  const { data, isLoading } = useQuery({
    queryKey: ['admin-reports'],
    refetchInterval: 10000,
    queryFn: () => adminGetDashboard(),
  })

  if (isLoading) {
    return (
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="h-10 bg-gray-200 rounded-lg animate-pulse mb-6" />
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          {[...Array(4)].map((_, i) => (
            <div key={i} className="h-24 bg-gray-200 rounded-2xl animate-pulse" />
          ))}
        </div>
      </div>
    )
  }

  const stats = data?.data?.stats || {}
  const revenueByMonth = data?.data?.revenue_by_month || []
  const recentPayments = data?.data?.recent_payments || []
  const recentUsers = data?.data?.recent_users || []
  const maxRevenue = Math.max(1, ...revenueByMonth.map((r) => r.total))

  const summary = [
    { label: 'Total Revenue', value: formatMoney(stats.total_revenue), icon: '💰', color: 'text-amber-600', bg: 'bg-amber-50' },
    { label: 'Monthly Revenue', value: formatMoney(stats.monthly_revenue), icon: '📈', color: 'text-teal-600', bg: 'bg-teal-50' },
    { label: t('totalUsers'), value: stats.users || 0, icon: '👥', color: 'text-indigo-600', bg: 'bg-indigo-50' },
    { label: t('totalBooks'), value: stats.books || 0, icon: '📚', color: 'text-emerald-600', bg: 'bg-emerald-50' },
    { label: t('totalPurchases') || 'Total Purchases', value: stats.purchases || 0, icon: '🛒', color: 'text-rose-600', bg: 'bg-rose-50' },
    { label: 'Pending Reviews', value: stats.pending_reviews || 0, icon: '⭐', color: 'text-purple-600', bg: 'bg-purple-50' },
    { label: 'Pending Messages', value: stats.pending_messages || 0, icon: '💬', color: 'text-cyan-600', bg: 'bg-cyan-50' },
    { label: 'Active Sharing', value: stats.active_sharing || 0, icon: '🔗', color: 'text-sky-600', bg: 'bg-sky-50' },
  ]

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <h1 className="text-2xl font-bold text-gray-900 mb-6">{t('adminReports')}</h1>

      {/* Summary stats */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        {summary.map((card) => (
          <div key={card.label} className={`${card.bg} rounded-2xl p-4`}>
            <div className="text-2xl mb-2">{card.icon}</div>
            <div className={`text-2xl font-bold ${card.color}`}>{card.value}</div>
            <div className="text-xs text-gray-600 mt-1">{card.label}</div>
          </div>
        ))}
      </div>

      {/* Revenue by month */}
      <div className="bg-white rounded-2xl shadow-sm p-6 mb-8">
        <h2 className="text-lg font-semibold text-gray-900 mb-5">Revenue by Month (6 months)</h2>
        <div className="flex items-end justify-between gap-3 h-44">
          {revenueByMonth.map((row) => (
            <div key={row.month} className="flex-1 flex flex-col items-center gap-2 min-w-0">
              <div className="text-xs font-semibold text-gray-600 truncate max-w-full">
                {row.total > 0 ? `$${row.total}` : ''}
              </div>
              <div
                className="w-full max-w-[64px] rounded-t-lg bg-gradient-to-t from-indigo-600 to-indigo-400 transition-all duration-300"
                style={{ height: `${Math.max(4, Math.round((row.total / maxRevenue) * 100))}%`, minHeight: row.total > 0 ? '8px' : '4px' }}
              />
              <div className="text-xs text-gray-500">{monthLabel(row.month)}</div>
            </div>
          ))}
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {/* Recent payments */}
        <div className="bg-white rounded-2xl shadow-sm p-6">
          <h2 className="text-lg font-semibold text-gray-900 mb-4">{t('adminOrders')}</h2>
          {recentPayments.length === 0 ? (
            <p className="text-sm text-gray-500">{t('noNotifications')}</p>
          ) : (
            <div className="space-y-3">
              {recentPayments.map((payment) => (
                <div key={payment.id} className="p-3 bg-gray-50 rounded-xl">
                  <div className="flex items-center justify-between gap-2">
                    <div className="font-medium text-gray-800 capitalize truncate">{payment.method}</div>
                    <span className="text-sm font-bold text-gray-800 shrink-0">${payment.amount}</span>
                  </div>
                  <div className="text-xs text-gray-500 mt-0.5">
                    {payment.user?.name || '-'} · {new Date(payment.created_at).toLocaleDateString()}
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Recent users */}
        <div className="bg-white rounded-2xl shadow-sm p-6">
          <h2 className="text-lg font-semibold text-gray-900 mb-4">Recent Users</h2>
          <div className="space-y-3">
            {recentUsers.map((user) => (
              <div key={user.id} className="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                {avatarUrl(user) ? (
                  <img src={avatarUrl(user)} alt="" className="w-9 h-9 rounded-full object-cover" />
                ) : (
                  <div className="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold">
                    {user.name?.charAt(0)?.toUpperCase()}
                  </div>
                )}
                <div className="min-w-0 flex-1">
                  <div className="font-medium text-gray-800 truncate">{user.name}</div>
                  <div className="text-xs text-gray-500 truncate">{user.email}</div>
                </div>
                <span className="text-xs px-2 py-1 rounded-full bg-indigo-50 text-indigo-600 capitalize shrink-0">
                  {user.role || 'reader'}
                </span>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  )
}
