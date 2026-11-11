import { Navigate, Route, Routes } from 'react-router-dom'
import { AuthProvider } from './context/AuthContext'
import { BusinessProvider } from './context/BusinessContext'
import { AdminRoute, GuestRoute, ProtectedRoute } from './routes/guards'
import AuthLayout from './layouts/AuthLayout'
import BusinessLayout from './layouts/BusinessLayout'
import AdminLayout from './layouts/AdminLayout'

import Login from './pages/auth/Login'
import Register from './pages/auth/Register'
import ForgotPassword from './pages/auth/ForgotPassword'
import ResetPassword from './pages/auth/ResetPassword'
import VerifyEmail from './pages/auth/VerifyEmail'
import AcceptInvitation from './pages/auth/AcceptInvitation'

import Dashboard from './pages/business/Dashboard'
import Products from './pages/business/Products'
import Customers from './pages/business/Customers'
import Orders from './pages/business/Orders'
import Invoices from './pages/business/Invoices'
import Team from './pages/business/Team'
import Branches from './pages/business/Branches'
import Reports from './pages/business/Reports'
import Billing from './pages/business/Billing'
import ApiTokens from './pages/business/ApiTokens'
import Settings from './pages/business/Settings'

import AdminDashboard from './pages/admin/Dashboard'
import AdminUsers from './pages/admin/Users'
import AdminBusinesses from './pages/admin/Businesses'
import AdminPlans from './pages/admin/Plans'
import AdminCoupons from './pages/admin/Coupons'
import AdminSubscriptions from './pages/admin/Subscriptions'
import AdminPayments from './pages/admin/Payments'

export default function App({ appName }) {
  return (
    <AuthProvider>
      <BusinessProvider>
        <Routes>
          <Route element={<GuestRoute />}>
            <Route element={<AuthLayout />}>
              <Route path="/login" element={<Login />} />
              <Route path="/register" element={<Register />} />
              <Route path="/forgot-password" element={<ForgotPassword />} />
              <Route path="/reset-password" element={<ResetPassword />} />
              <Route path="/invitations/:token" element={<AcceptInvitation />} />
            </Route>
          </Route>

          <Route element={<ProtectedRoute />}>
            <Route path="/verify-email" element={<VerifyEmail />} />
            <Route element={<BusinessLayout />}>
              <Route index element={<Dashboard />} />
              <Route path="/dashboard" element={<Dashboard />} />
              <Route path="/products" element={<Products />} />
              <Route path="/customers" element={<Customers />} />
              <Route path="/orders" element={<Orders />} />
              <Route path="/invoices" element={<Invoices />} />
              <Route path="/team" element={<Team />} />
              <Route path="/branches" element={<Branches />} />
              <Route path="/reports" element={<Reports />} />
              <Route path="/billing" element={<Billing />} />
              <Route path="/api-tokens" element={<ApiTokens />} />
              <Route path="/settings" element={<Settings />} />
            </Route>
          </Route>

          <Route element={<AdminRoute />}>
            <Route element={<AdminLayout />}>
              <Route path="/admin" element={<AdminDashboard />} />
              <Route path="/admin/users" element={<AdminUsers />} />
              <Route path="/admin/businesses" element={<AdminBusinesses />} />
              <Route path="/admin/plans" element={<AdminPlans />} />
              <Route path="/admin/coupons" element={<AdminCoupons />} />
              <Route path="/admin/subscriptions" element={<AdminSubscriptions />} />
              <Route path="/admin/payments" element={<AdminPayments />} />
            </Route>
          </Route>

          <Route path="*" element={<NavigateFallback />} />
        </Routes>
      </BusinessProvider>
    </AuthProvider>
  )
}

function NavigateFallback() {
  return (
    <div className="flex min-h-screen flex-col items-center justify-center gap-3 bg-slate-50 text-center">
      <p className="text-3xl font-semibold text-slate-900">404</p>
      <p className="text-sm text-slate-500">The page you are looking for does not exist.</p>
      <a href="/dashboard" className="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white">
        Back to dashboard
      </a>
    </div>
  )
}
