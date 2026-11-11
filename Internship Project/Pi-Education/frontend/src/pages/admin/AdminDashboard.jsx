import { useQuery } from '@tanstack/react-query'
import api from '../../api/client'
import Loading from '../../components/common/Loading'
import ErrorState from '../../components/common/ErrorState'
import { useI18n } from '../../i18n'

function formatPrice(price, locale) {
  return new Intl.NumberFormat(locale === 'km' ? 'en-US' : locale, {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
  }).format(price)
}

const statIcons = [
  <svg key="0" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M4 19.5A2.5 2.5 0 016.5 17H20" /><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z" /></svg>,
  <svg key="1" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M22 11.08V12a10 10 0 11-5.93-9.14" /><path d="M22 4L12 14.01l-3-3" /></svg>,
  <svg key="2" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" /></svg>,
  <svg key="3" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M6 3h12l4 6-10 13L2 9z" /><path d="M11 3 8 9l4 13 4-13-3-6M2 9h20" /></svg>,
  <svg key="4" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" /><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" /></svg>,
  <svg key="5" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" /><path d="M1 10h22" /></svg>,
  <svg key="6" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><line x1="12" y1="1" x2="12" y2="23" /><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6" /></svg>,
  <svg key="7" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" /><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" /></svg>,
]

export default function AdminDashboard() {
  const { t, locale } = useI18n()

  const { data, isLoading, isError } = useQuery({
    queryKey: ['admin-dashboard'],
    queryFn: async () => {
      const res = await api.get('/admin/dashboard')
      return res.data.data
    },
  })

  if (isLoading) return <Loading full />
  if (isError) return <ErrorState />

  const { counters, recent_orders: recentOrders } = data

  const stats = [
    { label: t('admin.totalBooks'), value: counters.total_books },
    { label: t('admin.publishedBooks'), value: counters.published_books },
    { label: t('admin.totalUsers'), value: counters.total_users },
    { label: t('admin.activeVip'), value: counters.active_vip_users },
    { label: t('admin.totalOrders'), value: counters.total_orders },
    { label: t('admin.paidOrders'), value: counters.paid_orders },
    { label: t('admin.revenue'), value: formatPrice(counters.revenue, locale) },
    { label: t('admin.pendingReviews'), value: counters.pending_reviews },
  ]

  return (
    <div>
      <div className="max-w-2xl">
        <p className="section-eyebrow">Admin</p>
        <h1 className="section-title">{t('admin.dashboard')}</h1>
        <p className="section-subtitle">An overview of the Pi-Education platform.</p>
      </div>

      <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {stats.map((stat, i) => (
          <div key={stat.label} className="card flex items-start justify-between p-5">
            <div>
              <p className="text-xs font-bold tracking-widest text-slate-400 uppercase">{stat.label}</p>
              <p className="mt-2 text-3xl font-extrabold text-slate-900">{stat.value}</p>
            </div>
            <span className="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
              {statIcons[i % statIcons.length]}
            </span>
          </div>
        ))}
      </div>

      <div className="card mt-8 overflow-hidden">
        <div className="border-b border-slate-100 px-6 py-4">
          <h2 className="font-bold text-slate-900">{t('admin.recentOrders')}</h2>
        </div>
        {recentOrders.length === 0 ? (
          <p className="px-6 py-8 text-center text-sm text-slate-500">{t('admin.noRecentOrders')}</p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <thead className="border-b border-slate-200 bg-slate-50 text-xs font-bold tracking-wide text-slate-500 uppercase">
                <tr>
                  <th className="px-6 py-3.5">{t('common.orderNo')}</th>
                  <th className="px-6 py-3.5">Email</th>
                  <th className="px-6 py-3.5">{t('common.status')}</th>
                  <th className="px-6 py-3.5">{t('common.amount')}</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {recentOrders.map((order) => (
                  <tr key={order.id} className="transition hover:bg-slate-50/70">
                    <td className="px-6 py-4 font-semibold text-slate-900">{order.order_number}</td>
                    <td className="px-6 py-4 text-slate-600">{order.user_email || '—'}</td>
                    <td className="px-6 py-4">
                      <span
                        className={`badge ring-1 ${
                          order.status === 'paid'
                            ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                            : order.status === 'pending'
                              ? 'bg-amber-50 text-amber-700 ring-amber-200'
                              : 'bg-slate-100 text-slate-600 ring-slate-200'
                        }`}
                      >
                        {order.status.toUpperCase()}
                      </span>
                    </td>
                    <td className="px-6 py-4 font-bold text-slate-900">{formatPrice(order.total, locale)}</td>
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