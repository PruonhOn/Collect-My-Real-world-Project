import { Bell, Check, LogOut, Menu, Palette, Search, Settings, User } from 'lucide-react'
import { useEffect, useRef, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '@/features/auth/auth-context'
import { useTheme } from '@/features/theme/theme-context'
import { THEMES } from '@/lib/themes'
import { cn } from '@/lib/cn'

export function Topbar({
  onMenuClick,
  unreadNotifications,
}: {
  onMenuClick: () => void
  unreadNotifications: number
}) {
  const { user, logout, can } = useAuth()
  const navigate = useNavigate()
  const [menuOpen, setMenuOpen] = useState(false)
  const menuRef = useRef<HTMLDivElement>(null)

  // Dismiss the account menu on an outside click or Escape.
  useEffect(() => {
    if (!menuOpen) return

    function onPointerDown(event: MouseEvent) {
      if (menuRef.current && !menuRef.current.contains(event.target as Node)) {
        setMenuOpen(false)
      }
    }

    function onKeyDown(event: KeyboardEvent) {
      if (event.key === 'Escape') setMenuOpen(false)
    }

    document.addEventListener('mousedown', onPointerDown)
    document.addEventListener('keydown', onKeyDown)

    return () => {
      document.removeEventListener('mousedown', onPointerDown)
      document.removeEventListener('keydown', onKeyDown)
    }
  }, [menuOpen])

  async function handleLogout() {
    await logout()
    navigate('/login', { replace: true })
  }

  const initials = (user?.name ?? '?')
    .split(' ')
    .map((part) => part[0])
    .slice(0, 2)
    .join('')
    .toUpperCase()

  return (
    <header className="border-ink-200 bg-ink-50/80 sticky top-0 z-20 flex h-14 shrink-0 items-center gap-3 border-b px-4 backdrop-blur sm:px-6">
      <button
        type="button"
        onClick={onMenuClick}
        aria-label="Open navigation"
        className="text-ink-500 hover:bg-ink-100 hover:text-ink-900 -ml-1 flex size-9 items-center justify-center rounded-md transition-colors lg:hidden"
      >
        <Menu className="size-5" aria-hidden />
      </button>

      {/* Global search. Wired to the shipments index in a later phase; it is
          rendered now so the shell matches the final layout. */}
      <div className="relative hidden max-w-md flex-1 sm:block">
        <Search
          className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-ink-400"
          aria-hidden
        />
        <input
          type="search"
          placeholder="Search shipments, bookings, invoices..."
          aria-label="Global search"
          className="h-9 w-full rounded-md border border-ink-200 bg-white pr-3 pl-9 text-sm shadow-xs transition-colors placeholder:text-ink-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/25 focus:outline-none"
        />
      </div>

      <div className="ml-auto flex items-center gap-1.5">
        <ThemeSwitcher />

        {can('notification.view') && (
          <Link
            to="/admin/notifications"
            aria-label={`Notifications${unreadNotifications > 0 ? `, ${unreadNotifications} unread` : ''}`}
            className="text-ink-500 hover:bg-ink-100 hover:text-ink-900 relative flex size-9 items-center justify-center rounded-md transition-colors"
          >
            <Bell className="size-4.5" aria-hidden />
            {unreadNotifications > 0 && (
              <span className="bg-danger-600 tabular absolute top-1.5 right-1.5 min-w-4 rounded-full px-1 text-[10px] leading-4 font-semibold text-white">
                {unreadNotifications > 9 ? '9+' : unreadNotifications}
              </span>
            )}
          </Link>
        )}

        <div className="relative" ref={menuRef}>
          <button
            type="button"
            onClick={() => setMenuOpen((open) => !open)}
            aria-haspopup="menu"
            aria-expanded={menuOpen}
            className="hover:bg-ink-100 flex items-center gap-2.5 rounded-md py-1 pr-2 pl-1 transition-colors"
          >
            <span className="bg-brand-600 flex size-7.5 shrink-0 items-center justify-center rounded-full text-xs font-semibold text-white">
              {initials}
            </span>
            <span className="hidden text-left sm:block">
              <span className="block max-w-32 truncate text-sm font-medium text-ink-900">
                {user?.name}
              </span>
              <span className="block text-[11px] text-ink-500">
                {user?.customer?.company_name ?? user?.roles?.[0] ?? 'Staff'}
              </span>
            </span>
          </button>

          {menuOpen && (
            <div
              role="menu"
              className="absolute right-0 z-30 mt-2 w-64 overflow-hidden rounded-lg border border-ink-200 bg-white shadow-lg"
            >
              <div className="border-ink-200 border-b px-4 py-3">
                <p className="truncate text-sm font-medium text-ink-900">{user?.name}</p>
                <p className="truncate text-xs text-ink-500">{user?.email}</p>
                {user?.roles && user.roles.length > 0 && (
                  <div className="mt-2 flex flex-wrap gap-1">
                    {user.roles.map((role) => (
                      <span
                        key={role}
                        className="bg-ink-100 text-ink-600 rounded px-1.5 py-0.5 text-[10px] font-medium"
                      >
                        {role}
                      </span>
                    ))}
                  </div>
                )}
              </div>

              <div className="p-1">
                <MenuLink to="/profile" icon={User} onClick={() => setMenuOpen(false)}>
                  My profile
                </MenuLink>

                {can('setting.view') && (
                  <MenuLink to="/admin/settings" icon={Settings} onClick={() => setMenuOpen(false)}>
                    System settings
                  </MenuLink>
                )}

                <button
                  type="button"
                  role="menuitem"
                  onClick={handleLogout}
                  className="text-danger-600 hover:bg-danger-50 flex w-full items-center gap-2.5 rounded-md px-3 py-2 text-sm transition-colors"
                >
                  <LogOut className="size-4" aria-hidden />
                  Sign out
                </button>
              </div>
            </div>
          )}
        </div>
      </div>
    </header>
  )
}

function MenuLink({
  to,
  icon: Icon,
  onClick,
  children,
}: {
  to: string
  icon: typeof User
  onClick: () => void
  children: React.ReactNode
}) {
  return (
    <Link
      to={to}
      role="menuitem"
      onClick={onClick}
      className={cn(
        'text-ink-700 hover:bg-ink-100 flex w-full items-center gap-2.5 rounded-md px-3 py-2 text-sm transition-colors',
      )}
    >
      <Icon className="size-4" aria-hidden />
      {children}
    </Link>
  )
}

/**
 * Quick theme switch in the top bar.
 *
 * Deliberately not a cycling button: which template a click would land on is
 * unreadable, and a three-item list is short enough to show in full. Each row
 * previews its own accent, since the selected one cannot be described by a
 * colour utility that already reflects the current theme.
 */
function ThemeSwitcher() {
  const { theme, setTheme } = useTheme()
  const [open, setOpen] = useState(false)
  const containerRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    if (!open) return

    function onPointerDown(event: MouseEvent) {
      if (containerRef.current && !containerRef.current.contains(event.target as Node)) {
        setOpen(false)
      }
    }

    function onKeyDown(event: KeyboardEvent) {
      if (event.key === 'Escape') setOpen(false)
    }

    document.addEventListener('mousedown', onPointerDown)
    document.addEventListener('keydown', onKeyDown)

    return () => {
      document.removeEventListener('mousedown', onPointerDown)
      document.removeEventListener('keydown', onKeyDown)
    }
  }, [open])

  return (
    <div className="relative" ref={containerRef}>
      <button
        type="button"
        onClick={() => setOpen((value) => !value)}
        aria-haspopup="menu"
        aria-expanded={open}
        aria-label="Change theme template"
        className="text-ink-500 hover:bg-ink-100 hover:text-ink-900 flex size-9 items-center justify-center rounded-md transition-colors"
      >
        <Palette className="size-4.5" aria-hidden />
      </button>

      {open && (
        <div
          role="menu"
          className="absolute right-0 z-30 mt-2 w-56 overflow-hidden rounded-lg border border-ink-200 bg-white shadow-lg"
        >
          <p className="text-ink-500 border-ink-200 border-b px-3 py-2 text-xs font-medium">
            Theme template
          </p>

          <div className="p-1">
            {THEMES.map((option) => (
              <button
                key={option.id}
                type="button"
                role="menuitemradio"
                aria-checked={option.id === theme}
                onClick={() => {
                  setTheme(option.id)
                  setOpen(false)
                }}
                className={cn(
                  'flex w-full items-center gap-2.5 rounded-md px-2.5 py-2 text-left text-sm transition-colors',
                  option.id === theme
                    ? 'text-ink-900 bg-ink-100'
                    : 'text-ink-700 hover:bg-ink-100',
                )}
              >
                <span
                  className="size-4 shrink-0 rounded-full ring-1 ring-ink-300/60 ring-inset"
                  style={{ backgroundColor: option.swatch }}
                  aria-hidden
                />
                <span className="flex-1 truncate">{option.label}</span>
                {option.id === theme && <Check className="text-brand-600 size-4 shrink-0" aria-hidden />}
              </button>
            ))}
          </div>
        </div>
      )}
    </div>
  )
}
