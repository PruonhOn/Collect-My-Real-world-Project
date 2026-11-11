import { useLanguage } from '../../context/LanguageContext'
import CrownIcon from './CrownIcon'

const SIZES = {
  xs: 'text-[9px] px-1.5 py-0.5 gap-0.5',
  sm: 'text-[10px] px-2 py-0.5 gap-1',
  md: 'text-xs px-3 py-1.5 gap-1.5',
}

/**
 * The "this book needs VIP" marker, previously written out four times across
 * the book card, home page, new arrivals and hero. Amber on amber-900 is left
 * alone by the dark-mode overrides in index.css, so it reads the same in both
 * themes without an extra variant.
 */
export default function VipBookBadge({ size = 'sm', className = '' }) {
  const { t } = useLanguage()
  const base = `inline-flex items-center rounded-md font-bold whitespace-nowrap ${SIZES[size] || SIZES.sm} ${className}`

  return (
    <span className={`${base} bg-gradient-to-br from-[#BF953F] via-[#FCF6BA] to-[#B38728] text-white shadow-[0_0_15px_rgba(212,175,55,0.3)] border border-amber-400/40 backdrop-blur-md transition-all duration-200 hover:shadow-[0_0_20px_rgba(212,175,55,0.5)] hover:-translate-y-0.5`}>
      <CrownIcon className={size === 'md' ? 'w-4 h-4' : 'w-3 h-3'} />
      {t('vipAccess')}
    </span>
  )
}
