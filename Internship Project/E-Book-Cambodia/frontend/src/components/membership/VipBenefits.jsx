import { useLanguage } from '../../context/LanguageContext'
import { VIP_BENEFIT_KEYS } from '../../utils/membership'
import CrownIcon from './CrownIcon'

/**
 * The VIP benefit list, in one place so the upgrade card and the marketing
 * banner cannot drift apart. The copy comes from VIP_BENEFIT_KEYS, which only
 * names benefits the backend enforces — see that file for the mapping.
 *
 * `tiles` sits on a dark gradient (the homepage banner); `list` is the plain
 * checkmark list for light cards.
 */
export default function VipBenefits({ variant = 'list', showTitle = false, className = '' }) {
  const { t } = useLanguage()

  if (variant === 'tiles') {
    return (
      <div className={`grid grid-cols-1 sm:grid-cols-2 gap-3 ${className}`}>
        {VIP_BENEFIT_KEYS.map((key) => (
          <div
            key={key}
            className="flex items-center gap-3 bg-white/10 backdrop-blur-md rounded-2xl px-4 py-3.5 border border-amber-400/30 shadow-[0_0_10px_rgba(212,175,55,0.15)]"
          >
            <span className="flex items-center justify-center w-9 h-9 rounded-xl bg-gradient-to-br from-[#BF953F]/20 via-[#FCF6BA]/20 to-[#B38728]/20 shrink-0 border border-amber-400/40">
              <CrownIcon className="w-4 h-4 text-[#fbbf24]" />
            </span>
            <span className="text-sm font-medium leading-snug">{t(key)}</span>
          </div>
        ))}
      </div>
    )
  }

  return (
    <div className={className}>
      {showTitle && (
        <h3 className="flex items-center gap-2 text-sm font-bold text-gray-900 mb-3">
          <CrownIcon className="w-4 h-4 text-amber-500" />
          {t('vipBenefitsTitle')}
        </h3>
      )}
      <ul className="space-y-2">
        {VIP_BENEFIT_KEYS.map((key) => (
          <li key={key} className="flex items-start gap-2.5 text-sm text-gray-600">
            <svg
              className="w-4 h-4 text-amber-500 shrink-0 mt-0.5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              aria-hidden="true"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth="2.5"
                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
            {t(key)}
          </li>
        ))}
      </ul>
    </div>
  )
}
