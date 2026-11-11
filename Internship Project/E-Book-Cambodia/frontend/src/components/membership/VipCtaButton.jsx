import { Link } from 'react-router-dom'
import { useLanguage } from '../../context/LanguageContext'
import { useMembership } from '../../hooks/useMembership'
import CrownIcon from './CrownIcon'
import VipExpiryNote from './VipExpiryNote'

/**
 * The membership-aware "Upgrade to VIP" call to action.
 *
 * Reads the same `/me` fields as every other membership surface, so it stays
 * a pure presentation of server state:
 *
 *   guest / normal member -> navy-and-gold "Upgrade to VIP" button -> /vip
 *   expired VIP           -> the same button, labelled "Renew VIP"
 *   active VIP            -> a gold "VIP Member" badge with the expiry note
 *
 * It never decides access — the backend re-validates on every read and every
 * payment. Prices and plans live on the VIP page, fetched from the API.
 */
export default function VipCtaButton({ size = 'sm', className = '' }) {
  const { t } = useLanguage()
  const { isVip, hasExpiredVip } = useMembership()

  if (isVip) {
    return (
      <Link
        to="/vip"
        title={t('vipMember')}
        className={`inline-flex items-center gap-1.5 rounded-full border border-amber-400/40 bg-gradient-to-br from-[#BF953F] via-[#FCF6BA] to-[#B38728] text-white font-semibold shadow-[0_0_15px_rgba(212,175,55,0.3)] backdrop-blur-md transition-all duration-200 hover:shadow-[0_0_25px_rgba(212,175,55,0.5)] hover:from-[#d4a737] hover:via-[#fdf3d4] hover:to-[#c49a30] hover:-translate-y-0.5 active:scale-95 ${
          size === 'sm' ? 'h-9 px-3 text-xs' : 'h-11 px-4 text-sm'
        } ${className}`}
      >
        <CrownIcon className={size === 'sm' ? 'w-3.5 h-3.5' : 'w-4 h-4'} />
        <span className="whitespace-nowrap">{t('vipMember')}</span>
        <VipExpiryNote className="hidden xl:inline text-[10px] font-normal text-[#FCF6BA]/90 whitespace-nowrap" />
      </Link>
    )
  }

  return (
    <Link
      to="/vip"
      className={`group inline-flex items-center gap-1.5 rounded-xl border border-amber-400/50 bg-gradient-to-br from-indigo-950 via-indigo-800 to-indigo-950 text-amber-300 font-semibold shadow-[0_0_15px_rgba(212,175,55,0.2)] backdrop-blur-md transition-all duration-200 hover:shadow-[0_0_25px_rgba(212,175,55,0.4)] hover:from-indigo-900 hover:via-indigo-700 hover:to-indigo-900 hover:border-amber-400/70 hover:-translate-y-0.5 active:scale-95 ${
        size === 'sm' ? 'h-9 px-2.5 sm:px-3 text-xs' : 'h-11 px-4 text-sm'
      } ${className}`}
    >
      <CrownIcon
        className={`${size === 'sm' ? 'w-3.5 h-3.5' : 'w-4 h-4'} transition-transform duration-200 group-hover:scale-120`}
      />
      <span className="whitespace-nowrap">
        {hasExpiredVip ? t('vipRenew') : t('upgradeToVip')}
      </span>
    </Link>
  )
}
