import { useCallback, useState } from 'react'
import { useMutation, useQuery } from '@tanstack/react-query'
import { Link, useNavigate } from 'react-router-dom'
import { getMe, getVipPlans, purchaseVip } from '../../api'
import BakongPaymentModal from '../../components/payment/BakongPaymentModal'
import CrownIcon from '../../components/membership/CrownIcon'
import VipDashboard from './VipDashboard'
import { useAuth } from '../../context/AuthContext'
import { useLanguage } from '../../context/LanguageContext'
import { useMembership } from '../../hooks/useMembership'

export default function VipPage() {
  const { t } = useLanguage()
  const { setUser, isAuthenticated } = useAuth()
  const { isVip, hasExpiredVip, expiredVipUntil } = useMembership()
  const navigate = useNavigate()
  const [purchasing, setPurchasing] = useState(null)
  const [purchaseResult, setPurchaseResult] = useState(null)

  const { data, isLoading } = useQuery({
    queryKey: ['vip-plans'],
    refetchInterval: 60000,
    queryFn: () => getVipPlans(),
  })

  const plans = data?.data?.plans || []

  const refreshUser = useCallback(async () => {
    try {
      const res = await getMe()
      setUser(res.data.user)
    } catch {
      // ignore
    }
  }, [setUser])

  const purchaseMutation = useMutation({
    mutationFn: ({ planId, method }) => purchaseVip(planId, { method, mock_approve: true }),
    onSuccess: (result) => {
      setPurchasing(null)
      setPurchaseResult(result.data)
    },
    onError: (error) => {
      setPurchasing(null)
      alert(error.response?.data?.message || t('purchaseFailed'))
    },
  })

  const handlePurchase = (plan) => {
    if (!isAuthenticated) {
      navigate('/login')
      return
    }
    setPurchasing(plan.id)
    purchaseMutation.mutate({ planId: plan.id, method: plan.is_test ? 'bakong' : 'mock' })
  }

  const handleBakongPaid = useCallback(() => {
    refreshUser()
    setPurchaseResult((prev) => (prev ? { ...prev, activated: true, status: 'completed' } : prev))
  }, [refreshUser])

  const plansGrid = isLoading ? (
    <div className="text-center py-10">{t('loading')}</div>
  ) : plans.length === 0 ? (
    <div className="text-center py-16 bg-white rounded-2xl shadow-sm">
      <p className="text-gray-500">{t('noPlans')}</p>
    </div>
  ) : (
    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
      {plans.map((plan) => (
        plan.is_test ? (
          <div
            key={plan.id}
            className="bg-amber-50 rounded-2xl border border-amber-200 p-8 flex flex-col"
          >
            <div className="flex items-center gap-2">
              <span className="text-sm font-bold text-amber-700 uppercase tracking-wide">
                {t('vipTestCard')}
              </span>
              <span className="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-[10px] font-bold">
                {t('testOnly')}
              </span>
            </div>
            <div className="mt-3 flex items-baseline gap-1">
              <span className="text-4xl font-bold text-gray-900">${plan.price}</span>
              <span className="text-sm text-gray-500">USD</span>
            </div>
            <div className="mt-1 text-sm font-semibold text-amber-700">
              {plan.duration_hours} {t('hours')}
            </div>
            <p className="mt-2 text-xs text-amber-700/80 flex-1">
              {t('bakongTestNotice')}
            </p>
            <button
              onClick={() => handlePurchase(plan)}
              disabled={purchasing === plan.id}
              className="mt-6 w-full px-6 py-3 bg-amber-500 text-white font-semibold rounded-xl hover:bg-amber-600 disabled:opacity-60"
            >
              {purchasing === plan.id ? t('loading') : t('payWithKhqr')}
            </button>
          </div>
        ) : (
          <div
            key={plan.id}
            className="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 flex flex-col"
          >
            <div className="text-sm font-semibold text-indigo-600 uppercase tracking-wide">
              {plan.name}
            </div>
            <div className="mt-3 flex items-baseline gap-1">
              <span className="text-4xl font-bold text-gray-900">${plan.price}</span>
              <span className="text-sm text-gray-500">/ {t('year')}</span>
            </div>
            {plan.description && <p className="mt-4 text-sm text-gray-600 flex-1">{plan.description}</p>}
            <button
              onClick={() => handlePurchase(plan)}
              disabled={purchasing === plan.id}
              className="mt-6 w-full px-6 py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 disabled:opacity-60"
            >
              {purchasing === plan.id ? t('loading') : isVip ? t('vipRenew') : t('joinVip')}
            </button>
          </div>
        )
      ))}
    </div>
  )

  return (
    <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      {isVip ? (
        /* Active VIP: the dashboard is the page; plans remain below for renewal. */
        <>
          <VipDashboard />
          <section id="renew" className="scroll-mt-28 mt-12">
            <h2 className="flex items-center gap-2 text-xl font-bold text-gray-900 mb-6">
              <CrownIcon className="w-5 h-5 text-amber-500" />
              {t('renewMembership')}
            </h2>
            {plansGrid}
          </section>
        </>
      ) : (
        <>
          <div className="text-center mb-10">
            <span className="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-indigo-600 bg-indigo-50 px-3 py-1 rounded-full mb-3">
              {t('vipMembership')}
            </span>
            <h1 className="text-3xl md:text-4xl font-bold text-gray-900 mb-3">{t('becomeVip')}</h1>
            <p className="text-gray-600 max-w-xl mx-auto">{t('vipBenefits')}</p>

            {isAuthenticated && hasExpiredVip ? (
              <div className="mt-6 inline-flex items-center gap-2.5 bg-amber-50 text-amber-800 border border-amber-200 rounded-2xl px-5 py-3 text-sm font-medium">
                <CrownIcon className="w-4 h-4" />
                {t('vipExpiredNotice')}
                {expiredVipUntil && (
                  <span className="text-amber-600/80 font-normal">
                    ({expiredVipUntil.toLocaleDateString()})
                  </span>
                )}
              </div>
            ) : isAuthenticated ? (
              <div className="mt-6 inline-flex items-center gap-2 text-sm text-gray-500">
                {t('currentPlan')}: <span className="text-gray-800 font-medium">{t('noVipPlan')}</span>
              </div>
            ) : (
              <div className="mt-6 flex flex-wrap items-center justify-center gap-3 text-sm">
                <Link
                  to="/login"
                  className="px-5 py-2.5 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition-colors"
                >
                  {t('login')}
                </Link>
                <Link
                  to="/register"
                  className="px-5 py-2.5 border border-gray-200 text-gray-700 font-medium rounded-xl hover:bg-gray-50 transition-colors"
                >
                  {t('register')}
                </Link>
              </div>
            )}
          </div>
          {plansGrid}
        </>
      )}

      {purchaseResult?.bakong?.qr ? (
        <BakongPaymentModal
          result={purchaseResult}
          onPaid={handleBakongPaid}
          onClosed={() => setPurchaseResult(null)}
        />
      ) : purchaseResult && (
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