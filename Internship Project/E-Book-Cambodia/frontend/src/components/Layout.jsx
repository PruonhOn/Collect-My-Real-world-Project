import { Outlet, useLocation, Navigate } from 'react-router-dom'
import Navbar from './Navbar'
import AuthorNavbar from './author/AuthorNavbar'
import AdminChrome from './admin/AdminChrome'
import BroadcastBanner from './BroadcastBanner'
import Footer from './Footer'
import { useAuth } from '../context/AuthContext'
import { useAuthor } from '../context/AuthorContext'
import { useMembership } from '../hooks/useMembership'

const VIEWPORT_ROUTES = ['/messages', '/admin/messages']

function useViewportLayout() {
  const { pathname } = useLocation()
  return VIEWPORT_ROUTES.includes(pathname)
}

function Shell({ viewport, bg, nav, children }) {
  const { isVip } = useMembership()

  return (
    <div className={`${viewport ? 'h-[100dvh] overflow-hidden' : 'min-h-screen'} flex flex-col ${bg}`}>
      {/* Animated gold frame around the whole viewport while VIP is active. */}
      {isVip && <div className="vip-page-frame" aria-hidden="true" />}
      {/* Same announcements in every portal: members, authors and admins. */}
      {!viewport && <BroadcastBanner />}
      {nav}
      <main className={viewport ? 'flex-1 min-h-0 overflow-hidden' : 'flex-1'}>{children}</main>
      {!viewport && <Footer />}
    </div>
  )
}

export function PublicLayout() {
  const viewport = useViewportLayout()

  return (
    <Shell viewport={viewport} bg="bg-gray-50" nav={<Navbar />}>
      <Outlet />
    </Shell>
  )
}

export function ProtectedLayout() {
  const { isAuthenticated, loading } = useAuth()
  const viewport = useViewportLayout()

  if (loading) {
    return <div className="min-h-screen flex items-center justify-center">Loading...</div>
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />
  }

  return (
    <Shell viewport={viewport} bg="bg-gray-50" nav={<Navbar />}>
      <Outlet />
    </Shell>
  )
}

export function AdminLayout() {
  const { isAuthenticated, isAdmin, loading } = useAuth()
  const viewport = useViewportLayout()

  if (loading) {
    return <div className="min-h-screen flex items-center justify-center">Loading...</div>
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />
  }

  if (!isAdmin) {
    return <Navigate to="/" replace />
  }

  return (
    <AdminChrome viewport={viewport}>
      <Outlet />
    </AdminChrome>
  )
}

export function AuthorLayout() {
  const { isAuthor, loading } = useAuthor()
  const viewport = useViewportLayout()

  if (loading) {
    return <div className="min-h-screen flex items-center justify-center">Loading...</div>
  }

  if (!isAuthor) {
    return <Navigate to="/login" replace />
  }

  return (
    <Shell viewport={viewport} bg="bg-gray-50" nav={<AuthorNavbar />}>
      <Outlet />
    </Shell>
  )
}

export { Navbar, Footer }
