import { useEffect, useState } from 'react'
import { useLanguage } from '../../context/LanguageContext'
import { useMembership } from '../../hooks/useMembership'

const MS_PER_HOUR = 60 * 60 * 1000
const MS_PER_DAY = 24 * MS_PER_HOUR

/**
 * One quiet line showing when a VIP membership runs out ("VIP expires:
 * 5 Jan 2027 (83 days)"). Renders nothing for members and guests, so the
 * navbar and mobile menu can place it unconditionally.
 *
 * A test-card membership (a few hours long) is under a day by definition, so
 * it switches to an hours/minutes countdown instead of a date.
 */
export default function VipExpiryNote({ className = '' }) {
  const { t } = useLanguage()
  const { isVip, vipUntil, daysUntilExpiry } = useMembership()
  const [now, setNow] = useState(() => Date.now())

  useEffect(() => {
    if (!isVip) return undefined
    const id = setInterval(() => setNow(Date.now()), 60000)
    return () => clearInterval(id)
  }, [isVip])

  if (!isVip || !vipUntil) return null

  const remaining = vipUntil.getTime() - now
  const underOneDay = remaining < MS_PER_DAY

  if (underOneDay) {
    const hours = Math.max(0, Math.floor(remaining / MS_PER_HOUR))
    const minutes = Math.max(0, Math.floor((remaining % MS_PER_HOUR) / 60000))
    return (
      <span className={`bg-white/10 backdrop-blur-md border border-amber-400/30 rounded-full px-2 py-0.5 text-white shadow-[0_0_10px_rgba(212,175,55,0.2)] ${className}`}>
        {t('expiresIn')}: {hours}h {String(minutes).padStart(2, '0')}m
      </span>
    )
  }

  return (
    <span className={`bg-white/10 backdrop-blur-md border border-amber-400/30 rounded-full px-2 py-0.5 text-white shadow-[0_0_10px_rgba(212,175,55,0.2)] ${className}`}>
      {t('vipExpires')}: {vipUntil.toLocaleDateString()}
      {daysUntilExpiry !== null && ` (${daysUntilExpiry} ${t('days')})`}
    </span>
  )
}
