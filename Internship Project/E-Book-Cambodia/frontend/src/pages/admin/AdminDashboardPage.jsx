import { useQuery } from '@tanstack/react-query'
import { adminGetDashboard, avatarUrl } from '../../api'
import { Link } from 'react-router-dom'
import { useLanguage } from '../../context/LanguageContext'

export default function AdminDashboardPage() {
  const { t } = useLanguage()

  const { data, isLoading } = useQuery({
    queryKey: ['admin-dashboard'],
    refetchInterval: 10000,
    queryFn: () => adminGetDashboard(),
  })

  const dashboard = data?.data
  const stats = dashboard?.stats || {}

  const statCards = [
    { label: t('totalUsers'), value: stats.users || 0, color: 'text-indigo-600', bg: 'bg-indigo-50', icon: '👥' },
    { label: t('totalBooks'), value: stats.books || 0, color: 'text-emerald-600', bg: 'bg-emerald-50', icon: '📚' },
    { label: t('totalRevenue'), value: `$${stats.total_revenue || 0}`, color: 'text-amber-600', bg: 'bg-amber-50', icon: '💰' },
    { label: 'Monthly Revenue', value: `$${stats.monthly_revenue || 0}`, color: 'text-teal-600', bg: 'bg-teal-50', icon: '📈' },
    { label: t('totalPurchases'), value: stats.purchases || 0, color: 'text-rose-600', bg: 'bg-rose-50', icon: '🛒' },
    { label: 'Pending Reviews', value: stats.pending_reviews || 0, color: 'text-purple-600', bg: 'bg-purple-50', icon: '⭐' },
    { label: 'Pending Messages', value: stats.pending_messages || 0, color: 'text-cyan-600', bg: 'bg-cyan-50', icon: '💬' },
  ]

  if (isLoading) {
    return (
      <div className="max-w-7xl mx-auto px-4 py-8">
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
          {[...Array(6)].map((_, i) => (
            <div key={i} className="h-24 bg-gray-200 rounded-xl animate-pulse" />
          ))}
        </div>
      </div>
    )
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <h1 className="text-2xl font-bold text-gray-900 mb-6">{t('adminDashboard')}</h1>

      {/* Stats cards */}
      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        {statCards.map((card) => (
          <div key={card.label} className={`${card.bg} rounded-2xl p-4`}>
            <div className="text-2xl mb-2">{card.icon}</div>
            <div className={`text-2xl font-bold ${card.color}`}>{card.value}</div>
            <div className="text-xs text-gray-600 mt-1">{card.label}</div>
          </div>
        ))}
      </div>

      {/* Quick actions */}
      <div className="bg-white rounded-2xl shadow-sm p-6 mb-8">
        <h2 className="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
          <Link to="/admin/books/new" className="px-4 py-3 bg-indigo-600 text-white rounded-xl text-center text-sm font-medium hover:bg-indigo-700">
            + {t('addBook')}
          </Link>
          <Link to="/admin/categories" className="px-4 py-3 bg-gray-100 text-gray-800 rounded-xl text-center text-sm font-medium hover:bg-gray-200">
            + {t('addCategory')}
          </Link>
          <Link to="/admin/authors" className="px-4 py-3 bg-gray-100 text-gray-800 rounded-xl text-center text-sm font-medium hover:bg-gray-200">
            {t('adminAuthors')}
          </Link>
          <Link to="/admin/users" className="px-4 py-3 bg-gray-100 text-gray-800 rounded-xl text-center text-sm font-medium hover:bg-gray-200">
            {t('adminUsers')}
          </Link>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {/* Recent payments */}
        <div className="bg-white rounded-2xl shadow-sm p-6">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-lg font-semibold text-gray-900">{t('adminPurchases')}</h2>
            <Link to="/admin/purchases" className="text-sm text-indigo-600 hover:text-indigo-700">
              {t('viewAll')} →
            </Link>
          </div>
          <div className="space-y-3">
            {(dashboard?.recent_payments || []).map((payment) => (
              <div key={payment.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                <div>
                  <div className="font-medium text-gray-800 capitalize">{payment.method}</div>
                  <div className="text-xs text-gray-500">
                    {payment.user?.name} · {new Date(payment.created_at).toLocaleDateString()}
                  </div>
                </div>
                <div className="flex items-center gap-2">
                  <span className="text-sm font-bold text-gray-800">${payment.amount}</span>
                  <span
                    className={`text-xs px-2 py-1 rounded-full capitalize ${
                      payment.status === 'completed'
                        ? 'bg-emerald-100 text-emerald-700'
                        : payment.status === 'pending'
                        ? 'bg-amber-100 text-amber-700'
                        : 'bg-red-100 text-red-600'
                    }`}
                  >
                    {payment.status}
                  </span>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Recent messages */}
        <div className="bg-white rounded-2xl shadow-sm p-6">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-lg font-semibold text-gray-900">{t('myMessages')}</h2>
            <Link to="/admin/messages" className="text-sm text-indigo-600 hover:text-indigo-700">
              {t('viewAll')} →
            </Link>
          </div>
          <div className="space-y-3">
            {(dashboard?.recent_messages || []).map((message) => (
              <div key={message.id} className="p-3 bg-gray-50 rounded-xl">
                <div className="flex items-center justify-between gap-2">
                  <div className="font-medium text-gray-800 truncate">{message.subject || t('noSubject')}</div>
                  <span
                    className={`shrink-0 text-xs px-2 py-1 rounded-full ${
                      message.is_replied ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'
                    }`}
                  >
                    {message.is_replied ? t('replied') : t('pending')}
                  </span>
                </div>
                <div className="text-xs text-gray-500 mt-0.5">
                  {message.user?.name || message.sender_name || '-'} · {new Date(message.created_at).toLocaleDateString()}
                </div>
              </div>
            ))}
            {(dashboard?.recent_messages || []).length === 0 && (
              <div className="text-center text-gray-500 py-4 text-sm">{t('noMessages')}</div>
            )}
          </div>
        </div>

        {/* Recent users */}
        <div className="bg-white rounded-2xl shadow-sm p-6">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-lg font-semibold text-gray-900">Recent Users</h2>
            <Link to="/admin/users" className="text-sm text-indigo-600 hover:text-indigo-700">
              {t('viewAll')} →
            </Link>
          </div>
          <div className="space-y-3">
            {(dashboard?.recent_users || []).map((user) => (
              <div key={user.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                <div className="flex items-center gap-3">
                  {avatarUrl(user) ? (
                    <img src={avatarUrl(user)} alt="" className="w-9 h-9 rounded-full object-cover" />
                  ) : (
                    <div className="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold">
                      {user.name?.charAt(0)?.toUpperCase()}
                    </div>
                  )}
                  <div>
                    <div className="font-medium text-gray-800">{user.name}</div>
                    <div className="text-xs text-gray-500">{user.email}</div>
                  </div>
                </div>
                <span className="text-xs px-2 py-1 rounded-full bg-indigo-50 text-indigo-600 capitalize">
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
