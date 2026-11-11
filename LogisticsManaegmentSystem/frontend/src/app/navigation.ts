import {
  Anchor,
  BadgeDollarSign,
  Building2,
  ChevronRight,
  ClipboardList,
  Container,
  CreditCard,
  FileBarChart,
  FileText,
  Gauge,
  Landmark,
  MapPin,
  Receipt,
  Route,
  ScrollText,
  Settings,
  Ship,
  ShieldCheck,
  Sparkles,
  Truck,
  Upload,
  UserCog,
  Users,
  Warehouse,
  type LucideIcon,
} from 'lucide-react'
import { MASTER_SURFACE_KEYS, SURFACES } from '@/features/master-data/registry'
import type { MasterSurfaceKey } from '@/features/master-data/types'

export interface NavItem {
  label: string
  to: string
  icon: LucideIcon
  /**
   * Permission required to see this entry. A user without it does not see the
   * link at all, mirroring the API's own authorization.
   */
  permission?: string
  /** Show a count badge, e.g. overdue invoices. */
  badgeKey?: string
}

export interface NavSection {
  label: string
  items: NavItem[]
}

/**
 * Icons for the master-data modules.
 *
 * The only thing a surface definition does not carry, so it is the one thing kept
 * here. Everything else about those entries - the label, the URL, and crucially
 * the permission - is read off the registry.
 *
 * Typed over `MasterSurfaceKey` rather than `SurfaceKey`, and that is the point of
 * the split: the registry also describes shipments and bookings, and a
 * `Record<SurfaceKey, _>` here would have demanded an icon for a consignment and
 * put it in the Master Data section, one click away from looking like another
 * lookup table. Those two have hand-written entries under Operations instead.
 */
const MASTER_ICONS: Record<MasterSurfaceKey, LucideIcon> = {
  customers: Building2,
  shippers: Upload,
  consignees: MapPin,
  carriers: Anchor,
  vessels: Ship,
  ports: Landmark,
  warehouses: Warehouse,
  factories: Warehouse,
  trucks: Truck,
  drivers: UserCog,
}

/**
 * The master-data section, derived from the surface registry.
 *
 * This is what keeps the sidebar honest. A hand-written list of ten links drifts:
 * the links below used to ask for `shipper.view` and `factory.view` while the
 * routes asked for `customer.view` and `warehouse.view`, so a user holding only
 * the broad read permission had a working screen they could never navigate to.
 * One definition of "who may see this module" is now the only one.
 *
 * Driven by `MASTER_SURFACE_KEYS` rather than `Object.keys`, so a module joining
 * the registry does not silently appear in a section it does not belong to.
 */
const MASTER_NAV: NavItem[] = MASTER_SURFACE_KEYS.map((key) => ({
  label: SURFACES[key].title,
  to: `/master${SURFACES[key].path}`,
  icon: MASTER_ICONS[key],
  permission: SURFACES[key].viewPermission,
}))

export const NAVIGATION: NavSection[] = [
  {
    label: 'Overview',
    items: [{ label: 'Dashboard', to: '/dashboard', icon: Gauge, permission: 'dashboard.view' }],
  },
  {
    label: 'Operations',
    items: [
      { label: 'Bookings', to: '/operations/bookings', icon: ClipboardList, permission: 'booking.view' },
      { label: 'Shipments', to: '/operations/shipments', icon: Ship, permission: 'shipment.view' },
      { label: 'Tracking', to: '/operations/tracking', icon: Route, permission: 'tracking.view' },
      { label: 'Containers', to: '/operations/containers', icon: Container, permission: 'container.view' },
      { label: 'Customs', to: '/operations/customs', icon: ScrollText, permission: 'customs.view' },
      { label: 'Deliveries', to: '/operations/deliveries', icon: Truck, permission: 'delivery.view' },
    ],
  },
  {
    label: 'Master Data',
    items: MASTER_NAV,
  },
  {
    label: 'Finance',
    items: [
      { label: 'Charges', to: '/finance/charges', icon: Receipt, permission: 'charge.view' },
      { label: 'Invoices', to: '/finance/invoices', icon: FileText, permission: 'invoice.view', badgeKey: 'overdue_invoices' },
      { label: 'Payments', to: '/finance/payments', icon: CreditCard, permission: 'payment.view' },
      { label: 'Expenses', to: '/finance/expenses', icon: BadgeDollarSign, permission: 'expense.view' },
    ],
  },
  {
    label: 'Documents',
    items: [{ label: 'Documents', to: '/documents', icon: FileText, permission: 'document.view' }],
  },
  {
    label: 'Reports',
    items: [
      { label: 'Shipment Reports', to: '/reports/shipments', icon: FileBarChart, permission: 'report.view' },
      { label: 'Operational Reports', to: '/reports/operational', icon: Route, permission: 'report.view' },
      { label: 'Customer Reports', to: '/reports/customers', icon: Building2, permission: 'report.view' },
      { label: 'Carrier Reports', to: '/reports/carriers', icon: Anchor, permission: 'report.view' },
      { label: 'Financial Reports', to: '/reports/financial', icon: BadgeDollarSign, permission: 'report.view' },
    ],
  },
  {
    label: 'Premium',
    items: [
      { label: 'Premium Dashboard', to: '/premium/dashboard', icon: Sparkles, permission: 'premium.view' },
      { label: 'Premium Shipments', to: '/premium/shipments', icon: Ship, permission: 'premium.view' },
      { label: 'Premium Customers', to: '/premium/customers', icon: Building2, permission: 'premium.view' },
    ],
  },
  {
    label: 'Administration',
    items: [
      { label: 'Users', to: '/admin/users', icon: Users, permission: 'user.view' },
      { label: 'Roles & Permissions', to: '/admin/roles', icon: ShieldCheck, permission: 'role.view' },
      { label: 'Notifications', to: '/admin/notifications', icon: FileText, permission: 'notification.view' },
      { label: 'Activity Logs', to: '/admin/activity-logs', icon: ScrollText, permission: 'activity_log.view' },
      { label: 'System Settings', to: '/admin/settings', icon: Settings, permission: 'setting.view' },
    ],
  },
]

/** The landing route after signing in. */
export const DEFAULT_ROUTE = '/dashboard'

export { ChevronRight }
