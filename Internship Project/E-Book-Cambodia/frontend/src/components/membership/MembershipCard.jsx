import { Link } from 'react-router-dom'
import { useLanguage } from '../../context/LanguageContext'
import { useMembership } from '../../hooks/useMembership'
import MembershipBadge from './MembershipBadge'
import VipBenefits from './VipBenefits'
import VipUpgradeCard from './VipUpgradeCard'
import CrownIcon from './CrownIcon'

const formatDate = (date) => (date ? date.toLocaleDateString() : '')

/**
 * The membership panel on the profile page. Both tiers get the same card so
 * the difference is legible at a glance:
 *
 *   Member -> a clean white-and-blue card plus a short upgrade path
 *   VIP    -> a navy-and-gold panel with the membership dates and the
 *             benefits the backend actually grants
 *
 * Everything shown here comes from the `is_vip` / `vip_until` / `vip_plan`
 * fields the API returns on login and on /me.
 */
export default function MembershipCard() {
  const { t } = useLanguage()
  const { isVip, isTestVip, vipUntil, daysUntilExpiry } = useMembership()

  if (isVip) {
    return (
      <div className="rounded-2xl border border-amber-200/70 bg-white overflow-hidden shadow-sm">
        <div className="flex flex-wrap items-center justify-between gap-3 px-5 py-4 bg-gradient-to-br from-indigo-950 via-indigo-900 to-indigo-950">
          <div className="flex items-center gap-2.5 min-w-0">
            <span className="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-amber-400 text-indigo-950 shrink-0 ring-1 ring-amber-300/60">
              <CrownIcon className="w-4 h-4" />
            </span>
            <div className="min-w-0">
              <p className="text-xs font-medium uppercase tracking-wide text-amber-300/90">
                {t('membership')}
              </p>
              <p className="text-sm font-bold text-white leading-tight truncate">
                {t('vipMember')}
              </p>
            </div>
          </div>
          <MembershipBadge size="md" />
        </div>

        <div className="px-5 py-4 space-y-4">
          <div className="flex items-center gap-2 text-sm font-medium text-emerald-700">
            <span className="w-2 h-2 rounded-full bg-emerald-500 shrink-0" aria-hidden="true" />
            {t('vipActive')}
            {isTestVip && (
              <span className="text-[10px] font-bold uppercase tracking-wide bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded">
                {t('testOnly')}
              </span>
            )}
          </div>

          <dl className="grid sm:grid-cols-2 gap-3">
            {vipUntil && (
              <div className="rounded-xl bg-white border border-amber-200/70 px-3.5 py-3">
                <dt className="text-xs text-gray-500">{t('vipExpires')}</dt>
                <dd className="text-sm font-semibold text-gray-900 mt-0.5">
                  {formatDate(vipUntil)}
                  {daysUntilExpiry !== null && (
                    <span className="ml-1.5 text-xs font-normal text-gray-500">
                      ({daysUntilExpiry} {t('days')})
                    </span>
                  )}
                </dd>
              </div>
            )}
            <div className="rounded-xl bg-white border border-amber-200/70 px-3.5 py-3">
              <dt className="text-xs text-gray-500">{t('currentPlan')}</dt>
              <dd className="text-sm font-semibold text-gray-900 mt-0.5 flex items-center gap-1.5">
                <CrownIcon className="w-3.5 h-3.5 text-amber-500" />
                {isTestVip ? t('vipTestCard') : t('vip')}
              </dd>
            </div>
          </dl>

          <VipBenefits showTitle />

          <Link
            to="/vip"
            className="inline-flex items-center gap-1.5 text-sm font-medium text-amber-600 hover:text-amber-700"
          >
            {isTestVip ? t('vipLearnMore') : t('vipRenew')}
            <span aria-hidden="true">→</span>
          </Link>
        </div>
      </div>
    )
  }

  return (
    <div className="space-y-4">
      <div className="rounded-2xl border border-blue-200/70 bg-white overflow-hidden shadow-sm">
        <div className="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b border-blue-100 bg-gradient-to-br from-blue-50/70 to-white">
          <div className="min-w-0">
            <p className="text-xs font-medium uppercase tracking-wide text-blue-600/80">
              {t('membership')}
            </p>
            <p className="text-sm font-bold text-gray-900 leading-tight">{t('normalMember')}</p>
          </div>
          <MembershipBadge size="md" />
        </div>
        <div className="px-5 py-4">
          <p className="text-sm text-gray-600 leading-relaxed">{t('normalMemberDesc')}</p>
          <p className="mt-2 text-sm text-gray-600 leading-relaxed">{t('vipBenefits')}</p>
          <Link
            to="/vip"
            className="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-amber-600 hover:text-amber-700"
          >
            {t('vipLearnMore')}
            <span aria-hidden="true">→</span>
          </Link>
        </div>
      </div>

      <VipUpgradeCard />
    </div>
  )
}
