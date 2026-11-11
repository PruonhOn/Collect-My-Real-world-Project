import { sql } from "drizzle-orm";
import {
  index,
  integer,
  primaryKey,
  sqliteTable,
  text,
  uniqueIndex,
} from "drizzle-orm/sqlite-core";

const epochMs = sql`(unixepoch() * 1000)`;

function timestamps() {
  return {
    createdAt: integer("created_at").notNull().default(epochMs),
    updatedAt: integer("updated_at").notNull().default(epochMs),
  };
}

export const users = sqliteTable(
  "users",
  {
    id: text("id").primaryKey(),
    email: text("email").notNull(),
    passwordHash: text("password_hash").notNull(),
    googleId: text("google_id"),
    name: text("name").notNull(),
    phone: text("phone"),
    locale: text("locale").notNull().default("en"),
    status: text("status").notNull().default("active"),
    emailVerifiedAt: integer("email_verified_at"),
    lastLoginAt: integer("last_login_at"),
    ...timestamps(),
  },
  (table) => [
    uniqueIndex("users_email_unique").on(table.email),
    uniqueIndex("users_google_id_unique").on(table.googleId),
  ],
);

export const sessions = sqliteTable(
  "sessions",
  {
    id: text("id").primaryKey(),
    userId: text("user_id")
      .notNull()
      .references(() => users.id, { onDelete: "cascade" }),
    tokenHash: text("token_hash").notNull(),
    ip: text("ip"),
    userAgent: text("user_agent"),
    expiresAt: integer("expires_at").notNull(),
    revokedAt: integer("revoked_at"),
    createdAt: integer("created_at").notNull().default(epochMs),
  },
  (table) => [
    uniqueIndex("sessions_token_hash_unique").on(table.tokenHash),
    index("sessions_user_idx").on(table.userId),
  ],
);

export const authTokens = sqliteTable(
  "auth_tokens",
  {
    id: text("id").primaryKey(),
    userId: text("user_id")
      .notNull()
      .references(() => users.id, { onDelete: "cascade" }),
    type: text("type").notNull(),
    tokenHash: text("token_hash").notNull(),
    expiresAt: integer("expires_at").notNull(),
    usedAt: integer("used_at"),
    createdAt: integer("created_at").notNull().default(epochMs),
  },
  (table) => [
    uniqueIndex("auth_tokens_token_hash_unique").on(table.tokenHash),
    index("auth_tokens_user_type_idx").on(table.userId, table.type),
  ],
);

export const platformAdmins = sqliteTable(
  "platform_admins",
  {
    id: text("id").primaryKey(),
    userId: text("user_id")
      .notNull()
      .references(() => users.id, { onDelete: "cascade" }),
    role: text("role").notNull().default("support_admin"),
    createdAt: integer("created_at").notNull().default(epochMs),
  },
  (table) => [uniqueIndex("platform_admins_user_unique").on(table.userId)],
);

export const workspaces = sqliteTable(
  "workspaces",
  {
    id: text("id").primaryKey(),
    name: text("name").notNull(),
    slug: text("slug").notNull(),
    ownerUserId: text("owner_user_id")
      .notNull()
      .references(() => users.id),
    defaultCurrency: text("default_currency").notNull().default("USD"),
    locale: text("locale").notNull().default("en"),
    timezone: text("timezone").notNull().default("Asia/Phnom_Penh"),
    status: text("status").notNull().default("active"),
    logoKey: text("logo_key"),
    ...timestamps(),
  },
  (table) => [
    uniqueIndex("workspaces_slug_unique").on(table.slug),
    index("workspaces_owner_idx").on(table.ownerUserId),
  ],
);

export const roles = sqliteTable(
  "roles",
  {
    id: text("id").primaryKey(),
    workspaceId: text("workspace_id").references(() => workspaces.id, {
      onDelete: "cascade",
    }),
    key: text("key").notNull(),
    name: text("name").notNull(),
    isSystem: integer("is_system", { mode: "boolean" }).notNull().default(false),
    createdAt: integer("created_at").notNull().default(epochMs),
  },
  (table) => [
    uniqueIndex("roles_workspace_key_unique").on(table.workspaceId, table.key),
  ],
);

export const permissions = sqliteTable(
  "permissions",
  {
    id: text("id").primaryKey(),
    key: text("key").notNull(),
    category: text("category").notNull(),
    description: text("description"),
    createdAt: integer("created_at").notNull().default(epochMs),
  },
  (table) => [uniqueIndex("permissions_key_unique").on(table.key)],
);

export const rolePermissions = sqliteTable(
  "role_permissions",
  {
    roleId: text("role_id")
      .notNull()
      .references(() => roles.id, { onDelete: "cascade" }),
    permissionId: text("permission_id")
      .notNull()
      .references(() => permissions.id, { onDelete: "cascade" }),
  },
  (table) => [primaryKey({ columns: [table.roleId, table.permissionId] })],
);

export const workspaceMembers = sqliteTable(
  "workspace_members",
  {
    id: text("id").primaryKey(),
    workspaceId: text("workspace_id")
      .notNull()
      .references(() => workspaces.id, { onDelete: "cascade" }),
    userId: text("user_id")
      .notNull()
      .references(() => users.id, { onDelete: "cascade" }),
    roleId: text("role_id")
      .notNull()
      .references(() => roles.id),
    status: text("status").notNull().default("active"),
    invitedByUserId: text("invited_by_user_id").references(() => users.id),
    joinedAt: integer("joined_at"),
    createdAt: integer("created_at").notNull().default(epochMs),
  },
  (table) => [
    uniqueIndex("workspace_members_workspace_user_unique").on(
      table.workspaceId,
      table.userId,
    ),
    index("workspace_members_user_idx").on(table.userId),
  ],
);

export const invitations = sqliteTable(
  "invitations",
  {
    id: text("id").primaryKey(),
    workspaceId: text("workspace_id")
      .notNull()
      .references(() => workspaces.id, { onDelete: "cascade" }),
    email: text("email").notNull(),
    roleId: text("role_id")
      .notNull()
      .references(() => roles.id),
    tokenHash: text("token_hash").notNull(),
    status: text("status").notNull().default("pending"),
    invitedByUserId: text("invited_by_user_id")
      .notNull()
      .references(() => users.id),
    expiresAt: integer("expires_at").notNull(),
    createdAt: integer("created_at").notNull().default(epochMs),
  },
  (table) => [
    uniqueIndex("invitations_token_hash_unique").on(table.tokenHash),
    index("invitations_workspace_email_idx").on(table.workspaceId, table.email),
  ],
);

export const branches = sqliteTable(
  "branches",
  {
    id: text("id").primaryKey(),
    workspaceId: text("workspace_id")
      .notNull()
      .references(() => workspaces.id, { onDelete: "cascade" }),
    name: text("name").notNull(),
    code: text("code").notNull(),
    phone: text("phone"),
    address: text("address"),
    isDefault: integer("is_default", { mode: "boolean" }).notNull().default(false),
    status: text("status").notNull().default("active"),
    ...timestamps(),
  },
  (table) => [
    uniqueIndex("branches_workspace_code_unique").on(table.workspaceId, table.code),
    index("branches_workspace_idx").on(table.workspaceId),
  ],
);

export const workspaceSettings = sqliteTable("workspace_settings", {
  workspaceId: text("workspace_id")
    .primaryKey()
    .references(() => workspaces.id, { onDelete: "cascade" }),
  taxEnabled: integer("tax_enabled", { mode: "boolean" }).notNull().default(false),
  taxRateBasisPoints: integer("tax_rate_basis_points").notNull().default(0),
  invoicePrefix: text("invoice_prefix").notNull().default("INV"),
  exchangeRateKhrPerUsd: integer("exchange_rate_khr_per_usd")
    .notNull()
    .default(4100),
  lowStockThresholdDefault: integer("low_stock_threshold_default")
    .notNull()
    .default(5),
  updatedAt: integer("updated_at").notNull().default(epochMs),
});

export const plans = sqliteTable(
  "plans",
  {
    id: text("id").primaryKey(),
    name: text("name").notNull(),
    slug: text("slug").notNull(),
    description: text("description"),
    monthlyPriceCents: integer("monthly_price_cents").notNull().default(0),
    yearlyPriceCents: integer("yearly_price_cents").notNull().default(0),
    currency: text("currency").notNull().default("USD"),
    trialDays: integer("trial_days").notNull().default(0),
    isActive: integer("is_active", { mode: "boolean" }).notNull().default(true),
    sortOrder: integer("sort_order").notNull().default(0),
    ...timestamps(),
  },
  (table) => [uniqueIndex("plans_slug_unique").on(table.slug)],
);

export const planEntitlements = sqliteTable(
  "plan_entitlements",
  {
    id: text("id").primaryKey(),
    planId: text("plan_id")
      .notNull()
      .references(() => plans.id, { onDelete: "cascade" }),
    key: text("key").notNull(),
    value: text("value").notNull(),
  },
  (table) => [
    uniqueIndex("plan_entitlements_plan_key_unique").on(table.planId, table.key),
  ],
);

export const subscriptions = sqliteTable(
  "subscriptions",
  {
    id: text("id").primaryKey(),
    workspaceId: text("workspace_id")
      .notNull()
      .references(() => workspaces.id, { onDelete: "cascade" }),
    planId: text("plan_id")
      .notNull()
      .references(() => plans.id),
    status: text("status").notNull().default("trialing"),
    billingCycle: text("billing_cycle").notNull().default("monthly"),
    currentPeriodStart: integer("current_period_start"),
    currentPeriodEnd: integer("current_period_end"),
    trialEndsAt: integer("trial_ends_at"),
    graceEndsAt: integer("grace_ends_at"),
    cancelAtPeriodEnd: integer("cancel_at_period_end", { mode: "boolean" })
      .notNull()
      .default(false),
    ...timestamps(),
  },
  (table) => [
    uniqueIndex("subscriptions_workspace_unique").on(table.workspaceId),
  ],
);

export const usageCounters = sqliteTable(
  "usage_counters",
  {
    id: text("id").primaryKey(),
    workspaceId: text("workspace_id")
      .notNull()
      .references(() => workspaces.id, { onDelete: "cascade" }),
    metric: text("metric").notNull(),
    period: text("period").notNull(),
    used: integer("used").notNull().default(0),
    updatedAt: integer("updated_at").notNull().default(epochMs),
  },
  (table) => [
    uniqueIndex("usage_counters_workspace_metric_period_unique").on(
      table.workspaceId,
      table.metric,
      table.period,
    ),
  ],
);

export const rateLimits = sqliteTable("rate_limits", {
  key: text("key").primaryKey(),
  count: integer("count").notNull().default(0),
  resetAt: integer("reset_at").notNull(),
});

export const auditLogs = sqliteTable(
  "audit_logs",
  {
    id: text("id").primaryKey(),
    workspaceId: text("workspace_id").references(() => workspaces.id, {
      onDelete: "set null",
    }),
    actorUserId: text("actor_user_id").references(() => users.id, {
      onDelete: "set null",
    }),
    action: text("action").notNull(),
    entityType: text("entity_type"),
    entityId: text("entity_id"),
    metadata: text("metadata"),
    ip: text("ip"),
    userAgent: text("user_agent"),
    createdAt: integer("created_at").notNull().default(epochMs),
  },
  (table) => [
    index("audit_logs_workspace_created_idx").on(
      table.workspaceId,
      table.createdAt,
    ),
    index("audit_logs_actor_idx").on(table.actorUserId),
  ],
);

export const notifications = sqliteTable(
  "notifications",
  {
    id: text("id").primaryKey(),
    workspaceId: text("workspace_id")
      .notNull()
      .references(() => workspaces.id, { onDelete: "cascade" }),
    userId: text("user_id").references(() => users.id, { onDelete: "cascade" }),
    type: text("type").notNull(),
    title: text("title").notNull(),
    body: text("body"),
    channel: text("channel").notNull().default("in_app"),
    status: text("status").notNull().default("pending"),
    readAt: integer("read_at"),
    createdAt: integer("created_at").notNull().default(epochMs),
  },
  (table) => [
    index("notifications_workspace_user_idx").on(table.workspaceId, table.userId),
  ],
);

export const categories = sqliteTable(
  "categories",
  {
    id: text("id").primaryKey(),
    workspaceId: text("workspace_id")
      .notNull()
      .references(() => workspaces.id, { onDelete: "cascade" }),
    name: text("name").notNull(),
    description: text("description"),
    ...timestamps(),
  },
  (table) => [
    uniqueIndex("categories_workspace_name_unique").on(
      table.workspaceId,
      table.name,
    ),
    index("categories_workspace_idx").on(table.workspaceId),
  ],
);

export const products = sqliteTable(
  "products",
  {
    id: text("id").primaryKey(),
    workspaceId: text("workspace_id")
      .notNull()
      .references(() => workspaces.id, { onDelete: "cascade" }),
    categoryId: text("category_id").references(() => categories.id, {
      onDelete: "set null",
    }),
    name: text("name").notNull(),
    sku: text("sku").notNull(),
    barcode: text("barcode"),
    description: text("description"),
    unit: text("unit").notNull().default("pcs"),
    costPriceCents: integer("cost_price_cents").notNull().default(0),
    sellingPriceCents: integer("selling_price_cents").notNull().default(0),
    taxRateBasisPoints: integer("tax_rate_basis_points"),
    trackInventory: integer("track_inventory", { mode: "boolean" })
      .notNull()
      .default(true),
    lowStockThreshold: integer("low_stock_threshold").notNull().default(5),
    status: text("status").notNull().default("active"),
    ...timestamps(),
  },
  (table) => [
    uniqueIndex("products_workspace_sku_unique").on(
      table.workspaceId,
      table.sku,
    ),
    uniqueIndex("products_workspace_barcode_unique").on(
      table.workspaceId,
      table.barcode,
    ),
    index("products_workspace_status_idx").on(table.workspaceId, table.status),
    index("products_category_idx").on(table.categoryId),
  ],
);

export const inventoryLevels = sqliteTable(
  "inventory_levels",
  {
    id: text("id").primaryKey(),
    workspaceId: text("workspace_id")
      .notNull()
      .references(() => workspaces.id, { onDelete: "cascade" }),
    branchId: text("branch_id")
      .notNull()
      .references(() => branches.id, { onDelete: "cascade" }),
    productId: text("product_id")
      .notNull()
      .references(() => products.id, { onDelete: "cascade" }),
    quantity: integer("quantity").notNull().default(0),
    updatedAt: integer("updated_at").notNull().default(epochMs),
  },
  (table) => [
    uniqueIndex("inventory_levels_branch_product_unique").on(
      table.branchId,
      table.productId,
    ),
    index("inventory_levels_workspace_idx").on(table.workspaceId),
  ],
);

export const stockMovements = sqliteTable(
  "stock_movements",
  {
    id: text("id").primaryKey(),
    workspaceId: text("workspace_id")
      .notNull()
      .references(() => workspaces.id, { onDelete: "cascade" }),
    branchId: text("branch_id")
      .notNull()
      .references(() => branches.id, { onDelete: "cascade" }),
    productId: text("product_id")
      .notNull()
      .references(() => products.id, { onDelete: "cascade" }),
    type: text("type").notNull(),
    quantity: integer("quantity").notNull(),
    unitCostCents: integer("unit_cost_cents"),
    referenceType: text("reference_type"),
    referenceId: text("reference_id"),
    note: text("note"),
    createdByUserId: text("created_by_user_id").references(() => users.id, {
      onDelete: "set null",
    }),
    createdAt: integer("created_at").notNull().default(epochMs),
  },
  (table) => [
    index("stock_movements_workspace_created_idx").on(
      table.workspaceId,
      table.createdAt,
    ),
    index("stock_movements_branch_product_idx").on(
      table.branchId,
      table.productId,
    ),
  ],
);

export const customers = sqliteTable(
  "customers",
  {
    id: text("id").primaryKey(),
    workspaceId: text("workspace_id")
      .notNull()
      .references(() => workspaces.id, { onDelete: "cascade" }),
    name: text("name").notNull(),
    phone: text("phone"),
    email: text("email"),
    address: text("address"),
    type: text("type").notNull().default("individual"),
    creditLimitCents: integer("credit_limit_cents").notNull().default(0),
    notes: text("notes"),
    status: text("status").notNull().default("active"),
    ...timestamps(),
  },
  (table) => [
    index("customers_workspace_idx").on(table.workspaceId),
    index("customers_workspace_phone_idx").on(table.workspaceId, table.phone),
  ],
);

export const suppliers = sqliteTable(
  "suppliers",
  {
    id: text("id").primaryKey(),
    workspaceId: text("workspace_id")
      .notNull()
      .references(() => workspaces.id, { onDelete: "cascade" }),
    name: text("name").notNull(),
    contactName: text("contact_name"),
    phone: text("phone"),
    email: text("email"),
    address: text("address"),
    notes: text("notes"),
    status: text("status").notNull().default("active"),
    ...timestamps(),
  },
  (table) => [index("suppliers_workspace_idx").on(table.workspaceId)],
);

export const sales = sqliteTable(
  "sales",
  {
    id: text("id").primaryKey(),
    workspaceId: text("workspace_id")
      .notNull()
      .references(() => workspaces.id, { onDelete: "cascade" }),
    branchId: text("branch_id")
      .notNull()
      .references(() => branches.id),
    customerId: text("customer_id").references(() => customers.id, {
      onDelete: "set null",
    }),
    customerName: text("customer_name"),
    number: text("number").notNull(),
    status: text("status").notNull().default("completed"),
    paymentStatus: text("payment_status").notNull().default("unpaid"),
    paymentMethod: text("payment_method").notNull().default("cash"),
    currency: text("currency").notNull().default("USD"),
    exchangeRateKhrPerUsd: integer("exchange_rate_khr_per_usd")
      .notNull()
      .default(4100),
    subtotalCents: integer("subtotal_cents").notNull().default(0),
    discountCents: integer("discount_cents").notNull().default(0),
    taxCents: integer("tax_cents").notNull().default(0),
    totalCents: integer("total_cents").notNull().default(0),
    paidCents: integer("paid_cents").notNull().default(0),
    balanceCents: integer("balance_cents").notNull().default(0),
    saleDate: integer("sale_date").notNull().default(epochMs),
    dueDate: integer("due_date"),
    note: text("note"),
    createdByUserId: text("created_by_user_id").references(() => users.id, {
      onDelete: "set null",
    }),
    voidedAt: integer("voided_at"),
    ...timestamps(),
  },
  (table) => [
    uniqueIndex("sales_workspace_number_unique").on(
      table.workspaceId,
      table.number,
    ),
    index("sales_workspace_date_idx").on(table.workspaceId, table.saleDate),
    index("sales_customer_idx").on(table.customerId),
    index("sales_branch_idx").on(table.branchId),
  ],
);

export const saleItems = sqliteTable(
  "sale_items",
  {
    id: text("id").primaryKey(),
    workspaceId: text("workspace_id")
      .notNull()
      .references(() => workspaces.id, { onDelete: "cascade" }),
    saleId: text("sale_id")
      .notNull()
      .references(() => sales.id, { onDelete: "cascade" }),
    productId: text("product_id").references(() => products.id, {
      onDelete: "set null",
    }),
    productName: text("product_name").notNull(),
    sku: text("sku"),
    quantity: integer("quantity").notNull(),
    unitPriceCents: integer("unit_price_cents").notNull(),
    costPriceCents: integer("cost_price_cents").notNull().default(0),
    discountCents: integer("discount_cents").notNull().default(0),
    taxRateBasisPoints: integer("tax_rate_basis_points").notNull().default(0),
    subtotalCents: integer("subtotal_cents").notNull().default(0),
    taxCents: integer("tax_cents").notNull().default(0),
    totalCents: integer("total_cents").notNull().default(0),
    createdAt: integer("created_at").notNull().default(epochMs),
  },
  (table) => [
    index("sale_items_sale_idx").on(table.saleId),
    index("sale_items_product_idx").on(table.productId),
  ],
);

export const purchases = sqliteTable(
  "purchases",
  {
    id: text("id").primaryKey(),
    workspaceId: text("workspace_id")
      .notNull()
      .references(() => workspaces.id, { onDelete: "cascade" }),
    branchId: text("branch_id")
      .notNull()
      .references(() => branches.id),
    supplierId: text("supplier_id").references(() => suppliers.id, {
      onDelete: "set null",
    }),
    supplierName: text("supplier_name"),
    number: text("number").notNull(),
    status: text("status").notNull().default("received"),
    paymentStatus: text("payment_status").notNull().default("unpaid"),
    paymentMethod: text("payment_method").notNull().default("cash"),
    currency: text("currency").notNull().default("USD"),
    subtotalCents: integer("subtotal_cents").notNull().default(0),
    discountCents: integer("discount_cents").notNull().default(0),
    taxCents: integer("tax_cents").notNull().default(0),
    totalCents: integer("total_cents").notNull().default(0),
    paidCents: integer("paid_cents").notNull().default(0),
    balanceCents: integer("balance_cents").notNull().default(0),
    purchaseDate: integer("purchase_date").notNull().default(epochMs),
    note: text("note"),
    stockApplied: integer("stock_applied", { mode: "boolean" })
      .notNull()
      .default(true),
    createdByUserId: text("created_by_user_id").references(() => users.id, {
      onDelete: "set null",
    }),
    ...timestamps(),
  },
  (table) => [
    uniqueIndex("purchases_workspace_number_unique").on(
      table.workspaceId,
      table.number,
    ),
    index("purchases_workspace_date_idx").on(
      table.workspaceId,
      table.purchaseDate,
    ),
    index("purchases_supplier_idx").on(table.supplierId),
  ],
);

export const purchaseItems = sqliteTable(
  "purchase_items",
  {
    id: text("id").primaryKey(),
    workspaceId: text("workspace_id")
      .notNull()
      .references(() => workspaces.id, { onDelete: "cascade" }),
    purchaseId: text("purchase_id")
      .notNull()
      .references(() => purchases.id, { onDelete: "cascade" }),
    productId: text("product_id").references(() => products.id, {
      onDelete: "set null",
    }),
    productName: text("product_name").notNull(),
    sku: text("sku"),
    quantity: integer("quantity").notNull(),
    unitCostCents: integer("unit_cost_cents").notNull(),
    subtotalCents: integer("subtotal_cents").notNull().default(0),
    createdAt: integer("created_at").notNull().default(epochMs),
  },
  (table) => [
    index("purchase_items_purchase_idx").on(table.purchaseId),
    index("purchase_items_product_idx").on(table.productId),
  ],
);

export const expenses = sqliteTable(
  "expenses",
  {
    id: text("id").primaryKey(),
    workspaceId: text("workspace_id")
      .notNull()
      .references(() => workspaces.id, { onDelete: "cascade" }),
    branchId: text("branch_id").references(() => branches.id, {
      onDelete: "set null",
    }),
    category: text("category").notNull(),
    description: text("description").notNull(),
    amountCents: integer("amount_cents").notNull(),
    currency: text("currency").notNull().default("USD"),
    paymentMethod: text("payment_method").notNull().default("cash"),
    vendor: text("vendor"),
    reference: text("reference"),
    note: text("note"),
    expenseDate: integer("expense_date").notNull().default(epochMs),
    status: text("status").notNull().default("recorded"),
    createdByUserId: text("created_by_user_id").references(() => users.id, {
      onDelete: "set null",
    }),
    ...timestamps(),
  },
  (table) => [
    index("expenses_workspace_date_idx").on(
      table.workspaceId,
      table.expenseDate,
    ),
    index("expenses_category_idx").on(table.workspaceId, table.category),
  ],
);

export const employees = sqliteTable(
  "employees",
  {
    id: text("id").primaryKey(),
    workspaceId: text("workspace_id")
      .notNull()
      .references(() => workspaces.id, { onDelete: "cascade" }),
    branchId: text("branch_id").references(() => branches.id, {
      onDelete: "set null",
    }),
    name: text("name").notNull(),
    phone: text("phone"),
    email: text("email"),
    position: text("position"),
    department: text("department"),
    salaryCents: integer("salary_cents").notNull().default(0),
    currency: text("currency").notNull().default("USD"),
    hireDate: integer("hire_date"),
    status: text("status").notNull().default("active"),
    notes: text("notes"),
    ...timestamps(),
  },
  (table) => [
    index("employees_workspace_idx").on(table.workspaceId),
    index("employees_branch_idx").on(table.branchId),
    index("employees_workspace_status_idx").on(
      table.workspaceId,
      table.status,
    ),
  ],
);

export const payrollRuns = sqliteTable(
  "payroll_runs",
  {
    id: text("id").primaryKey(),
    workspaceId: text("workspace_id")
      .notNull()
      .references(() => workspaces.id, { onDelete: "cascade" }),
    branchId: text("branch_id").references(() => branches.id, {
      onDelete: "set null",
    }),
    number: text("number").notNull(),
    periodStart: integer("period_start").notNull(),
    periodEnd: integer("period_end").notNull(),
    status: text("status").notNull().default("draft"),
    totalCents: integer("total_cents").notNull().default(0),
    paidCents: integer("paid_cents").notNull().default(0),
    paidAt: integer("paid_at"),
    note: text("note"),
    createdByUserId: text("created_by_user_id").references(() => users.id, {
      onDelete: "set null",
    }),
    ...timestamps(),
  },
  (table) => [
    uniqueIndex("payroll_runs_workspace_number_unique").on(
      table.workspaceId,
      table.number,
    ),
    index("payroll_runs_workspace_idx").on(table.workspaceId),
  ],
);

export const payrollItems = sqliteTable(
  "payroll_items",
  {
    id: text("id").primaryKey(),
    workspaceId: text("workspace_id")
      .notNull()
      .references(() => workspaces.id, { onDelete: "cascade" }),
    runId: text("run_id")
      .notNull()
      .references(() => payrollRuns.id, { onDelete: "cascade" }),
    employeeId: text("employee_id")
      .notNull()
      .references(() => employees.id, { onDelete: "cascade" }),
    salaryCents: integer("salary_cents").notNull().default(0),
    allowancesCents: integer("allowances_cents").notNull().default(0),
    deductionsCents: integer("deductions_cents").notNull().default(0),
    netCents: integer("net_cents").notNull().default(0),
    paidAt: integer("paid_at"),
    ...timestamps(),
  },
  (table) => [
    index("payroll_items_run_idx").on(table.runId),
    index("payroll_items_employee_idx").on(table.employeeId),
  ],
);

export const stockTransfers = sqliteTable(
  "stock_transfers",
  {
    id: text("id").primaryKey(),
    workspaceId: text("workspace_id")
      .notNull()
      .references(() => workspaces.id, { onDelete: "cascade" }),
    fromBranchId: text("from_branch_id")
      .notNull()
      .references(() => branches.id),
    toBranchId: text("to_branch_id")
      .notNull()
      .references(() => branches.id),
    number: text("number").notNull(),
    status: text("status").notNull().default("pending"),
    note: text("note"),
    createdByUserId: text("created_by_user_id").references(() => users.id, {
      onDelete: "set null",
    }),
    completedAt: integer("completed_at"),
    ...timestamps(),
  },
  (table) => [
    uniqueIndex("stock_transfers_workspace_number_unique").on(
      table.workspaceId,
      table.number,
    ),
    index("stock_transfers_workspace_created_idx").on(
      table.workspaceId,
      table.createdAt,
    ),
    index("stock_transfers_from_idx").on(table.fromBranchId),
    index("stock_transfers_to_idx").on(table.toBranchId),
  ],
);

export const stockTransferItems = sqliteTable(
  "stock_transfer_items",
  {
    id: text("id").primaryKey(),
    workspaceId: text("workspace_id")
      .notNull()
      .references(() => workspaces.id, { onDelete: "cascade" }),
    transferId: text("transfer_id")
      .notNull()
      .references(() => stockTransfers.id, { onDelete: "cascade" }),
    productId: text("product_id").references(() => products.id, {
      onDelete: "set null",
    }),
    productName: text("product_name").notNull(),
    sku: text("sku"),
    quantity: integer("quantity").notNull(),
    createdAt: integer("created_at").notNull().default(epochMs),
  },
  (table) => [
    index("stock_transfer_items_transfer_idx").on(table.transferId),
    index("stock_transfer_items_product_idx").on(table.productId),
  ],
);

export const orders = sqliteTable(
  "orders",
  {
    id: text("id").primaryKey(),
    workspaceId: text("workspace_id")
      .notNull()
      .references(() => workspaces.id, { onDelete: "cascade" }),
    branchId: text("branch_id")
      .notNull()
      .references(() => branches.id),
    customerId: text("customer_id").references(() => customers.id, {
      onDelete: "set null",
    }),
    customerName: text("customer_name"),
    number: text("number").notNull(),
    status: text("status").notNull().default("pending"),
    paymentMethod: text("payment_method").notNull().default("cash"),
    currency: text("currency").notNull().default("USD"),
    subtotalCents: integer("subtotal_cents").notNull().default(0),
    discountCents: integer("discount_cents").notNull().default(0),
    taxCents: integer("tax_cents").notNull().default(0),
    totalCents: integer("total_cents").notNull().default(0),
    paidCents: integer("paid_cents").notNull().default(0),
    orderDate: integer("order_date").notNull().default(epochMs),
    expectedDate: integer("expected_date"),
    note: text("note"),
    saleId: text("sale_id").references(() => sales.id, { onDelete: "set null" }),
    createdByUserId: text("created_by_user_id").references(() => users.id, {
      onDelete: "set null",
    }),
    ...timestamps(),
  },
  (table) => [
    uniqueIndex("orders_workspace_number_unique").on(
      table.workspaceId,
      table.number,
    ),
    index("orders_workspace_date_idx").on(table.workspaceId, table.orderDate),
    index("orders_customer_idx").on(table.customerId),
    index("orders_branch_idx").on(table.branchId),
    index("orders_workspace_status_idx").on(table.workspaceId, table.status),
  ],
);

export const orderItems = sqliteTable(
  "order_items",
  {
    id: text("id").primaryKey(),
    workspaceId: text("workspace_id")
      .notNull()
      .references(() => workspaces.id, { onDelete: "cascade" }),
    orderId: text("order_id")
      .notNull()
      .references(() => orders.id, { onDelete: "cascade" }),
    productId: text("product_id").references(() => products.id, {
      onDelete: "set null",
    }),
    productName: text("product_name").notNull(),
    sku: text("sku"),
    quantity: integer("quantity").notNull(),
    unitPriceCents: integer("unit_price_cents").notNull(),
    costPriceCents: integer("cost_price_cents").notNull().default(0),
    discountCents: integer("discount_cents").notNull().default(0),
    taxRateBasisPoints: integer("tax_rate_basis_points").notNull().default(0),
    subtotalCents: integer("subtotal_cents").notNull().default(0),
    taxCents: integer("tax_cents").notNull().default(0),
    totalCents: integer("total_cents").notNull().default(0),
    createdAt: integer("created_at").notNull().default(epochMs),
  },
  (table) => [
    index("order_items_order_idx").on(table.orderId),
    index("order_items_product_idx").on(table.productId),
  ],
);

export type User = typeof users.$inferSelect;
export type NewUser = typeof users.$inferInsert;
export type Workspace = typeof workspaces.$inferSelect;
export type NewWorkspace = typeof workspaces.$inferInsert;
export type Plan = typeof plans.$inferSelect;
export type Subscription = typeof subscriptions.$inferSelect;
export type Role = typeof roles.$inferSelect;
export type Branch = typeof branches.$inferSelect;
export type AuditLog = typeof auditLogs.$inferSelect;
export type Category = typeof categories.$inferSelect;
export type Product = typeof products.$inferSelect;
export type InventoryLevel = typeof inventoryLevels.$inferSelect;
export type StockMovement = typeof stockMovements.$inferSelect;
export type Customer = typeof customers.$inferSelect;
export type Supplier = typeof suppliers.$inferSelect;
export type Sale = typeof sales.$inferSelect;
export type SaleItem = typeof saleItems.$inferSelect;
export type Purchase = typeof purchases.$inferSelect;
export type PurchaseItem = typeof purchaseItems.$inferSelect;
export type Expense = typeof expenses.$inferSelect;
export type Employee = typeof employees.$inferSelect;
export type NewEmployee = typeof employees.$inferInsert;
export type StockTransfer = typeof stockTransfers.$inferSelect;
export type StockTransferItem = typeof stockTransferItems.$inferSelect;
export type Order = typeof orders.$inferSelect;
export type OrderItem = typeof orderItems.$inferSelect;
export type PayrollRun = typeof payrollRuns.$inferSelect;
export type NewPayrollRun = typeof payrollRuns.$inferInsert;
export type PayrollItem = typeof payrollItems.$inferSelect;
export type NewPayrollItem = typeof payrollItems.$inferInsert;
