import { useState } from 'react'
import { Link, NavLink, Outlet, useNavigate } from 'react-router-dom'
import {
  BarChart3,
  Building2,
  CreditCard,
  FileText,
  KeyRound,
  LayoutDashboard,
  LogOut,
  Menu,
  Package,
  Receipt,
  Settings,
  ShieldCheck,
  ShoppingCart,
  Users,
  UsersRound,
  X,
} from 'lucide-react'
import { useAuth } from '../context/AuthContext'
import { useBusiness } from '../context/BusinessContext'
import logo from '../assets/logo.svg'
import { APP_NAME } from '../utils/constants'
import { Badge } from '../components/ui/Badge'
import { Select } from '../components/ui/Form'
import CreateBusinessPanel from '../pages/business/CreateBusinessPanel'

const NAVIGATION = [
  { to: '/dashboard', label: 'Dashboard', icon: LayoutDashboard },
  { to: '/products', label: 'Products', icon: Package },
  { to: '/customers', label: 'Customers', icon: Users },
  { to: '/orders', label: 'Orders', icon: ShoppingCart },
  { to: '/invoices', label: 'Invoices', icon: Receipt },
  { to: '/branches', label: 'Branches', icon: Building2, feature: 'multi_branch' },
  { to: '/reports', label: 'Reports', icon: BarChart3, feature: 'advanced_reports' },
  { to: '/api-tokens', label: 'API tokens', icon: KeyRound, feature: 'api_access' },
  { to: '/team', label: 'Team', icon: UsersRound },
  { to: '/billing', label: 'Billing', icon: CreditCard },
  { to: '/settings', label: 'Settings', icon: Settings },
]

export function BusinessLayout() {
  const { user, signOut, isSuperAdmin } = useAuth()
  const { businesses, activeBusiness, activeBusinessId, switchBusiness, allows, billing } = useBusiness()
  const navigate = useNavigate()
  const [mobileOpen, setMobileOpen] = useState(false)

  const handleSwitch = async (event) => {
    const value = Number(event.target.value)

    if (value && value !== activeBusinessId) {
      await switchBusiness(value)
    }
  }

  const handleSignOut = async () => {
    await signOut()
    navigate('/login', { replace: true })
  }

  const sidebar = (
    <div className="flex h-full flex-col">
      <div className="flex items-center gap-2 px-5 py-5">
        <img src={logo} alt="" className="size-9 rounded-lg" />
        <span className="text-base font-semibold tracking-tight text-slate-900">{APP_NAME}</span>
      </div>

      <nav className="scroll-thin flex-1 space-y-0.5 overflow-y-auto px-3 pb-4">
        {NAVIGATION.map((item) => {
          const Icon = item.icon
          const locked = item.feature ? !allows(item.feature) : false

          return (
            <NavLink
              key={item.to}
              to={item.to}
              onClick={() => setMobileOpen(false)}
              className={({ isActive }) =>
                `flex items-center justify-between gap-2 rounded-lg px-3 py-2 text-sm transition ${
                  isActive
                    ? 'bg-brand-50 font-medium text-brand-700'
                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                }`
              }
            >
              <span className="flex items-center gap-2.5">
                <Icon className="size-4" />
                {item.label}
              </span>
              {locked && <span className="text-[10px] font-semibold uppercase tracking-wide text-amber-600">Pro</span>}
            </NavLink>
          )
        })}
      </nav>

      <div className="border-t border-slate-200 px-3 py-3">
        <div className="px-2">
          <p className="truncate text-sm font-medium text-slate-800">{user?.name}</p>
          <p className="truncate text-xs text-slate-500">{user?.email}</p>
        </div>
        {isSuperAdmin && (
          <Link
            to="/admin"
            className="mt-2 flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
          >
            <ShieldCheck className="size-4" />
            Admin panel
          </Link>
        )}
        <button
          type="button"
          onClick={handleSignOut}
          className="mt-1 flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
        >
          <LogOut className="size-4" />
          Sign out
        </button>
      </div>
    </div>
  )

  return (
    <div className="min-h-screen bg-slate-50">
      <aside className="fixed inset-y-0 left-0 z-30 hidden w-64 border-r border-slate-200 bg-white lg:block">{sidebar}</aside>

      {mobileOpen && (
        <div className="fixed inset-0 z-40 lg:hidden">
          <div className="absolute inset-0 bg-slate-900/40" onClick={() => setMobileOpen(false)} />
          <div className="absolute inset-y-0 left-0 w-64 bg-white shadow-xl">{sidebar}</div>
        </div>
      )}

      <div className="lg:pl-64">
        <header className="sticky top-0 z-20 flex flex-wrap items-center gap-3 border-b border-slate-200 bg-white/95 px-4 py-3 backdrop-blur">
          <button
            type="button"
            className="rounded-md p-1.5 text-slate-500 hover:bg-slate-100 lg:hidden"
            onClick={() => setMobileOpen((open) => !open)}
            aria-label="Toggle navigation"
          >
            {mobileOpen ? <X className="size-5" /> : <Menu className="size-5" />}
          </button>

          <div className="flex min-w-0 flex-1 items-center gap-3">
            {businesses.length > 0 ? (
              <div className="flex min-w-0 items-center gap-2">
                <Select
                  aria-label="Active business"
                  className="max-w-56"
                  value={activeBusinessId ?? ''}
                  onChange={handleSwitch}
                >
                  {businesses.map((business) => (
                    <option key={business.id} value={business.id}>
                      {business.name}
                    </option>
                  ))}
                </Select>
                {activeBusiness && (
                  <Badge tone={activeBusiness.role === 'owner' ? 'brand' : 'neutral'} className="hidden sm:inline-flex">
                    {activeBusiness.role}
                  </Badge>
                )}
                {billing?.subscription && (
                  <Badge tone={billing.subscription.status === 'active' ? 'success' : 'warning'}>
                    {billing.subscription.plan} · {billing.subscription.status}
                  </Badge>
                )}
              </div>
            ) : (
              <span className="text-sm text-slate-500">No business selected</span>
            )}
          </div>

          <Link
            to="/invoices"
            className="hidden items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm text-slate-600 transition hover:bg-slate-100 sm:flex"
          >
            <FileText className="size-4" />
            Invoices
          </Link>
        </header>

        <main className="px-4 py-6 lg:px-8">
          {businesses.length === 0 ? (
            <CreateBusinessPanel />
          ) : (
            <Outlet />
          )}
        </main>
      </div>
    </div>
  )
}

export default BusinessLayout
