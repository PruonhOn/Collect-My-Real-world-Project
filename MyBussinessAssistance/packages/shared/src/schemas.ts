import { z } from "zod";
import {
  BILLING_CYCLES,
  CUSTOMER_TYPES,
  EMPLOYEE_STATUSES,
  PAYMENT_METHODS,
  PRODUCT_UNITS,
  SUPPORTED_CURRENCIES,
  SUPPORTED_LOCALES,
} from "./constants";

const cambodiaPhone = /^(\+855|0)\d{8,9}$/;

// Local development test account that bypasses strict RFC email validation.
const TEST_ACCOUNT_EMAIL = "mybussiness@123";

export const localeSchema = z.enum(SUPPORTED_LOCALES);
export const currencySchema = z.enum(SUPPORTED_CURRENCIES);

export const emailSchema = z.union([
  z.literal(TEST_ACCOUNT_EMAIL),
  z.string().trim().toLowerCase().email().max(254),
]);
export const phoneSchema = z
  .string()
  .trim()
  .regex(cambodiaPhone, "Enter a valid Cambodian phone number");

export const passwordSchema = z
  .string()
  .min(10, "Password must be at least 10 characters")
  .max(128, "Password is too long")
  .regex(/[A-Za-z]/, "Password must include a letter")
  .regex(/[0-9]/, "Password must include a number");

export const registerSchema = z.object({
  name: z.string().trim().min(2).max(120),
  email: emailSchema,
  password: passwordSchema,
  phone: phoneSchema.optional(),
  locale: localeSchema.default("en"),
});

export const loginSchema = z.object({
  email: emailSchema,
  password: z.string().min(1).max(128),
});

export const forgotPasswordSchema = z.object({
  email: emailSchema,
});

export const resetPasswordSchema = z.object({
  token: z.string().min(16),
  password: passwordSchema,
});

export const createWorkspaceSchema = z.object({
  name: z.string().trim().min(2).max(120),
  defaultCurrency: currencySchema.default("USD"),
  locale: localeSchema.default("en"),
});

export const createBranchSchema = z.object({
  name: z.string().trim().min(2).max(120),
  code: z.string().trim().min(1).max(24),
  phone: phoneSchema.optional(),
  address: z.string().trim().max(300).optional(),
});

export const paginationSchema = z.object({
  page: z.coerce.number().int().min(1).default(1),
  pageSize: z.coerce.number().int().min(1).max(100).default(20),
});

export const updateWorkspaceSchema = z.object({
  name: z.string().trim().min(2).max(120).optional(),
  defaultCurrency: currencySchema.optional(),
  locale: localeSchema.optional(),
  taxEnabled: z.boolean().optional(),
  taxRateBasisPoints: z.number().int().min(0).max(10_000).optional(),
  invoicePrefix: z.string().trim().min(1).max(12).optional(),
  exchangeRateKhrPerUsd: z.number().int().positive().max(100_000).optional(),
});

export const inviteMemberSchema = z.object({
  email: emailSchema,
  roleId: z.string().min(1),
});

export const updateMemberSchema = z.object({
  roleId: z.string().min(1),
});

export const verifyEmailSchema = z.object({
  token: z.string().min(16),
});

export const acceptInvitationSchema = z.object({
  token: z.string().min(16),
});

export const updateBranchSchema = z.object({
  name: z.string().trim().min(2).max(120).optional(),
  phone: phoneSchema.optional(),
  address: z.string().trim().max(300).optional(),
  status: z.enum(["active", "inactive"]).optional(),
});

const centsSchema = z.number().int().min(0).max(1_000_000_000_000);
const optionalText = (max: number) => z.string().trim().max(max).optional();
const optionalEmail = z.string().trim().toLowerCase().email().max(254).optional();
const dateMsSchema = z.number().int().positive();

export const createCategorySchema = z.object({
  name: z.string().trim().min(1).max(80),
  description: optionalText(300),
});

export const updateCategorySchema = z.object({
  name: z.string().trim().min(1).max(80).optional(),
  description: optionalText(300),
});

export const createProductSchema = z.object({
  name: z.string().trim().min(1).max(160),
  sku: z.string().trim().min(1).max(64),
  barcode: optionalText(64),
  categoryId: z.string().min(1).nullable().optional(),
  description: optionalText(1000),
  unit: z.enum(PRODUCT_UNITS).default("pcs"),
  costPriceCents: centsSchema.default(0),
  sellingPriceCents: centsSchema.default(0),
  taxRateBasisPoints: z.number().int().min(0).max(10_000).nullable().optional(),
  trackInventory: z.boolean().default(true),
  lowStockThreshold: z.number().int().min(0).max(1_000_000).default(5),
  openingStock: z.number().int().min(0).max(1_000_000).optional(),
});

export const updateProductSchema = z.object({
  name: z.string().trim().min(1).max(160).optional(),
  sku: z.string().trim().min(1).max(64).optional(),
  barcode: z.string().trim().max(64).nullable().optional(),
  categoryId: z.string().min(1).nullable().optional(),
  description: z.string().trim().max(1000).nullable().optional(),
  unit: z.enum(PRODUCT_UNITS).optional(),
  costPriceCents: centsSchema.optional(),
  sellingPriceCents: centsSchema.optional(),
  taxRateBasisPoints: z.number().int().min(0).max(10_000).nullable().optional(),
  trackInventory: z.boolean().optional(),
  lowStockThreshold: z.number().int().min(0).max(1_000_000).optional(),
  status: z.enum(["active", "inactive"]).optional(),
});

export const adjustStockSchema = z.object({
  branchId: z.string().min(1),
  productId: z.string().min(1),
  quantity: z.number().int().min(0).max(1_000_000),
  note: optionalText(300),
});

export const createCustomerSchema = z.object({
  name: z.string().trim().min(1).max(160),
  phone: z.string().trim().max(30).optional(),
  email: optionalEmail,
  address: optionalText(300),
  type: z.enum(CUSTOMER_TYPES).default("individual"),
  creditLimitCents: centsSchema.default(0),
  notes: optionalText(1000),
});

export const updateCustomerSchema = z.object({
  name: z.string().trim().min(1).max(160).optional(),
  phone: z.string().trim().max(30).nullable().optional(),
  email: z.string().trim().toLowerCase().email().max(254).nullable().optional(),
  address: z.string().trim().max(300).nullable().optional(),
  type: z.enum(CUSTOMER_TYPES).optional(),
  creditLimitCents: centsSchema.optional(),
  notes: z.string().trim().max(1000).nullable().optional(),
  status: z.enum(["active", "inactive"]).optional(),
});

export const createSupplierSchema = z.object({
  name: z.string().trim().min(1).max(160),
  contactName: optionalText(120),
  phone: z.string().trim().max(30).optional(),
  email: optionalEmail,
  address: optionalText(300),
  notes: optionalText(1000),
});

export const updateSupplierSchema = z.object({
  name: z.string().trim().min(1).max(160).optional(),
  contactName: z.string().trim().max(120).nullable().optional(),
  phone: z.string().trim().max(30).nullable().optional(),
  email: z.string().trim().toLowerCase().email().max(254).nullable().optional(),
  address: z.string().trim().max(300).nullable().optional(),
  notes: z.string().trim().max(1000).nullable().optional(),
  status: z.enum(["active", "inactive"]).optional(),
});

export const saleItemInputSchema = z.object({
  productId: z.string().min(1),
  quantity: z.number().int().min(1).max(100_000),
  unitPriceCents: centsSchema.optional(),
  discountCents: centsSchema.optional(),
});

export const createSaleSchema = z.object({
  branchId: z.string().min(1),
  customerId: z.string().min(1).nullable().optional(),
  customerName: optionalText(160),
  paymentMethod: z.enum(PAYMENT_METHODS).default("cash"),
  paidCents: centsSchema.default(0),
  discountCents: centsSchema.default(0),
  saleDate: dateMsSchema.optional(),
  dueDate: dateMsSchema.nullable().optional(),
  note: optionalText(500),
  items: z.array(saleItemInputSchema).min(1).max(200),
});

export const salePaymentSchema = z.object({
  paidCents: centsSchema,
});

export const purchaseItemInputSchema = z.object({
  productId: z.string().min(1),
  quantity: z.number().int().min(1).max(100_000),
  unitCostCents: centsSchema.optional(),
});

export const createPurchaseSchema = z.object({
  branchId: z.string().min(1),
  supplierId: z.string().min(1).nullable().optional(),
  supplierName: optionalText(160),
  paymentMethod: z.enum(PAYMENT_METHODS).default("cash"),
  paidCents: centsSchema.default(0),
  purchaseDate: dateMsSchema.optional(),
  note: optionalText(500),
  items: z.array(purchaseItemInputSchema).min(1).max(200),
});

export const purchasePaymentSchema = z.object({
  paidCents: centsSchema,
});

export const createExpenseSchema = z.object({
  branchId: z.string().min(1).nullable().optional(),
  category: z.string().trim().min(1).max(80),
  description: z.string().trim().min(1).max(300),
  amountCents: z.number().int().min(1).max(1_000_000_000_000),
  paymentMethod: z.enum(PAYMENT_METHODS).default("cash"),
  vendor: optionalText(160),
  reference: optionalText(120),
  expenseDate: dateMsSchema.optional(),
  note: optionalText(500),
});

export const updateExpenseSchema = z.object({
  branchId: z.string().min(1).nullable().optional(),
  category: z.string().trim().min(1).max(80).optional(),
  description: z.string().trim().min(1).max(300).optional(),
  amountCents: z.number().int().min(1).max(1_000_000_000_000).optional(),
  paymentMethod: z.enum(PAYMENT_METHODS).optional(),
  vendor: z.string().trim().max(160).nullable().optional(),
  reference: z.string().trim().max(120).nullable().optional(),
  expenseDate: dateMsSchema.optional(),
  note: z.string().trim().max(500).nullable().optional(),
  status: z.enum(["recorded", "approved", "void"]).optional(),
});

export const reportRangeSchema = z.object({
  from: z.coerce.number().int().positive().optional(),
  to: z.coerce.number().int().positive().optional(),
  branchId: z.string().min(1).optional(),
});

export const createEmployeeSchema = z.object({
  name: z.string().trim().min(1).max(160),
  branchId: z.string().min(1).nullable().optional(),
  phone: z.string().trim().max(30).optional(),
  email: optionalEmail,
  position: optionalText(120),
  department: optionalText(120),
  salaryCents: centsSchema.default(0),
  currency: currencySchema.default("USD"),
  hireDate: dateMsSchema.nullable().optional(),
  notes: optionalText(1000),
  status: z.enum(EMPLOYEE_STATUSES).optional(),
});

export const updateEmployeeSchema = z.object({
  name: z.string().trim().min(1).max(160).optional(),
  branchId: z.string().min(1).nullable().optional(),
  phone: z.string().trim().max(30).nullable().optional(),
  email: z.string().trim().toLowerCase().email().max(254).nullable().optional(),
  position: z.string().trim().max(120).nullable().optional(),
  department: z.string().trim().max(120).nullable().optional(),
  salaryCents: centsSchema.optional(),
  currency: currencySchema.optional(),
  hireDate: dateMsSchema.nullable().optional(),
  notes: z.string().trim().max(1000).nullable().optional(),
  status: z.enum(EMPLOYEE_STATUSES).optional(),
});

export const createTransferSchema = z.object({
  fromBranchId: z.string().min(1),
  toBranchId: z.string().min(1),
  note: optionalText(500),
  items: z
    .array(
      z.object({
        productId: z.string().min(1),
        quantity: z.number().int().min(1).max(100_000),
      }),
    )
    .min(1)
    .max(200),
});

export const createOrderSchema = z.object({
  branchId: z.string().min(1),
  customerId: z.string().min(1).nullable().optional(),
  customerName: optionalText(160),
  paymentMethod: z.enum(PAYMENT_METHODS).default("cash"),
  discountCents: centsSchema.default(0),
  paidCents: centsSchema.default(0),
  orderDate: dateMsSchema.optional(),
  expectedDate: dateMsSchema.nullable().optional(),
  note: optionalText(500),
  items: z
    .array(
      z.object({
        productId: z.string().min(1),
        quantity: z.number().int().min(1).max(100_000),
        unitPriceCents: centsSchema.optional(),
        discountCents: centsSchema.optional(),
      }),
    )
    .min(1)
    .max(200),
});

export const updateOrderStatusSchema = z.object({
  status: z.enum(["pending", "confirmed", "cancelled"]),
});

export const changeSubscriptionSchema = z.object({
  planId: z.string().min(1),
  billingCycle: z.enum(BILLING_CYCLES).default("monthly"),
});

export const aiChatSchema = z.object({
  message: z.string().trim().min(1).max(2_000),
  locale: localeSchema.default("en"),
  history: z
    .array(
      z.object({
        role: z.enum(["user", "assistant"]),
        content: z.string().trim().min(1).max(4_000),
      }),
    )
    .max(12)
    .default([]),
});

export const adminUpdateWorkspaceSchema = z.object({
  status: z.enum(["active", "suspended"]),
});

export const adminUpdatePlanSchema = z.object({
  name: z.string().trim().min(1).max(80).optional(),
  description: z.string().trim().max(300).nullable().optional(),
  monthlyPriceCents: z.number().int().min(0).optional(),
  yearlyPriceCents: z.number().int().min(0).optional(),
  isActive: z.boolean().optional(),
});

export const adminUpdateEntitlementSchema = z.object({
  key: z.string().trim().min(1).max(80),
  value: z.string().trim().max(2_000).nullable(),
});

export const payrollItemInputSchema = z.object({
  employeeId: z.string().min(1),
  salaryCents: centsSchema.default(0),
  allowancesCents: centsSchema.default(0),
  deductionsCents: centsSchema.default(0),
});

export const createPayrollRunSchema = z.object({
  branchId: z.string().min(1).nullable().optional(),
  periodStart: dateMsSchema,
  periodEnd: dateMsSchema,
  note: optionalText(500),
  items: z.array(payrollItemInputSchema).min(1).max(500),
});

export const payrollPaySchema = z.object({
  paidCents: centsSchema,
});
