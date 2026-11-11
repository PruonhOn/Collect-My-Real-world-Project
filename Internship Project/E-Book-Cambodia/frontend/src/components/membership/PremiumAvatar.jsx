import { useMembership } from '../../hooks/useMembership'

/**
 * A gold ring around an avatar for people who hold an active membership.
 *
 * Only a border and a faint glow: no animation, no shimmer. Normal members
 * get the project's existing flat avatar treatment — `tone` picks which of the
 * two treatments already in the app to use, so a profile page and a navbar
 * keep their current look — and the difference reads as a perk rather than a
 * downgrade for everyone else.
 */
export default function PremiumAvatar({
  src,
  name,
  size = 'w-8 h-8',
  tone = 'solid',
  initialsClassName = 'text-sm font-semibold',
  className = '',
}) {
  const { isVip } = useMembership()

  const initials = name?.charAt(0)?.toUpperCase() || '?'

  if (!isVip) {
    const toneClass =
      tone === 'soft' ? 'bg-indigo-100 text-indigo-700' : 'bg-indigo-600 text-white'

    if (src) {
      return <img src={src} alt={name} className={`${size} rounded-full object-cover ${className}`} />
    }

    return (
      <span
        className={`${size} rounded-full flex items-center justify-center ${toneClass} ${initialsClassName} ${className}`}
      >
        {initials}
      </span>
    )
  }

  // p-[2px] exposes the shimmering gradient as a hairline ring around the photo.
  const ring =
    'premium-avatar-ring p-[2px] shadow-[0_0_15px_rgba(212,175,55,0.3)]'

  if (src) {
    return (
      <span className={`${size} ${ring} rounded-full shrink-0 ${className}`}>
        <img
          src={src}
          alt={name}
          className="w-full h-full rounded-full object-cover ring-1 ring-amber-400/60"
        />
      </span>
    )
  }

  return (
    <span
      className={`${size} ${ring} rounded-full shrink-0 flex items-center justify-center ${className}`}
    >
      <span
        className={`w-full h-full rounded-full bg-[#FCF6BA]/20 text-[#B38728] flex items-center justify-center ${initialsClassName}`}
      >
        {initials}
      </span>
    </span>
  )
}
