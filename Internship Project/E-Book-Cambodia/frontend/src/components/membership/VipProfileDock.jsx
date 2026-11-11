import { Link } from 'react-router-dom'
import { avatarUrl } from '../../api'
import { useAuth } from '../../context/AuthContext'
import { useLanguage } from '../../context/LanguageContext'
import { useMembership } from '../../hooks/useMembership'
import PremiumAvatar from './PremiumAvatar'
import MembershipBadge from './MembershipBadge'
import CrownIcon from './CrownIcon'

/**
 * Floating VIP profile card on the right edge of the books template.
 *
 * Only renders for an active membership, so the catalogue stays unchanged for
 * guests and normal members. It is decorative but links to the profile, and is
 * hidden below 2xl where it would cover the book grid.
 */
export default function VipProfileDock() {
  const { user } = useAuth()
  const { t } = useLanguage()
  const { isVip } = useMembership()

  if (!isVip || !user) return null

  return (
    <Link
      to="/profile"
      className="vip-profile-dock"
      aria-label={t('profile')}
      title={user.name}
    >
      <span className="vip-profile-dock__float">
        <span className="vip-profile-dock__halo" aria-hidden="true" />
        <span className="vip-profile-dock__card">
          <span className="vip-profile-dock__crown" aria-hidden="true">
            <CrownIcon className="w-4 h-4" />
          </span>
          <PremiumAvatar
            src={avatarUrl(user)}
            name={user.name}
            size="w-16 h-16 xl:w-20 xl:h-20"
            tone="soft"
            initialsClassName="text-xl xl:text-2xl font-bold"
            className="ring-4 ring-white/80"
          />
          <span className="vip-profile-dock__name">{user.name}</span>
          <MembershipBadge size="xs" />
        </span>
      </span>
    </Link>
  )
}
