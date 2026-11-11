export const APP_NAME = "MyBusiness Assistant";
export const APP_TIMEZONE = "Asia/Phnom_Penh";
export const DEFAULT_CURRENCY = "USD";
export const SUPPORTED_CURRENCIES = ["USD", "KHR"] as const;
export const SUPPORTED_LOCALES = ["en", "km"] as const;

export type SupportedCurrency = (typeof SUPPORTED_CURRENCIES)[number];
export type SupportedLocale = (typeof SUPPORTED_LOCALES)[number];

export const SUBSCRIPTION_STATUSES = [
  "trialing",
  "active",
  "past_due",
  "canceled",
  "expired",
] as const;

export type SubscriptionStatus = (typeof SUBSCRIPTION_STATUSES)[number];

export const BILLING_CYCLES = ["monthly", "yearly"] as const;
export type BillingCycle = (typeof BILLING_CYCLES)[number];

export const ENTITLEMENT_KEYS = [
  "max_products",
  "max_employees",
  "max_branches",
  "max_transactions_per_month",
  "max_workspaces",
  "storage_mb",
  "ai_credits_per_month",
  "modules",
] as const;

export type EntitlementKey = (typeof ENTITLEMENT_KEYS)[number];

export const AI_USAGE_METRIC = "ai_credits";
export const TRANSACTION_METRIC = "transactions";

export const PRODUCT_UNITS = [
  "pcs",
  "kg",
  "g",
  "box",
  "pack",
  "bottle",
  "liter",
  "meter",
  "pair",
  "set",
  "service",
] as const;

export type ProductUnit = (typeof PRODUCT_UNITS)[number];

export const PAYMENT_METHODS = [
  "cash",
  "khqr",
  "aba",
  "card",
  "bank_transfer",
  "credit",
  "other",
] as const;

export type PaymentMethod = (typeof PAYMENT_METHODS)[number];

export const CUSTOMER_TYPES = ["individual", "business"] as const;
export type CustomerType = (typeof CUSTOMER_TYPES)[number];

export const STOCK_MOVEMENT_TYPES = [
  "opening",
  "adjustment",
  "purchase",
  "sale",
  "return",
  "transfer_in",
  "transfer_out",
  "void",
] as const;

export type StockMovementType = (typeof STOCK_MOVEMENT_TYPES)[number];

export const EXPENSE_CATEGORIES = [
  "rent",
  "utilities",
  "salaries",
  "transport",
  "marketing",
  "supplies",
  "maintenance",
  "tax",
  "other",
] as const;

export type ExpenseCategory = (typeof EXPENSE_CATEGORIES)[number];

export const EMPLOYEE_STATUSES = ["active", "inactive"] as const;
export type EmployeeStatus = (typeof EMPLOYEE_STATUSES)[number];

export const ORDER_STATUSES = [
  "pending",
  "confirmed",
  "fulfilled",
  "cancelled",
] as const;
export type OrderStatus = (typeof ORDER_STATUSES)[number];

export const TRANSFER_STATUSES = ["pending", "completed", "cancelled"] as const;
export type TransferStatus = (typeof TRANSFER_STATUSES)[number];
