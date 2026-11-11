import { Link, NavLink, Outlet, useNavigate } from 'react-router-dom'
import { ArrowLeft, Briefcase, CreditCard, LayoutDashboard, LogOut, ShieldCheck, Tag, Users, Wallet } from 'lucide-react'
import { useAuth } from '../context/AuthContext'
import logo from '../assets/logo.svg'
import { APP_NAME } from '../utils/constants'

const NAVIGATION = [
  { to: '/admin', label: 'Overview', icon: LayoutDashboard, end: true },
  { to: '/admin/users', label: 'Users', icon: Users },
  { to: '/admin/businesses', label: 'Businesses', icon: Briefcase },
  { to: '/admin/plans', label: 'Plans', icon: Wallet },
  { to: '/admin/coupons', label: 'Coupons', icon: Tag },
  { to: '/admin/subscriptions', label: 'Subscriptions', icon: CreditCard },
  { to: '/admin/payments', label: 'Payments', icon: Wallet },
]

export function AdminLayout() {
  const { user, signOut } = useAuth()
  const navigate = useNavigate()

  const handleSignOut = async () => {
    await signOut()
    navigate('/login', { replace: true })
  }

  return (
    <div className="min-h-screen bg-slate-900 text-slate-100">
      <aside className="fixed inset-y-0 left-0 z-30 hidden w-64 border-r border-white/10 bg-slate-950 lg:block">
        <div className="flex items-center gap-2 px-5 py-5">
          <img src={logo} alt="" className="size-9 rounded-lg" />
          <span className="text-base font-semibold tracking-tight">{APP_NAME} Admin</span>
        </div>

        <nav className="space-y-0.5 px-3">
          {NAVIGATION.map((item) => {
            const Icon = item.icon

            return (
              <NavLink
                key={item.to}
                to={item.to}
                end={item.end}
                className={({ isActive }) =>
                  `flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition ${
                    isActive ? 'bg-brand-600 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white'
                  }`
                }
              >
                <Icon className="size-4" />
                {item.label}
              </NavLink>
            )
          })}
        </nav>

        <div className="absolute inset-x-0 bottom-0 border-t border-white/10 px-3 py-3">
          <Link
            to="/dashboard"
            className="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-300 transition hover:bg-white/10 hover:text-white"
          >
            <ArrowLeft className="size-4" />
            Back to workspace
          </Link>
          <div className="mt-2 px-2">
            <p className="truncate text-sm font-medium">{user?.name}</p>
            <p className="truncate text-xs text-slate-400">{user?.email}</p>
          </div>
          <button
            type="button"
            onClick={handleSignOut}
            className="mt-1 flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-300 transition hover:bg-white/10 hover:text-white"
          >
            <LogOut className="size-4" />
            Sign out
          </button>
        </div>
      </aside>

      <div className="lg:pl-64">
        <header className="flex items-center justify-between gap-3 border-b border-white/10 px-4 py-3">
          <div className="flex items-center gap-2 text-sm text-slate-300">
            <ShieldCheck className="size-4" />
            Platform administration
          </div>
          <Link to="/dashboard" className="text-sm text-slate-300 transition hover:text-white lg:hidden">
            Workspace
          </Link>
        </header>

        <main className="px-4 py-6 lg:px-8">
          <Outlet />
        </main>
      </div>
    </div>
  )
}

export default AdminLayout
