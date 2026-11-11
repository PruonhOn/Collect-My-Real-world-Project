import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useNavigate } from 'react-router-dom'
import api from '../api/client'
import Loading from '../components/common/Loading'
import ErrorState from '../components/common/ErrorState'
import { useAuth } from '../context/AuthContext'
import { useI18n } from '../i18n'

function formatPrice(price, locale) {
  return new Intl.NumberFormat(locale === 'km' ? 'en-US' : locale, {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: price % 1 === 0 ? 0 : 2,
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

export default function Pricing() {
  const { t, locale } = useI18n()
  const { isAuthenticated, refreshUser } = useAuth()
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  const { data, isLoading, isError } = useQuery({
    queryKey: ['vip-plans', locale],
    queryFn: async () => {
      const res = await api.get('/vip/plans')
      return res.data.data
    },
  })

  const { data: status } = useQuery({
    queryKey: ['vip-status'],
    enabled: isAuthenticated,
    queryFn: async () => {
      const res = await api.get('/vip/status')
      return res.data.data
    },
  })

  const subscribe = useMutation({
    mutationFn: (planId) => api.post('/vip/subscribe', { plan_id: planId }),
    onSuccess: async () => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: ['vip-status'] }),
        refreshUser(),
      ])
    },
  })

  if (isLoading) return <Loading full />
  if (isError) return <ErrorState />

  const plans = data?.plans ?? []
  const isVip = Boolean(status?.is_vip)

  const handleSubscribe = (planId) => {
    if (!isAuthenticated) {
      navigate('/login')
      return
    }
    subscribe.mutate(planId)
  }

  return (
    <div className="page">
      <div className="mx-auto max-w-2xl text-center">
        <p className="section-eyebrow">VIP Membership</p>
        <h1 className="section-title">{t('pricing.title')}</h1>
        <p className="section-subtitle">{t('pricing.subtitle')}</p>
      </div>

      {isAuthenticated && (
        <div
          className={`mx-auto mt-8 flex max-w-xl items-center justify-center gap-2 rounded-2xl border px-5 py-3.5 text-sm font-semibold ${
            isVip
              ? 'border-indigo-200 bg-indigo-50 text-indigo-800'
              : 'border-slate-200 bg-slate-50 text-slate-600'
          }`}
        >
          <span
            className={`flex h-8 w-8 items-center justify-center rounded-full text-sm ${
              isVip ? 'bg-indigo-600 text-white' : 'bg-slate-300 text-white'
            }`}
          >
            {isVip ? '★' : '·'}
          </span>
          {isVip ? (
            <span>
              {t('pricing.vipActive')} · {t('pricing.vipUntil')}{' '}
              <span className="font-extrabold">{formatDate(status?.vip_until, locale)}</span>
            </span>
          ) : (
            t('pricing.notVip')
          )}
        </div>
      )}

      {subscribe.isSuccess && (
        <div className="mx-auto mt-6 max-w-xl rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-3.5 text-center text-sm font-semibold text-emerald-800">
          ✓ {t('pricing.subscribed')}
        </div>
      )}
      {subscribe.isError && (
        <div className="mx-auto mt-6 max-w-xl rounded-2xl border border-red-200 bg-red-50 px-5 py-3.5 text-center text-sm font-semibold text-red-700">
          {t('common.error')}
        </div>
      )}

      <div className="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        {plans.map((plan) => {
          const popular = plan.duration_days === 90
          return (
            <div
              key={plan.id}
              className={`relative flex flex-col rounded-3xl border bg-white p-7 transition duration-200 ${
                popular
                  ? 'border-indigo-400 shadow-2xl shadow-indigo-500/15 ring-2 ring-indigo-500'
                  : 'card card-hover'
              }`}
            >
              {popular && (
                <span className="absolute -top-3.5 left-1/2 -translate-x-1/2 rounded-full bg-gradient-to-r from-indigo-500 to-violet-600 px-4 py-1.5 text-xs font-bold text-white shadow-lg shadow-indigo-500/30">
                  ★ {t('pricing.mostPopular')}
                </span>
              )}
              <h3 className="text-lg font-extrabold text-slate-900">{plan.name}</h3>
              <p className="mt-1 text-sm text-slate-500">{plan.description}</p>
              <div className="mt-5 flex items-baseline gap-1.5">
                <span className={`text-4xl font-extrabold tracking-tight ${popular ? 'text-indigo-600' : 'text-slate-900'}`}>
                  {formatPrice(plan.price, locale)}
                </span>
                <span className="text-sm text-slate-500">/ {plan.duration_days} {t('pricing.days')}</span>
              </div>
              <ul className="mt-6 flex-1 space-y-3 text-sm text-slate-600">
                {(plan.features ?? []).map((feature, i) => (
                  <li key={i} className="flex items-start gap-2.5">
                    <span className="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-600">
                      ✓
                    </span>
                    {feature}
                  </li>
                ))}
              </ul>
              <button
                type="button"
                onClick={() => handleSubscribe(plan.id)}
                disabled={subscribe.isPending}
                className={`mt-7 w-full py-3.5 ${
                  popular
                    ? 'btn-primary'
                    : 'btn-secondary'
                }`}
              >
                {isVip ? t('pricing.subscribe') : t('pricing.subscribeFor', { price: formatPrice(plan.price, locale) })}
              </button>
            </div>
          )
        })}
      </div>

      {!isAuthenticated && (
        <p className="mt-10 text-center text-sm font-medium text-slate-500">🔑 {t('pricing.needLogin')}</p>
      )}
    </div>
  )
}