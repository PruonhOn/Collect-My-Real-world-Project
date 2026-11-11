import { Link } from 'react-router-dom'
import { useLanguage } from '../../context/LanguageContext'
import { useAuth } from '../../context/AuthContext'
import { useMembership } from '../../hooks/useMembership'
import CrownIcon from './CrownIcon'
import VipBenefits from './VipBenefits'

/**
 * The upgrade prompt. It never blocks anything: no modal, no redirect, and it
 * renders nothing at all for someone who already holds a membership, because
 * advertising VIP to a VIP member is the fastest way to make the tier feel
 * cheap.
 *
 * `compact` is the inline variant used under a locked book; the default is the
 * full card for the profile page.
 */
export default function VipUpgradeCard({ compact = false, className = '' }) {
  const { t } = useLanguage()
  const { isAuthenticated } = useAuth()
  const { isVip } = useMembership()

  if (isVip) return null

  const target = isAuthenticated ? '/vip' : '/login'
  const actionLabel = isAuthenticated ? t('upgradeToVip') : t('login')

  if (compact) {
    return (
      <div
        className={`rounded-xl border border-amber-400/40 bg-gradient-to-br from-[#BF953F]/10 via-[#FCF6BA]/10 to-[#B38728]/10 px-4 py-3.5 text-center backdrop-blur-md shadow-[0_0_15px_rgba(212,175,55,0.15)] ${className}`}
      >
        <p className="flex items-center justify-center gap-1.5 text-xs font-medium text-amber-300">
          <CrownIcon className="w-3.5 h-3.5" />
          {t('vipBookNotice')}
        </p>
        <Link
          to={target}
          className="mt-2.5 inline-flex items-center justify-center px-4 py-2 bg-gradient-to-br from-[#BF953F] via-[#FCF6BA] to-[#B38728] text-white text-xs font-semibold rounded-lg border border-amber-400/40 shadow-[0_0_10px_rgba(212,175,55,0.2)] hover:shadow-[0_0_15px_rgba(212,175,55,0.4)] hover:from-[#d4a737] hover:to-[#c49a30] transition-all duration-200"
        >
          {actionLabel}
        </Link>
      </div>
    )
  }

  return (
    <div
      className={`relative overflow-hidden rounded-2xl border border-amber-400/30 bg-gradient-to-br from-[#BF953F]/10 via-[#FCF6BA]/10 to-[#B38728]/10 p-5 sm:p-6 backdrop-blur-md shadow-[0_0_20px_rgba(212,175,55,0.15)] ${className}`}
    >
      <div
        className="absolute -top-12 -right-12 w-40 h-40 bg-amber-400/10 rounded-full blur-2xl pointer-events-none"
        aria-hidden="true"
      />

      <div className="relative">
        <div className="flex items-center gap-2.5">
          <span className="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-gradient-to-br from-[#BF953F] via-[#FCF6BA] to-[#B38728] text-white shrink-0 shadow-[0_0_10px_rgba(212,175,55,0.3)] border border-amber-400/40">
            <CrownIcon className="w-4.5 h-4.5" />
          </span>
          <div className="min-w-0">
            <h3 className="text-sm font-bold text-gray-900 leading-tight">
              {t('unlockMoreLearning')}
            </h3>
            <p className="text-xs text-gray-500">{t('vipBenefits')}</p>
          </div>
        </div>

        <VipBenefits className="mt-4" />

        <Link
          to={target}
          className="mt-5 inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-br from-[#BF953F] via-[#FCF6BA] to-[#B38728] text-white text-sm font-semibold rounded-xl border border-amber-400/40 shadow-[0_0_15px_rgba(212,175,55,0.3)] hover:shadow-[0_0_25px_rgba(212,175,55,0.5)] hover:from-[#d4a737] hover:to-[#c49a30] hover:-translate-y-0.5 transition-all"
        >
          <CrownIcon className="w-4 h-4" />
          {actionLabel}
        </Link>
      </div>
    </div>
  )
}
