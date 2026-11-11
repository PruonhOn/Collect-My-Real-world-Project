import { useLanguage } from '../../context/LanguageContext'
import { useMembership } from '../../hooks/useMembership'
import CrownIcon from './CrownIcon'

/**
 * Size scale shared by every membership chip. `xs` is for dense surfaces like
 * the book card and the navbar row; `md` is for the profile header.
 */
const SIZES = {
  xs: 'text-[9px] px-1.5 py-0.5 gap-0.5',
  sm: 'text-[10px] px-2 py-0.5 gap-1',
  md: 'text-xs px-2.5 py-1 gap-1.5',
}

/**
 * The membership tier chip: crown + VIP for an active member, a clear blue
 * "Normal Member" pill for everyone else, and the admin role pill for admins.
 *
 * Admin is deliberately a separate branch. `role` and membership tier are
 * different concepts, so an admin keeps the admin chip unless they actually
 * hold an active membership — nothing here grants VIP to anyone.
 */
export default function MembershipBadge({ size = 'sm', full = false, className = '' }) {
  const { t } = useLanguage()
  const { isVip, isAdmin } = useMembership()

  const base = `inline-flex items-center rounded-full font-bold whitespace-nowrap ${SIZES[size] || SIZES.sm} ${className}`

  if (isVip) {
    return (
      <span className={`${base} bg-gradient-to-br from-[#BF953F] via-[#FCF6BA] to-[#B38728] text-white shadow-[0_0_15px_rgba(212,175,55,0.3)] border border-amber-400/30 backdrop-blur-md transition-all duration-200 hover:shadow-[0_0_20px_rgba(212,175,55,0.5)] hover:scale-105`}>
        <CrownIcon className={size === 'md' ? 'w-3.5 h-3.5' : 'w-3 h-3'} />
        {full ? t('vipMember') : t('vip')}
      </span>
    )
  }

  if (isAdmin) {
    return (
      <span className={`${base} bg-indigo-100 text-indigo-700`}>
        <span className="w-1.5 h-1.5 rounded-full bg-indigo-500" aria-hidden="true" />
        {t('roleAdmin')}
      </span>
    )
  }

  return (
    <span className={`${base} bg-blue-50 text-blue-700 ring-1 ring-blue-200/70`}>
      <span className="w-1.5 h-1.5 rounded-full bg-blue-500" aria-hidden="true" />
      {t('normalMember')}
    </span>
  )
}
