import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import api from '../api/client'
import Loading from '../components/common/Loading'
import ErrorState from '../components/common/ErrorState'
import { useI18n } from '../i18n'

function formatPrice(price, locale) {
  return new Intl.NumberFormat(locale === 'km' ? 'en-US' : locale, {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
  }).format(price)
}

function formatDate(iso, locale) {
  if (!iso) return ''
  return new Date(iso).toLocaleDateString(locale === 'km' ? 'en-US' : locale, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

const statusStyles = {
  pending: 'bg-amber-50 text-amber-700 ring-amber-200',
  paid: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
  cancelled: 'bg-slate-50 text-slate-600 ring-slate-200',
  failed: 'bg-red-50 text-red-700 ring-red-200',
}

export default function Orders() {
  const { t, locale } = useI18n()

  const { data, isLoading, isError } = useQuery({
    queryKey: ['my-orders'],
    queryFn: async () => {
      const res = await api.get('/orders')
      return res.data.data
    },
  })

  if (isLoading) return <Loading full />
  if (isError) return <ErrorState />

  const orders = data?.orders ?? []

  if (orders.length === 0) {
    return (
      <div className="page max-w-3xl text-center">
        <div className="card mx-auto flex h-20 w-20 items-center justify-center rounded-3xl bg-slate-100 text-slate-400 ring-1 ring-slate-200">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" />
            <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" />
          </svg>
        </div>
        <h1 className="mt-6 text-3xl font-extrabold tracking-tight text-slate-900">{t('orders.title')}</h1>
        <p className="mt-3 text-slate-500">{t('orders.emptyDesc')}</p>
        <Link to="/books" className="btn-primary mt-8 px-6 py-3">
          {t('orders.browseBooks')}
        </Link>
      </div>
    )
  }

  return (
    <div className="page">
      <div className="max-w-2xl">
        <p className="section-eyebrow">Account</p>
        <h1 className="section-title">{t('orders.title')}</h1>
        <p className="section-subtitle">Track your orders and payments.</p>
      </div>

      <div className="card mt-8 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead className="border-b border-slate-200 bg-slate-50 text-xs font-bold tracking-wide text-slate-500 uppercase">
              <tr>
                <th className="px-5 py-3.5">{t('common.orderNo')}</th>
                <th className="px-5 py-3.5">{t('common.date')}</th>
                <th className="px-5 py-3.5">{t('common.amount')}</th>
                <th className="px-5 py-3.5">{t('common.status')}</th>
                <th className="px-5 py-3.5">{t('common.actions')}</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {orders.map((order) => (
                <tr key={order.id} className="transition hover:bg-slate-50/70">
                  <td className="px-5 py-4 font-semibold text-slate-900">{order.order_number}</td>
                  <td className="px-5 py-4 text-slate-600">{formatDate(order.created_at, locale)}</td>
                  <td className="px-5 py-4 font-bold text-slate-900">{formatPrice(order.total, locale)}</td>
                  <td className="px-5 py-4">
                    <span className={`badge ring-1 ${statusStyles[order.status] || statusStyles.pending}`}>
                      {order.status === 'pending'
                        ? t('checkout.pending')
                        : order.status === 'paid'
                          ? t('checkout.paid')
                          : order.status.toUpperCase()}
                    </span>
                  </td>
                  <td className="px-5 py-4">
                    <Link
                      to={`/checkout/${order.order_number}`}
                      className={`inline-flex rounded-xl px-3.5 py-1.5 text-xs font-bold transition ${
                        order.status === 'pending'
                          ? 'bg-indigo-600 text-white hover:bg-indigo-700'
                          : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                      }`}
                    >
                      {order.status === 'pending' ? t('common.payNow') : t('orders.view')}
                    </Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}