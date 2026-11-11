import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { adminGetVipUsers } from '../../api'
import { useLanguage } from '../../context/LanguageContext'

export default function AdminVipUsersPage() {
  const { t } = useLanguage()
  const [search, setSearch] = useState('')
  const [page, setPage] = useState(1)

  const { data, isLoading } = useQuery({
    queryKey: ['admin-vip-users', search, page],
    refetchInterval: 10000,
    queryFn: () =>
      adminGetVipUsers({ search, per_page: 15, page }).then((res) => {
        const payload = res.data
        payload.users.data = payload.users.data.map((user) => ({
          ...user,
          vipActive: !!(user.vip_until && new Date(user.vip_until).getTime() > Date.now()),
        }))
        return payload
      }),
  })

  const stats = data?.stats
  const users = data?.users?.data || []
  const pagination = data?.users
  const lastPage = pagination?.last_page || 1

  const statCards = stats
    ? [
        { label: t('vipActive'), value: stats.active, icon: '👑', tint: 'text-emerald-700 bg-emerald-50' },
        { label: t('vipExpired'), value: stats.expired, icon: '⏳', tint: 'text-gray-600 bg-gray-100' },
        { label: t('vipTotal'), value: stats.total, icon: '👥', tint: 'text-indigo-700 bg-indigo-50' },
        { label: t('vipRevenue'), value: `$${Number(stats.revenue ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 })}`, icon: '💰', tint: 'text-amber-700 bg-amber-50' },
      ]
    : []

  const isActive = (user) => user.vipActive

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{t('adminVipUsers')}</h1>
          <p className="text-gray-500 text-sm mt-1">{t('vipMembers')}</p>
        </div>
        <input
          type="text"
          placeholder={t('search')}
          value={search}
          onChange={(e) => { setSearch(e.target.value); setPage(1) }}
          className="px-4 py-2 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        />
      </div>

      {statCards.length > 0 && (
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
          {statCards.map((stat) => (
            <div key={stat.label} className="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
              <div className="flex items-center gap-3">
                <span className={`w-11 h-11 rounded-xl flex items-center justify-center text-xl ${stat.tint}`}>
                  {stat.icon}
                </span>
                <div className="min-w-0">
                  <p className="text-xl font-bold text-gray-900 truncate">{stat.value}</p>
                  <p className="text-xs text-gray-500">{stat.label}</p>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      <div className="bg-white rounded-2xl shadow-sm overflow-hidden">
        {isLoading ? (
          <div className="p-8 text-center text-gray-500">{t('loading')}</div>
        ) : users.length === 0 ? (
          <div className="p-8 text-center text-gray-500">{t('noUsers')}</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('name')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('email')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('vipPlan')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('vipExpires')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('vipStatus')}</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{t('purchases')}</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200">
                {users.map((user) => {
                  const active = isActive(user)
                  return (
                    <tr key={user.id} className="hover:bg-gray-50">
                      <td className="px-6 py-4 text-sm text-gray-500">{user.id}</td>
                      <td className="px-6 py-4">
                        <div className="flex items-center gap-3">
                          <div className="w-9 h-9 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center font-bold">
                            {user.name?.charAt(0)?.toUpperCase()}
                          </div>
                          <div className="text-sm font-medium text-gray-900">{user.name}</div>
                        </div>
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-600">{user.email}</td>
                      <td className="px-6 py-4">
                        <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 text-xs font-medium">
                          {user.vip_plan || '—'}
                        </span>
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-600">
                        {user.vip_until ? new Date(user.vip_until).toLocaleDateString() : '—'}
                      </td>
                      <td className="px-6 py-4">
                        <span className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ${active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500'}`}>
                          <span className={`w-1.5 h-1.5 rounded-full ${active ? 'bg-emerald-500' : 'bg-gray-400'}`} />
                          {active ? t('vipActiveNow') : t('vipExpiredNow')}
                        </span>
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-600 text-right">{user.purchases_count ?? 0}</td>
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
