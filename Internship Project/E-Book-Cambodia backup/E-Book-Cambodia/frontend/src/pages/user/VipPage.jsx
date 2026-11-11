import { useMutation, useQuery } from '@tanstack/react-query'
import { getVipPlans, purchaseVip } from '../../api'
import { useAuth } from '../../context/AuthContext'
import { useLanguage } from '../../context/LanguageContext'
import { useState } from 'react'
import { Link } from 'react-router-dom'

export default function VipPage() {
  const { t } = useLanguage()
  const { user } = useAuth()
  const [purchasing, setPurchasing] = useState(null)
  const [purchaseResult, setPurchaseResult] = useState(null)

  const { data, isLoading } = useQuery({
    queryKey: ['vip-plans'],
    queryFn: () => getVipPlans(),
  })

  const plans = data?.data?.plans || []

  const purchaseMutation = useMutation({
    mutationFn: (planId) => purchaseVip(planId, { method: 'mock', mock_approve: true }),
    onSuccess: (result) => {
      setPurchasing(null)
      setPurchaseResult(result.data)
    },
    onError: (error) => {
      setPurchasing(null)
      alert(error.response?.data?.message || 'Purchase failed')
    },
  })

  const handlePurchase = (planId) => {
    setPurchasing(planId)
    purchaseMutation.mutate(planId)
  }

  const vipUntil = user?.vip_until
  const isVipActive = user?.is_vip

  return (
    <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      <div className="text-center mb-10">
        <span className="inline-block text-xs font-bold uppercase tracking-wider text-indigo-600 bg-indigo-50 px-3 py-1 rounded-full mb-3">
          {t('vipMembership')}
        </span>
        <h1 className="text-3xl md:text-4xl font-bold text-gray-900 mb-3">{t('becomeVip')}</h1>
        <p className="text-gray-600 max-w-xl mx-auto">{t('vipBenefits')}</p>

        {isVipActive && vipUntil ? (
          <div className="mt-6 inline-flex items-center gap-3 bg-emerald-50 text-emerald-700 rounded-2xl px-5 py-3">
            <span className="w-2 h-2 rounded-full bg-emerald-500" />
            <span className="font-semibold">
              {t('vipActive')} · {t('vipExpires')}: {new Date(vipUntil).toLocaleDateString()}
            </span>
          </div>
        ) : (
          <div className="mt-6 inline-flex items-center gap-2 text-sm text-gray-500">
            {t('currentPlan')}: <span className="text-gray-800 font-medium">No VIP</span>
          </div>
        )}
      </div>

      {isLoading ? (
        <div className="text-center py-10">{t('loading')}</div>
      ) : plans.length === 0 ? (
        <div className="text-center py-16 bg-white rounded-2xl shadow-sm">
          <p className="text-gray-500">{t('noPlans')}</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {plans.map((plan) => (
            <div
              key={plan.id}
              className="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 flex flex-col"
            >
              <div className="text-sm font-semibold text-indigo-600 uppercase tracking-wide">
                {plan.name}
              </div>
              <div className="mt-3 flex items-baseline gap-1">
                <span className="text-4xl font-bold text-gray-900">${plan.price}</span>
                <span className="text-sm text-gray-500">
                  / {plan.duration_days} {t('days')}
                </span>
              </div>
              {plan.description && <p className="mt-4 text-sm text-gray-600 flex-1">{plan.description}</p>}
              <button
                onClick={() => handlePurchase(plan.id)}
                disabled={purchasing === plan.id}
                className="mt-6 w-full px-6 py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 disabled:opacity-60"
              >
                {purchasing === plan.id ? t('loading') : t('joinVip')}
              </button>
            </div>
          ))}
        </div>
      )}

      {purchaseResult && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-2xl p-8 max-w-md w-full text-center">
            <h2 className="text-xl font-bold text-gray-900 mb-4">
              {purchaseResult.activated ? t('paymentSuccess') : t('completePayment')}
            </h2>
            <div className="bg-indigo-50 rounded-xl p-4 text-indigo-700 text-sm">
              {purchaseResult.message || t('demoNotice')}
            </div>
            <div className="mt-6 flex gap-3">
              <button
                onClick={() => setPurchaseResult(null)}
                className="flex-1 px-4 py-2.5 border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50"
              >
                {t('close')}
              </button>
              {purchaseResult.activated && (
                <Link
                  to="/my-library"
                  className="flex-1 px-4 py-2.5 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700"
                >
                  {t('myLibrary')}
                </Link>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  )
}