import { useState } from 'react'
import { Outlet, useLocation } from 'react-router-dom'
import { Sidebar } from '@/components/layout/Sidebar'
import { Topbar } from '@/components/layout/Topbar'
import { useAuth } from '@/features/auth/auth-context'
import { useUnreadNotifications } from '@/features/notifications/useUnreadNotifications'

const SIDEBAR_COLLAPSED_KEY = 'logistics.sidebar.collapsed'

export function AppShell() {
  const location = useLocation()
  const { can } = useAuth()
  const [collapsed, setCollapsed] = useState(
    () => window.localStorage.getItem(SIDEBAR_COLLAPSED_KEY) === 'true',
  )
  const [mobileOpen, setMobileOpen] = useState(false)

  // The badge follows the unread filter of the notifications endpoint, and only
  // while the user may read notifications: a 403 on a 15-second loop is a
  // request storm against a permission the session does not hold.
  const { data: unreadNotifications = 0 } = useUnreadNotifications(can('notification.view'))

  // Close the mobile drawer whenever the route changes, otherwise it stays
  // open on top of the page the user just navigated to. Adjusting during
  // render rather than in an effect avoids a frame where the drawer is still
  // open over the page that was just navigated to.
  const [lastPath, setLastPath] = useState(location.pathname)
  if (location.pathname !== lastPath) {
    setLastPath(location.pathname)
    setMobileOpen(false)
  }

  function toggleCollapsed() {
    setCollapsed((current) => {
      const next = !current
      window.localStorage.setItem(SIDEBAR_COLLAPSED_KEY, String(next))
      return next
    })
  }

  return (
    <div className="min-h-screen">
      <Sidebar
        collapsed={collapsed}
        onToggle={toggleCollapsed}
        mobileOpen={mobileOpen}
        onMobileClose={() => setMobileOpen(false)}
        // Populated from the dashboard summary endpoint in a later phase.
        badgeCounts={{}}
      />

      <div
        className={
          collapsed
            ? 'flex min-h-screen flex-col transition-[padding] duration-200 lg:pl-16'
            : 'flex min-h-screen flex-col transition-[padding] duration-200 lg:pl-64'
        }
      >
        <Topbar onMenuClick={() => setMobileOpen(true)} unreadNotifications={unreadNotifications} />

        <main className="flex-1">
          <Outlet />
        </main>
      </div>
    </div>
  )
}
