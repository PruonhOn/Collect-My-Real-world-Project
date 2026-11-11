/**
 * Development-only VIP card the backend sells in test mode. Mirrors
 * VipService::TEST_PLAN_SLUG; a normal membership is always the yearly plan.
 */
export const VIP_TEST_PLAN_SLUG = 'vip-test-5h'

/**
 * The VIP benefits the backend actually enforces, in presentation order.
 *
 * AccessService::canRead() is what grants them, so every entry here maps to a
 * real rule: an active membership reads VIP-only books (which no purchase or
 * sharing key unlocks) and reads paid, non-VIP-only books without buying them,
 * and LibraryController lists that catalogue under the "vip" source. Nothing
 * goes in this list unless a backend rule backs it — the VIP tier is only
 * worth advertising if the API keeps the promise.
 */
export const VIP_BENEFIT_KEYS = [
  'vipBenefitAccess',
  'vipBenefitReading',
  'vipBenefitAnytime',
  'vipBenefitContent',
]

const MS_PER_DAY = 24 * 60 * 60 * 1000

/**
 * Read the membership tier out of the authenticated user the API returned.
 *
 * `is_vip` is the backend's own answer (User::$appends), taken from the login,
 * register, /me and profile-update payloads. It is deliberately the only
 * accepted signal: a missing or falsey flag means "not a member", so client
 * state can never talk the UI into showing VIP content the API would refuse.
 */
export function deriveMembership(user) {
  const isVip = user?.is_vip === true
  const vipUntil = isVip && user?.vip_until ? new Date(user.vip_until) : null
  const expiredVipUntil =
    !isVip && user?.vip_until && new Date(user.vip_until) < new Date()
      ? new Date(user.vip_until)
      : null

  return {
    isAuthenticated: !!user,
    isAdmin: user?.role === 'admin',
    isVip,
    isTestVip: isVip && user?.vip_plan === VIP_TEST_PLAN_SLUG,
    vipPlan: isVip ? user?.vip_plan || null : null,
    vipUntil,
    daysUntilExpiry: vipUntil
      ? Math.max(0, Math.ceil((vipUntil.getTime() - Date.now()) / MS_PER_DAY))
      : null,
    hasExpiredVip: !!expiredVipUntil,
    expiredVipUntil,
    tier: isVip ? 'vip' : 'member',
  }
}

/**
 * Whether a book needs a membership to open, judged the same way the API
 * does. Used for the "VIP-only" label only — the reader still asks
 * /books/{id}/access before serving a page.
 */
export function isVipOnlyBook(book) {
  return !!book?.is_vip_only
}

/**
 * UI mirror of AccessService::canRead() for catalogue surfaces (book cards,
 * grids, detail chips). Returns a hint only:
 *
 *   - `state`  – which label the UI should draw ('free' | 'owned' | 'vip' |
 *                'vip_only' | 'premium')
 *   - `canRead`– whether this viewer *appears* able to open the book
 *   - `needsVip` – whether the VIP upgrade is the right ask
 *
 * The Laravel API stays the source of truth: /reader/{book} re-checks every
 * request and answers 403 + `vip_required`, so a stale or tampered client
 * can never open a protected file. Never use this to unlock content — only
 * to draw badges and pick the friendly call to action.
 */
export function getBookAccess(book, { isVip = false } = {}) {
  if (!book) return { state: 'unknown', canRead: false, needsVip: false }

  if (book.is_vip_only) {
    return isVip
      ? { state: 'vip', canRead: true, needsVip: false }
      : { state: 'vip_only', canRead: false, needsVip: true }
  }
  if (book.is_free) return { state: 'free', canRead: true, needsVip: false }
  if (book.is_purchased) return { state: 'owned', canRead: true, needsVip: false }
  if (isVip) return { state: 'vip', canRead: true, needsVip: false }
  return { state: 'premium', canRead: false, needsVip: false }
}

/** Formats a VIP expiry date for compact chips (navbar, detail headers). */
export function formatVipExpiry(vipUntil) {
  return vipUntil ? vipUntil.toLocaleDateString() : ''
}
