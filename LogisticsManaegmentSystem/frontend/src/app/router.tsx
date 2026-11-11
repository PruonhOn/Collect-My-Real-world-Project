import type { ReactElement } from 'react'
import { Navigate, createBrowserRouter } from 'react-router-dom'
import { AppShell } from '@/components/layout/AppShell'
import { ProtectedRoute } from '@/components/guards/ProtectedRoute'
import { DashboardPage } from '@/pages/DashboardPage'
import { ForbiddenPage, NotFoundPage } from '@/pages/ErrorPages'
import { ForgotPasswordPage } from '@/pages/auth/ForgotPasswordPage'
import { GoogleCallbackPage } from '@/pages/auth/GoogleCallbackPage'
import { LoginPage } from '@/pages/auth/LoginPage'
import { RegisterPage } from '@/pages/auth/RegisterPage'
import { ResetPasswordPage } from '@/pages/auth/ResetPasswordPage'
import {
  CarriersPage,
  ConsigneesPage,
  CustomersPage,
  DriversPage,
  FactoriesPage,
  PortsPage,
  ShippersPage,
  TrucksPage,
  VesselsPage,
  WarehousesPage,
} from '@/pages/master/MasterPages'
import { BookingsPage } from '@/pages/operations/BookingsPage'
import { ContainersPage } from '@/pages/operations/ContainersPage'
import { CustomsPage } from '@/pages/operations/CustomsPage'
import { DeliveriesPage } from '@/pages/operations/DeliveriesPage'
import { ShipmentsPage } from '@/pages/operations/ShipmentsPage'
import { TrackingPage } from '@/pages/operations/TrackingPage'
import { ChargesPage, ExpensesPage, InvoicesPage, PaymentsPage } from '@/pages/finance/FinancePages'
import { DocumentsPage } from '@/pages/documents/DocumentsPage'
import {
  CarrierReportsPage,
  CustomerReportsPage,
  FinancialReportsPage,
  OperationalReportsPage,
  ShipmentReportsPage,
} from '@/pages/reports/ReportPages'
import {
  ActivityLogsPage,
  NotificationsPage,
  RolesPage,
  SettingsPage,
  UsersPage,
} from '@/pages/admin/AdminPages'
import {
  PremiumDashboardPage,
  PremiumShipmentsPage,
  PremiumCustomersPage,
} from '@/pages/premium/PremiumPages'
import { SURFACES } from '@/features/master-data/registry'
import type { MasterSurfaceKey } from '@/features/master-data/types'
import { DEFAULT_ROUTE } from '@/app/navigation'

/**
 * Route table.
 *
 * Only pages that actually exist are registered. Each phase adds its own
 * segment here as the modules land, so an unbuilt route resolves to the real
 * 404 page rather than a stub that pretends to work.
 */

/**
 * The master-data screens, keyed by surface.
 *
 * Typed as a total `Record` so adding a surface to the registry without giving
 * it a page is a build error rather than a nav link that dead-ends on the 404.
 * The URL comes from the registry's own path rather than being written out
 * again, so the sidebar, the router and the API cannot drift apart.
 *
 * Keyed over `MasterSurfaceKey`, not `SurfaceKey`, for the same reason the
 * sidebar's icon map is: shipments and bookings are registered as surfaces and
 * are *not* master-data screens, so they get their own routes below instead of
 * being forced into a `/master/...` path and a `Record` that would have to invent
 * one.
 */
const MASTER_PAGES: Record<MasterSurfaceKey, ReactElement> = {
  customers: <CustomersPage />,
  shippers: <ShippersPage />,
  consignees: <ConsigneesPage />,
  carriers: <CarriersPage />,
  vessels: <VesselsPage />,
  ports: <PortsPage />,
  warehouses: <WarehousesPage />,
  factories: <FactoriesPage />,
  trucks: <TrucksPage />,
  drivers: <DriversPage />,
}

const masterRoutes = Object.keys(MASTER_PAGES).map((key) => {
  const surface = key as MasterSurfaceKey

  return {
    path: `master${SURFACES[surface].path}`,
    element: MASTER_PAGES[surface],
  }
})

/**
 * Routes for the transactional modules.
 *
 * Separate from the master routes because they are not namespaced under
 * `/master` and do not follow the one-surface-one-page loop: each is a module
 * with its own place in the lifecycle. The path is still read from the registry
 * rather than repeated, so the sidebar link and the route cannot disagree.
 *
 * Written out rather than derived from the registry, and the three newer entries
 * are why that is deliberate: shipments, bookings and tracking are one screen each
 * with a different shape of problem, so there is no loop that would generate this
 * list correctly. A consignment's containers, filings and deliveries hang off it,
 * and they are listed in the order they happen rather than alphabetically.
 *
 * The finance group is in the same order for the same kind of reason, and it is
 * the one list here that is a *chain* rather than a set of screens: a charge goes
 * on an invoice, a payment settles it, and an expense is what the job cost. A user
 * moving through them left to right is walking a consignment's money in the order
 * it happens, which is also the order the seed data and the dashboard's
 * outstanding figure are built on.
 */
const OPERATIONAL_ROUTES = [
  { path: `operations${SURFACES.bookings.path}`, element: <BookingsPage /> },
  { path: `operations${SURFACES.shipments.path}`, element: <ShipmentsPage /> },
  { path: `operations${SURFACES.tracking.path}`, element: <TrackingPage /> },
  { path: `operations${SURFACES.containers.path}`, element: <ContainersPage /> },
  { path: `operations${SURFACES.customs.path}`, element: <CustomsPage /> },
  { path: `operations${SURFACES.deliveries.path}`, element: <DeliveriesPage /> },
]

const FINANCE_ROUTES = [
  { path: `finance${SURFACES.charges.path}`, element: <ChargesPage /> },
  { path: `finance${SURFACES.invoices.path}`, element: <InvoicesPage /> },
  { path: `finance${SURFACES.payments.path}`, element: <PaymentsPage /> },
  { path: `finance${SURFACES.expenses.path}`, element: <ExpensesPage /> },
]

/**
 * The document register, on its own and not under a group prefix.
 *
 * Its own entry rather than a fourth member of any list above, because it belongs to
 * none of them: the register spans consignments the way the operations screens do
 * and holds the business's own certificates the way master data holds a carrier, and
 * filing it under either heading would make the sidebar's "Documents" section point
 * at a route that lives under another section's name. The path is still read from
 * the registry, so the sidebar link and this route cannot disagree.
 */
const DOCUMENT_ROUTES = [
  { path: SURFACES.documents.path, element: <DocumentsPage /> },
]

export const router = createBrowserRouter([
  {
    path: '/login',
    element: <LoginPage />,
  },
  {
    path: '/register',
    element: <RegisterPage />,
  },
  {
    path: '/forgot-password',
    element: <ForgotPasswordPage />,
  },
  {
    path: '/reset-password',
    element: <ResetPasswordPage />,
  },
  {
    path: '/auth/google/callback',
    element: <GoogleCallbackPage />,
  },

  {
    path: '/',
    element: (
      <ProtectedRoute>
        <AppShell />
      </ProtectedRoute>
    ),
    children: [
      { index: true, element: <Navigate to={DEFAULT_ROUTE} replace /> },
      { path: 'dashboard', element: <DashboardPage /> },
      { path: 'forbidden', element: <ForbiddenPage /> },
      ...masterRoutes,
      ...OPERATIONAL_ROUTES,
      ...FINANCE_ROUTES,
      ...DOCUMENT_ROUTES,
      { path: 'reports/shipments', element: <ShipmentReportsPage /> },
      { path: 'reports/operational', element: <OperationalReportsPage /> },
      { path: 'reports/customers', element: <CustomerReportsPage /> },
      { path: 'reports/carriers', element: <CarrierReportsPage /> },
      { path: 'reports/financial', element: <FinancialReportsPage /> },
      { path: 'admin/users', element: <UsersPage /> },
      { path: 'admin/roles', element: <RolesPage /> },
      { path: 'admin/notifications', element: <NotificationsPage /> },
      { path: 'admin/activity-logs', element: <ActivityLogsPage /> },
      { path: 'admin/settings', element: <SettingsPage /> },
      { path: 'premium/dashboard', element: <PremiumDashboardPage /> },
      { path: 'premium/shipments', element: <PremiumShipmentsPage /> },
      { path: 'premium/customers', element: <PremiumCustomersPage /> },
    ],
  },

  {
    path: '*',
    element: <NotFoundPage />,
  },
])
