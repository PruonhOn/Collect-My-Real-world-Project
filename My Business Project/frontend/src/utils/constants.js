export const APP_NAME = import.meta.env.VITE_APP_NAME || 'My Business'

export const ORDER_STATUSES = [
  { value: 'pending', label: 'Pending' },
  { value: 'processing', label: 'Processing' },
  { value: 'completed', label: 'Completed' },
  { value: 'canceled', label: 'Canceled' },
  { value: 'refunded', label: 'Refunded' },
]

export const PAYMENT_STATUSES = [
  { value: 'unpaid', label: 'Unpaid' },
  { value: 'paid', label: 'Paid' },
  { value: 'partial', label: 'Partial' },
  { value: 'refunded', label: 'Refunded' },
]

export const INVOICE_STATUSES = [
  { value: 'draft', label: 'Draft' },
  { value: 'unpaid', label: 'Unpaid' },
  { value: 'paid', label: 'Paid' },
  { value: 'overdue', label: 'Overdue' },
  { value: 'canceled', label: 'Canceled' },
]

export const MEMBER_ROLES = [
  { value: 'admin', label: 'Admin' },
  { value: 'staff', label: 'Staff' },
]

export const SUBSCRIPTION_STATUSES = [
  { value: 'trialing', label: 'Trialing' },
  { value: 'active', label: 'Active' },
  { value: 'past_due', label: 'Past due' },
  { value: 'canceled', label: 'Canceled' },
  { value: 'expired', label: 'Expired' },
  { value: 'suspended', label: 'Suspended' },
]

export const BILLING_CYCLES = [
  { value: 'monthly', label: 'Monthly' },
  { value: 'yearly', label: 'Yearly' },
]

export const API_TOKEN_ABILITIES = [
  'products:read',
  'customers:read',
  'orders:read',
  'orders:write',
]

export const FEATURE_LABELS = {
  multi_branch: 'Multi-branch management',
  advanced_reports: 'Advanced reports',
  profit_reports: 'Profit reports',
  custom_roles: 'Custom roles',
  branded_invoices: 'Branded invoices',
  api_access: 'API access',
  limited_exports: 'Limited exports',
  pdf_excel_exports: 'PDF & Excel exports',
  low_stock_alerts: 'Low stock alerts',
  inventory_management: 'Inventory management',
  discounts_and_tax: 'Discounts and tax settings',
  activity_history: 'Business activity history',
  max_businesses: 'Businesses per user',
  max_products: 'Products',
  max_customers: 'Customers',
  max_members: 'Team members',
  max_orders_monthly: 'Orders per month',
  max_branches: 'Branches',
}

export const STATUS_TONES = {
  active: 'success',
  paid: 'success',
  completed: 'success',
  trialing: 'info',
  processing: 'info',
  pending: 'warning',
  unpaid: 'warning',
  partial: 'warning',
  past_due: 'warning',
  overdue: 'danger',
  canceled: 'danger',
  failed: 'danger',
  revoked: 'danger',
  expired: 'danger',
  suspended: 'danger',
  inactive: 'neutral',
  draft: 'neutral',
  invited: 'info',
}
