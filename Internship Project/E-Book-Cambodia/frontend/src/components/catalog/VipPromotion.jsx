import { Link } from 'react-router-dom'
import { useLanguage } from '../../context/LanguageContext'
import { useMembership } from '../../hooks/useMembership'
import CrownIcon from '../membership/CrownIcon'
import VipBenefits from '../membership/VipBenefits'

// Shared by the home page and the books page, which use different container
// widths, so the caller decides.
export default function VipPromotion({
  containerClassName = 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8',
}) {
  const { t } = useLanguage()
  const { isVip } = useMembership()

  // Nothing to sell to someone who already holds the tier. Rendered as null
  // rather than unmounted by callers so HomePage stays unchanged.
  if (isVip) return null

  return (
    <section className={`${containerClassName} pt-14`}>
      <div className="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-950 via-indigo-900 to-indigo-950 text-white shadow-2xl shadow-[0_0_30px_rgba(212,175,55,0.25)] border border-amber-400/20">
        <div className="absolute -top-16 -right-16 w-64 h-64 bg-white/10 rounded-full blur-2xl pointer-events-none" aria-hidden="true" />
        <div className="absolute -bottom-20 -left-10 w-72 h-72 bg-amber-400/10 rounded-full blur-2xl pointer-events-none" aria-hidden="true" />

        <div className="relative p-6 sm:p-10 lg:p-12">
          <div className="flex flex-col lg:flex-row lg:items-center gap-8">
            <div className="max-w-xl">
              <span className="inline-flex items-center gap-2 text-xs font-bold bg-gradient-to-br from-[#BF953F] via-[#FCF6BA] to-[#B38728] text-white rounded-full px-3 py-1.5 shadow-[0_0_10px_rgba(212,175,55,0.3)]">
                <CrownIcon className="w-3.5 h-3.5" />
                VIP
              </span>
              <h2 className="mt-4 text-2xl sm:text-3xl font-bold leading-snug">{t('vipTitle')}</h2>
              <p className="mt-3 text-amber-100/90 leading-relaxed">{t('vipSub')}</p>
              <Link
                to="/vip"
                className="mt-6 inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-br from-[#BF953F] via-[#FCF6BA] to-[#B38728] text-white font-semibold rounded-xl shadow-[0_0_15px_rgba(212,175,55,0.3)] hover:shadow-[0_0_25px_rgba(212,175,55,0.5)] hover:from-[#d4a737] hover:to-[#c49a30] hover:-translate-y-0.5 transition-all"
              >
                {t('vipLearnMore')}
                <span aria-hidden="true">→</span>
              </Link>
            </div>

            <VipBenefits variant="tiles" className="flex-1" />
          </div>
        </div>
      </div>
    </section>
  )
}