import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Link, useParams } from 'react-router-dom'
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

export default function Checkout() {
  const { orderNumber } = useParams()
  const { t, locale } = useI18n()
  const queryClient = useQueryClient()

  const { data, isLoading, isError } = useQuery({
    queryKey: ['order', orderNumber],
    queryFn: async () => {
      const res = await api.get(`/orders/${orderNumber}`)
      return res.data.data
    },
  })

  const pay = useMutation({
    mutationFn: () => api.post(`/orders/${orderNumber}/pay`, { payment_method: 'mock' }),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['order', orderNumber] })
      await queryClient.invalidateQueries({ queryKey: ['my-orders'] })
      await queryClient.invalidateQueries({ queryKey: ['my-books'] })
    },
  })

  if (isLoading) return <Loading full />
  if (isError) return <ErrorState />

  const order = data
  const isPaid = order.status === 'paid'

  return (
    <div className="page max-w-3xl">
      <Link
        to="/orders"
        className="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-indigo-600"
      >
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
          <path d="M19 12H5M12 19l-7-7 7-7" />
        </svg>
        {t('common.back')}
      </Link>

      <h1 className="mt-4 text-3xl font-extrabold tracking-tight text-slate-900">{t('common.checkout')}</h1>
      <p className="mt-1 text-sm text-slate-500">
        {t('common.orderNo')}:{' '}
        <span className="rounded-lg bg-slate-100 px-2 py-0.5 font-bold text-slate-800">
          {order.order_number}
        </span>
      </p>

      {isPaid && (
        <div className="relative mt-7 overflow-hidden rounded-3xl border border-emerald-200 bg-emerald-50 p-8 text-center">
          <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-500 text-3xl text-white">
            ✓
          </div>
          <h2 className="mt-4 text-xl font-extrabold text-emerald-800">{t('checkout.success')}</h2>
          <p className="mt-1 text-sm text-emerald-700">{t('checkout.successDesc')}</p>
          <div className="mt-6 flex flex-wrap justify-center gap-3">
            <Link to="/my-books" className="btn-primary">
              {t('checkout.goToMyBooks')}
            </Link>
            <Link to="/books" className="btn-secondary">
              {t('checkout.continueShopping')}
            </Link>
          </div>
        </div>
      )}

      <div className="card mt-7 p-6 sm:p-8">
        <h2 className="font-bold text-slate-900">{t('checkout.items')}</h2>
        <div className="mt-4 space-y-3">
          {(order.items ?? []).map((item) => (
            <div key={item.book_id} className="flex items-center justify-between gap-4 rounded-xl bg-slate-50 px-4 py-3 text-sm">
              <span className="font-semibold text-slate-900">{item.book_title}</span>
              <span className="text-slate-600">{formatPrice(item.price, locale)}</span>
            </div>
          ))}
        </div>

        <div className="mt-6 space-y-2.5 border-t border-slate-100 pt-5 text-sm">
          <div className="flex justify-between text-slate-600">
            <span>{t('checkout.subtotal')}</span>
            <span>{formatPrice(order.subtotal, locale)}</span>
          </div>
          <div className="flex justify-between text-slate-600">
            <span>{t('checkout.tax')}</span>
            <span>{formatPrice(order.tax, locale)}</span>
          </div>
          <div className="flex justify-between border-t border-dashed border-slate-200 pt-3 text-lg font-extrabold text-slate-900">
            <span>{t('checkout.total')}</span>
            <span>{formatPrice(order.total, locale)}</span>
          </div>
        </div>

        {!isPaid && (
          <>
            <button
              type="button"
              onClick={() => pay.mutate()}
              disabled={pay.isPending}
              className="btn-primary mt-7 w-full py-3.5"
            >
              {pay.isPending ? t('common.loading') : t('common.payNow')}
            </button>
            {pay.isError && (
              <p className="mt-3 text-center text-sm font-medium text-red-600">{t('common.error')}</p>
            )}
            <p className="mt-3 flex items-center justify-center gap-1.5 text-center text-xs text-slate-400">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" />
                <path d="M7 11V7a5 5 0 0110 0v4" />
              </svg>
              {t('checkout.mockNotice')}
            </p>
          </>
        )}
      </div>
    </div>
  )
}