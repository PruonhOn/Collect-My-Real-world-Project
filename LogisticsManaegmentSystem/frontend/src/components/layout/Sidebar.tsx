import { PanelLeftClose, PanelLeftOpen, X } from 'lucide-react'
import { NavLink } from 'react-router-dom'
import { NAVIGATION, type NavItem } from '@/app/navigation'
import { useAuth } from '@/features/auth/auth-context'
import { cn } from '@/lib/cn'

export function Sidebar({
  collapsed,
  onToggle,
  mobileOpen,
  onMobileClose,
  badgeCounts,
}: {
  collapsed: boolean
  onToggle: () => void
  mobileOpen: boolean
  onMobileClose: () => void
  badgeCounts: Record<string, number>
}) {
  const { can } = useAuth()

  // A filter or a role change can leave nothing visible; fall back to the
  // dashboard rather than showing an empty rail.
  const visibleSections = NAVIGATION.map((section) => ({
    ...section,
    items: section.items.filter((item) => !item.permission || can(item.permission)),
  })).filter((section) => section.items.length > 0)

  return (
    <>
      {/* Scrim behind the drawer on small screens */}
      {mobileOpen && (
        <div
          className="fixed inset-0 z-30 bg-ink-950/50 lg:hidden"
          onClick={onMobileClose}
          aria-hidden
        />
      )}

      <aside
        className={cn(
          'bg-sidebar text-ink-300 fixed inset-y-0 left-0 z-40 flex flex-col transition-all duration-200 ease-out',
          'lg:translate-x-0',
          mobileOpen ? 'translate-x-0' : '-translate-x-full',
          collapsed ? 'lg:w-16' : 'lg:w-64',
          'w-64',
        )}
        aria-label="Main navigation"
      >
        <div className="flex h-14 shrink-0 items-center justify-between border-b border-white/10 px-4">
          <div className="flex min-w-0 items-center gap-2.5">
            <div className="bg-brand-500 flex size-7 shrink-0 items-center justify-center rounded-md">
              <svg viewBox="0 0 24 24" className="size-4 text-white" fill="currentColor" aria-hidden>
                <path d="M3 18h18l-2 3H5l-2-3Zm2.2-2 2.3-5.4A2 2 0 0 1 9.3 9.5h5.4a2 2 0 0 1 1.8 1.1l2.3 5.4H5.2Zm3.3-1.6h6l-1.1-2.6H8.6l-1.1 2.6ZM12 3a2.4 2.4 0 1 1 0 4.8A2.4 2.4 0 0 1 12 3Z" />
              </svg>
            </div>
            {!collapsed && (
              <div className="min-w-0">
                <p className="truncate text-sm font-semibold text-white">Logistics ERP</p>
                <p className="truncate text-[10px] text-ink-400">Freight Forwarding</p>
              </div>
            )}
          </div>

          <button
            type="button"
            onClick={onMobileClose}
            aria-label="Close navigation"
            className="text-ink-400 hover:text-white lg:hidden"
          >
            <X className="size-5" aria-hidden />
          </button>
        </div>

        <nav className="scrollbar-thin flex-1 overflow-y-auto px-2 py-3">
          {visibleSections.map((section) => (
            <div key={section.label} className="mb-4 last:mb-0">
              {!collapsed && (
                <p className="px-2.5 pb-1.5 text-[10px] font-semibold tracking-wider text-ink-500 uppercase">
                  {section.label}
                </p>
              )}

              <ul className="space-y-0.5">
                {section.items.map((item) => (
                  <li key={item.to}>
                    <SidebarLink
                      item={item}
                      collapsed={collapsed}
                      badge={item.badgeKey ? badgeCounts[item.badgeKey] : undefined}
                    />
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </nav>

        <div className="hidden shrink-0 border-t border-white/10 p-2 lg:block">
          <button
            type="button"
            onClick={onToggle}
            aria-label={collapsed ? 'Expand navigation' : 'Collapse navigation'}
            className="text-ink-400 hover:bg-sidebar-hover hover:text-white flex w-full items-center justify-center gap-2 rounded-md py-2 text-xs transition-colors"
          >
            {collapsed ? (
              <PanelLeftOpen className="size-4" aria-hidden />
            ) : (
              <>
                <PanelLeftClose className="size-4" aria-hidden />
                Collapse
              </>
            )}
          </button>
        </div>
      </aside>
    </>
  )
}

function SidebarLink({
  item,
  collapsed,
  badge,
}: {
  item: NavItem
  collapsed: boolean
  badge?: number
}) {
  const Icon = item.icon

  return (
    <NavLink
      to={item.to}
      // title keeps the label reachable when the rail is collapsed to icons.
      title={collapsed ? item.label : undefined}
      className={({ isActive }) =>
        cn(
          'group flex items-center gap-2.5 rounded-md px-2.5 py-2 text-sm transition-colors',
          isActive
            ? 'bg-sidebar-active font-medium text-white'
            : 'text-ink-400 hover:bg-sidebar-hover hover:text-white',
          collapsed && 'justify-center px-0',
        )
      }
    >
      <Icon className="size-4.5 shrink-0" aria-hidden />
      {!collapsed && <span className="flex-1 truncate">{item.label}</span>}
      {!collapsed && badge !== undefined && badge > 0 && (
        <span className="tabular rounded-full bg-danger-600 px-1.5 py-0.5 text-[10px] font-semibold text-white">
          {badge > 99 ? '99+' : badge}
        </span>
      )}
    </NavLink>
  )
}
