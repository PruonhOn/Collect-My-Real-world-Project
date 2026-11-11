import type { Branch } from "./auth-api";
import { ApiClientError, apiRequest } from "./api";

export type Category = {
  id: string;
  workspaceId: string;
  name: string;
  description: string | null;
  createdAt: number;
  updatedAt: number;
  productCount?: number;
};

export type Product = {
  id: string;
  workspaceId: string;
  categoryId: string | null;
  name: string;
  sku: string;
  barcode: string | null;
  description: string | null;
  unit: string;
  costPriceCents: number;
  sellingPriceCents: number;
  taxRateBasisPoints: number | null;
  trackInventory: boolean;
  lowStockThreshold: number;
  status: string;
  createdAt: number;
  updatedAt: number;
  categoryName?: string | null;
  totalStock?: number;
  lowStock?: boolean;
};

export type StockLevel = {
  levelId: string;
  branchId: string;
  branchName: string;
  branchCode: string;
  productId: string;
  productName: string;
  sku: string;
  unit: string;
  quantity: number;
  lowStockThreshold: number;
  trackInventory: boolean;
  updatedAt: number;
  lowStock: boolean;
};

export type StockMovement = {
  id: string;
  type: string;
  quantity: number;
  note: string | null;
  createdAt: number;
  branchName: string;
  productName?: string;
  actorName: string | null;
};

export type Customer = {
  id: string;
  workspaceId: string;
  name: string;
  phone: string | null;
  email: string | null;
  address: string | null;
  type: string;
  creditLimitCents: number;
  notes: string | null;
  status: string;
  createdAt: number;
  updatedAt: number;
  outstandingCents?: number;
  totalSalesCents?: number;
  saleCount?: number;
};

export type Supplier = {
  id: string;
  workspaceId: string;
  name: string;
  contactName: string | null;
  phone: string | null;
  email: string | null;
  address: string | null;
  notes: string | null;
  status: string;
  createdAt: number;
  updatedAt: number;
  totalPurchasesCents?: number;
  outstandingCents?: number;
  purchaseCount?: number;
};

export type SaleSummary = {
  id: string;
  number: string;
  status: string;
  paymentStatus: string;
  paymentMethod: string;
  customerId: string | null;
  customerName: string | null;
  branchId: string;
  branchName: string;
  currency: string;
  subtotalCents: number;
  discountCents: number;
  taxCents: number;
  totalCents: number;
  paidCents: number;
  balanceCents: number;
  saleDate: number;
  dueDate: number | null;
  createdAt: number;
  itemCount: number;
};

export type SaleItem = {
  id: string;
  saleId: string;
  productId: string | null;
  productName: string;
  sku: string | null;
  quantity: number;
  unitPriceCents: number;
  costPriceCents: number;
  discountCents: number;
  taxRateBasisPoints: number;
  subtotalCents: number;
  taxCents: number;
  totalCents: number;
};

export type SaleDetail = {
  sale: Omit<SaleSummary, "branchName" | "itemCount"> & {
    branchId: string;
    customerName: string | null;
    note: string | null;
    createdByUserId: string | null;
    voidedAt: number | null;
    exchangeRateKhrPerUsd: number;
    updatedAt: number;
  };
  branch: { name: string; phone: string | null; address: string | null };
  createdByName: string | null;
  items: SaleItem[];
};

export type PurchaseSummary = {
  id: string;
  number: string;
  status: string;
  paymentStatus: string;
  paymentMethod: string;
  supplierId: string | null;
  supplierName: string | null;
  branchId: string;
  branchName: string;
  currency: string;
  subtotalCents: number;
  totalCents: number;
  paidCents: number;
  balanceCents: number;
  purchaseDate: number;
  createdAt: number;
  itemCount: number;
};

export type PurchaseItem = {
  id: string;
  purchaseId: string;
  productId: string | null;
  productName: string;
  sku: string | null;
  quantity: number;
  unitCostCents: number;
  subtotalCents: number;
};

export type Expense = {
  id: string;
  category: string;
  description: string;
  amountCents: number;
  currency: string;
  paymentMethod: string;
  vendor: string | null;
  reference: string | null;
  expenseDate: number;
  status: string;
  branchId: string | null;
  branchName: string | null;
  createdByName: string | null;
  createdAt: number;
};

export type DashboardData = {
  stats: {
    revenueCents: number;
    revenueChangePercent: number;
    salesCount: number;
    expenseCents: number;
    netProfitCents: number;
    outstandingCents: number;
    activeProducts: number;
    lowStockCount: number;
  };
  series: { date: string; salesCents: number; profitCents: number }[];
  lowStock: {
    productId: string;
    productName: string;
    quantity: number;
    threshold: number;
    branchName: string;
    unit: string;
  }[];
  recentSales: {
    id: string;
    number: string;
    customerName: string | null;
    totalCents: number;
    status: string;
    saleDate: number;
  }[];
  topProducts: {
    productId: string | null;
    productName: string;
    quantity: number;
    revenueCents: number;
  }[];
};

export type Employee = {
  id: string;
  workspaceId: string;
  branchId: string | null;
  branchName?: string | null;
  name: string;
  phone: string | null;
  email: string | null;
  position: string | null;
  department: string | null;
  salaryCents: number;
  currency: string;
  hireDate: number | null;
  status: string;
  notes: string | null;
  createdAt: number;
  updatedAt: number;
};

export type EmployeeInput = {
  name: string;
  branchId?: string | null;
  phone?: string | null;
  email?: string | null;
  position?: string | null;
  department?: string | null;
  salaryCents?: number;
  currency?: string;
  hireDate?: number | null;
  notes?: string | null;
  status?: string;
};

export type EmployeeListResponse = {
  employees: Employee[];
  summary: {
    count: number;
    activeCount: number;
    monthlySalaryCents: number;
  };
};

export type TransferSummary = {
  id: string;
  number: string;
  status: string;
  note: string | null;
  fromBranchId: string;
  fromBranchName: string;
  toBranchId: string;
  toBranchName: string;
  completedAt: number | null;
  createdAt: number;
  updatedAt: number;
  itemCount: number;
  totalQuantity: number;
};

export type TransferItem = {
  id: string;
  transferId: string;
  productId: string | null;
  productName: string;
  sku: string | null;
  quantity: number;
};

export type TransferDetail = {
  transfer: Omit<
    TransferSummary,
    "fromBranchName" | "toBranchName" | "itemCount" | "totalQuantity"
  > & {
    workspaceId: string;
    createdByUserId: string | null;
  };
  fromBranchName: string;
  toBranchName: string;
  items: TransferItem[];
};

export type TransferListResponse = {
  transfers: TransferSummary[];
  summary: { pending: number; completed: number; count: number };
};

export type OrderSummary = {
  id: string;
  number: string;
  status: string;
  paymentMethod: string;
  customerId: string | null;
  customerName: string | null;
  branchId: string;
  branchName: string;
  currency: string;
  subtotalCents: number;
  discountCents: number;
  taxCents: number;
  totalCents: number;
  paidCents: number;
  orderDate: number;
  expectedDate: number | null;
  saleId: string | null;
  createdAt: number;
  itemCount: number;
};

export type OrderItem = {
  id: string;
  orderId: string;
  productId: string | null;
  productName: string;
  sku: string | null;
  quantity: number;
  unitPriceCents: number;
  costPriceCents: number;
  discountCents: number;
  taxRateBasisPoints: number;
  subtotalCents: number;
  taxCents: number;
  totalCents: number;
};

export type OrderDetail = {
  order: Omit<OrderSummary, "branchName" | "itemCount"> & {
    workspaceId: string;
    note: string | null;
    createdByUserId: string | null;
    updatedAt: number;
  };
  branchName: string;
  items: OrderItem[];
};

export type OrderListResponse = {
  orders: OrderSummary[];
  summary: {
    count: number;
    pending: number;
    confirmed: number;
    fulfilled: number;
    totalCents: number;
  };
};

export type PlanEntitlements = Record<string, string>;

export type SubscriptionPlan = {
  id: string;
  name: string;
  slug: string;
  description: string | null;
  monthlyPriceCents: number;
  yearlyPriceCents: number;
  currency: string;
  trialDays: number;
  isActive: boolean;
  sortOrder: number;
  createdAt: number;
  updatedAt: number;
  entitlements: PlanEntitlements;
};

export type SubscriptionLimits = {
  maxProducts: number | null;
  maxEmployees: number | null;
  maxBranches: number | null;
  maxTransactionsPerMonth: number | null;
  maxWorkspaces: number | null;
  storageMb: number | null;
  aiCreditsPerMonth: number | null;
  modules: string[];
};

export type SubscriptionUsage = {
  products: number;
  employees: number;
  branches: number;
  transactionsThisMonth: number;
};

export type SubscriptionRecord = {
  id: string;
  workspaceId: string;
  planId: string;
  status: string;
  billingCycle: string;
  currentPeriodStart: number | null;
  currentPeriodEnd: number | null;
  trialEndsAt: number | null;
  graceEndsAt: number | null;
  cancelAtPeriodEnd: boolean;
  createdAt: number;
  updatedAt: number;
};

export type SubscriptionResponse = {
  subscription: {
    subscription: SubscriptionRecord;
    plan: SubscriptionPlan;
  } | null;
  plans: SubscriptionPlan[];
  limits: SubscriptionLimits;
  usage: SubscriptionUsage;
};

export type ReportData = {
  range: { from: number; to: number; branchId: string | null };
  summary: {
    revenueCents: number;
    costCents: number;
    grossProfitCents: number;
    expenseCents: number;
    netProfitCents: number;
    purchaseCents: number;
    salesCount: number;
    averageSaleCents: number;
    outstandingCents: number;
    customerCount: number;
  };
  series: { date: string; salesCents: number; expenseCents: number }[];
  topProducts: {
    productName: string;
    quantity: number;
    revenueCents: number;
  }[];
  expenseByCategory: { category: string; amountCents: number }[];
  paymentBreakdown: { method: string; totalCents: number }[];
};

function withQuery(path: string, params: Record<string, string | number | undefined>) {
  const search = new URLSearchParams();
  for (const [key, value] of Object.entries(params)) {
    if (value !== undefined && value !== "") search.set(key, String(value));
  }
  const suffix = search.toString();
  return suffix ? `${path}?${suffix}` : path;
}

export function fetchCategories(workspaceId: string) {
  return apiRequest<{ categories: Category[] }>(
    `/workspaces/${workspaceId}/categories`,
  );
}

export function createCategory(
  workspaceId: string,
  input: { name: string; description?: string },
) {
  return apiRequest<{ category: Category }>(
    `/workspaces/${workspaceId}/categories`,
    { method: "POST", body: JSON.stringify(input) },
  );
}

export function updateCategory(
  workspaceId: string,
  categoryId: string,
  input: { name?: string; description?: string },
) {
  return apiRequest<{ category: Category }>(
    `/workspaces/${workspaceId}/categories/${categoryId}`,
    { method: "PATCH", body: JSON.stringify(input) },
  );
}

export function deleteCategory(workspaceId: string, categoryId: string) {
  return apiRequest<{ deleted: boolean }>(
    `/workspaces/${workspaceId}/categories/${categoryId}`,
    { method: "DELETE" },
  );
}

export function fetchProducts(
  workspaceId: string,
  params: { search?: string; status?: string } = {},
) {
  return apiRequest<{ products: Product[] }>(
    withQuery(`/workspaces/${workspaceId}/products`, params),
  );
}

export function fetchProduct(workspaceId: string, productId: string) {
  return apiRequest<{
    product: Product;
    levels: {
      id: string;
      branchId: string;
      branchName: string;
      branchCode: string;
      quantity: number;
      updatedAt: number;
    }[];
    movements: StockMovement[];
  }>(`/workspaces/${workspaceId}/products/${productId}`);
}

export type ProductInput = {
  name: string;
  sku: string;
  barcode?: string;
  categoryId?: string | null;
  description?: string;
  unit: string;
  costPriceCents: number;
  sellingPriceCents: number;
  taxRateBasisPoints?: number | null;
  trackInventory: boolean;
  lowStockThreshold: number;
  openingStock?: number;
};

export function createProduct(workspaceId: string, input: ProductInput) {
  return apiRequest<{ product: Product }>(
    `/workspaces/${workspaceId}/products`,
    { method: "POST", body: JSON.stringify(input) },
  );
}

export function updateProduct(
  workspaceId: string,
  productId: string,
  input: Partial<ProductInput> & { status?: string },
) {
  return apiRequest<{ product: Product }>(
    `/workspaces/${workspaceId}/products/${productId}`,
    { method: "PATCH", body: JSON.stringify(input) },
  );
}

export function deactivateProduct(workspaceId: string, productId: string) {
  return apiRequest<{ deactivated: boolean }>(
    `/workspaces/${workspaceId}/products/${productId}`,
    { method: "DELETE" },
  );
}

export type InventoryResponse = {
  levels: StockLevel[];
  movements: StockMovement[];
  branches: Branch[];
  summary: { lowStockCount: number; totalUnits: number };
};

export function fetchBranches(workspaceId: string) {
  return apiRequest<{ branches: Branch[] }>(
    `/workspaces/${workspaceId}/branches`,
  );
}

export type WorkspaceSettings = {
  taxEnabled: boolean;
  taxRateBasisPoints: number;
  invoicePrefix: string;
  exchangeRateKhrPerUsd: number;
  lowStockThresholdDefault: number;
};

export function fetchWorkspaceSettings(workspaceId: string) {
  return apiRequest<{ settings: WorkspaceSettings | null }>(
    `/workspaces/${workspaceId}`,
  );
}

export function fetchInventory(
  workspaceId: string,
  params: { branchId?: string; search?: string } = {},
) {
  return apiRequest<InventoryResponse>(
    withQuery(`/workspaces/${workspaceId}/inventory`, params),
  );
}

export function adjustStock(
  workspaceId: string,
  input: {
    branchId: string;
    productId: string;
    quantity: number;
    note?: string;
  },
) {
  return apiRequest<{
    productId: string;
    branchId: string;
    previous: number;
    quantity: number;
  }>(`/workspaces/${workspaceId}/inventory/adjust`, {
    method: "POST",
    body: JSON.stringify(input),
  });
}

export function fetchCustomers(workspaceId: string, search?: string) {
  return apiRequest<{ customers: Customer[] }>(
    withQuery(`/workspaces/${workspaceId}/customers`, { search }),
  );
}

export type CustomerInput = {
  name: string;
  phone?: string;
  email?: string;
  address?: string;
  type: string;
  creditLimitCents: number;
  notes?: string;
  status?: string;
};

export function createCustomer(workspaceId: string, input: CustomerInput) {
  return apiRequest<{ customer: Customer }>(
    `/workspaces/${workspaceId}/customers`,
    { method: "POST", body: JSON.stringify(input) },
  );
}

export function updateCustomer(
  workspaceId: string,
  customerId: string,
  input: Partial<CustomerInput>,
) {
  return apiRequest<{ customer: Customer }>(
    `/workspaces/${workspaceId}/customers/${customerId}`,
    { method: "PATCH", body: JSON.stringify(input) },
  );
}

export function deactivateCustomer(workspaceId: string, customerId: string) {
  return apiRequest<{ deactivated: boolean }>(
    `/workspaces/${workspaceId}/customers/${customerId}`,
    { method: "DELETE" },
  );
}

export function fetchSuppliers(workspaceId: string, search?: string) {
  return apiRequest<{ suppliers: Supplier[] }>(
    withQuery(`/workspaces/${workspaceId}/suppliers`, { search }),
  );
}

export type SupplierInput = {
  name: string;
  contactName?: string;
  phone?: string;
  email?: string;
  address?: string;
  notes?: string;
  status?: string;
};

export function createSupplier(workspaceId: string, input: SupplierInput) {
  return apiRequest<{ supplier: Supplier }>(
    `/workspaces/${workspaceId}/suppliers`,
    { method: "POST", body: JSON.stringify(input) },
  );
}

export function updateSupplier(
  workspaceId: string,
  supplierId: string,
  input: Partial<SupplierInput>,
) {
  return apiRequest<{ supplier: Supplier }>(
    `/workspaces/${workspaceId}/suppliers/${supplierId}`,
    { method: "PATCH", body: JSON.stringify(input) },
  );
}

export function deactivateSupplier(workspaceId: string, supplierId: string) {
  return apiRequest<{ deactivated: boolean }>(
    `/workspaces/${workspaceId}/suppliers/${supplierId}`,
    { method: "DELETE" },
  );
}

export type SaleListResponse = {
  sales: SaleSummary[];
  summary: {
    totalCents: number;
    outstandingCents: number;
    count: number;
  };
};

export function fetchSales(
  workspaceId: string,
  params: {
    search?: string;
    status?: string;
    branchId?: string;
    customerId?: string;
    from?: number;
    to?: number;
  } = {},
) {
  return apiRequest<SaleListResponse>(
    withQuery(`/workspaces/${workspaceId}/sales`, params),
  );
}

export function fetchSale(workspaceId: string, saleId: string) {
  return apiRequest<SaleDetail>(
    `/workspaces/${workspaceId}/sales/${saleId}`,
  );
}

export type SaleInput = {
  branchId: string;
  customerId?: string | null;
  customerName?: string;
  paymentMethod: string;
  paidCents: number;
  discountCents: number;
  saleDate?: number;
  dueDate?: number | null;
  note?: string;
  items: {
    productId: string;
    quantity: number;
    unitPriceCents?: number;
    discountCents?: number;
  }[];
};

export function createSale(workspaceId: string, input: SaleInput) {
  return apiRequest<{
    sale: {
      id: string;
      number: string;
      totalCents: number;
      paidCents: number;
      balanceCents: number;
      paymentStatus: string;
    };
  }>(`/workspaces/${workspaceId}/sales`, {
    method: "POST",
    body: JSON.stringify(input),
  });
}

export function updateSalePayment(
  workspaceId: string,
  saleId: string,
  paidCents: number,
) {
  return apiRequest<{
    saleId: string;
    paidCents: number;
    balanceCents: number;
    paymentStatus: string;
  }>(`/workspaces/${workspaceId}/sales/${saleId}/payment`, {
    method: "PATCH",
    body: JSON.stringify({ paidCents }),
  });
}

export function voidSale(workspaceId: string, saleId: string) {
  return apiRequest<{ saleId: string; status: string }>(
    `/workspaces/${workspaceId}/sales/${saleId}/void`,
    { method: "POST" },
  );
}

export type PurchaseListResponse = {
  purchases: PurchaseSummary[];
  summary: {
    totalCents: number;
    outstandingCents: number;
    count: number;
  };
};

export function fetchPurchases(
  workspaceId: string,
  params: { search?: string; supplierId?: string; from?: number; to?: number } = {},
) {
  return apiRequest<PurchaseListResponse>(
    withQuery(`/workspaces/${workspaceId}/purchases`, params),
  );
}

export function fetchPurchase(workspaceId: string, purchaseId: string) {
  return apiRequest<{
    purchase: PurchaseSummary & {
      note: string | null;
      createdByUserId: string | null;
      updatedAt: number;
    };
    branch: { name: string };
    createdByName: string | null;
    items: PurchaseItem[];
  }>(`/workspaces/${workspaceId}/purchases/${purchaseId}`);
}

export type PurchaseInput = {
  branchId: string;
  supplierId?: string | null;
  supplierName?: string;
  paymentMethod: string;
  paidCents: number;
  purchaseDate?: number;
  note?: string;
  items: { productId: string; quantity: number; unitCostCents?: number }[];
};

export function createPurchase(workspaceId: string, input: PurchaseInput) {
  return apiRequest<{
    purchase: {
      id: string;
      number: string;
      totalCents: number;
      paidCents: number;
      balanceCents: number;
      paymentStatus: string;
    };
  }>(`/workspaces/${workspaceId}/purchases`, {
    method: "POST",
    body: JSON.stringify(input),
  });
}

export function updatePurchasePayment(
  workspaceId: string,
  purchaseId: string,
  paidCents: number,
) {
  return apiRequest<{
    purchaseId: string;
    paidCents: number;
    balanceCents: number;
    paymentStatus: string;
  }>(`/workspaces/${workspaceId}/purchases/${purchaseId}/payment`, {
    method: "PATCH",
    body: JSON.stringify({ paidCents }),
  });
}

export function fetchExpenses(
  workspaceId: string,
  params: {
    search?: string;
    category?: string;
    branchId?: string;
    from?: number;
    to?: number;
  } = {},
) {
  return apiRequest<{
    expenses: Expense[];
    summary: { totalCents: number; count: number };
  }>(withQuery(`/workspaces/${workspaceId}/expenses`, params));
}

export type ExpenseInput = {
  branchId?: string | null;
  category: string;
  description: string;
  amountCents: number;
  paymentMethod: string;
  vendor?: string;
  reference?: string;
  expenseDate?: number;
  note?: string;
  status?: string;
};

export function createExpense(workspaceId: string, input: ExpenseInput) {
  return apiRequest<{ expense: Expense }>(
    `/workspaces/${workspaceId}/expenses`,
    { method: "POST", body: JSON.stringify(input) },
  );
}

export function updateExpense(
  workspaceId: string,
  expenseId: string,
  input: Partial<ExpenseInput>,
) {
  return apiRequest<{ expense: Expense }>(
    `/workspaces/${workspaceId}/expenses/${expenseId}`,
    { method: "PATCH", body: JSON.stringify(input) },
  );
}

export function voidExpense(workspaceId: string, expenseId: string) {
  return apiRequest<{ voided: boolean }>(
    `/workspaces/${workspaceId}/expenses/${expenseId}`,
    { method: "DELETE" },
  );
}

export function fetchDashboard(workspaceId: string) {
  return apiRequest<DashboardData>(
    `/workspaces/${workspaceId}/dashboard`,
  );
}

export function fetchReport(
  workspaceId: string,
  params: { from?: number; to?: number; branchId?: string } = {},
) {
  return apiRequest<ReportData>(
    withQuery(`/workspaces/${workspaceId}/summary`, params),
  );
}

export function fetchEmployees(
  workspaceId: string,
  params: { search?: string; status?: string; branchId?: string } = {},
) {
  return apiRequest<EmployeeListResponse>(
    withQuery(`/workspaces/${workspaceId}/employees`, params),
  );
}

export function createEmployee(workspaceId: string, input: EmployeeInput) {
  return apiRequest<{ employee: Employee }>(
    `/workspaces/${workspaceId}/employees`,
    { method: "POST", body: JSON.stringify(input) },
  );
}

export function updateEmployee(
  workspaceId: string,
  employeeId: string,
  input: Partial<EmployeeInput>,
) {
  return apiRequest<{ employee: Employee }>(
    `/workspaces/${workspaceId}/employees/${employeeId}`,
    { method: "PATCH", body: JSON.stringify(input) },
  );
}

export function deactivateEmployee(workspaceId: string, employeeId: string) {
  return apiRequest<{ deactivated: boolean }>(
    `/workspaces/${workspaceId}/employees/${employeeId}`,
    { method: "DELETE" },
  );
}

export function fetchTransfers(
  workspaceId: string,
  params: { search?: string; status?: string; branchId?: string } = {},
) {
  return apiRequest<TransferListResponse>(
    withQuery(`/workspaces/${workspaceId}/transfers`, params),
  );
}

export function fetchTransfer(workspaceId: string, transferId: string) {
  return apiRequest<TransferDetail>(
    `/workspaces/${workspaceId}/transfers/${transferId}`,
  );
}

export type TransferInput = {
  fromBranchId: string;
  toBranchId: string;
  note?: string;
  items: { productId: string; quantity: number }[];
};

export function createTransfer(workspaceId: string, input: TransferInput) {
  return apiRequest<{
    transfer: { id: string; number: string; status: string };
  }>(`/workspaces/${workspaceId}/transfers`, {
    method: "POST",
    body: JSON.stringify(input),
  });
}

export function completeTransfer(workspaceId: string, transferId: string) {
  return apiRequest<{
    transferId: string;
    status: string;
    completedAt: number;
  }>(`/workspaces/${workspaceId}/transfers/${transferId}/complete`, {
    method: "POST",
  });
}

export type PayrollRun = {
  id: string;
  workspaceId: string;
  branchId: string | null;
  number: string;
  periodStart: number;
  periodEnd: number;
  status: string;
  totalCents: number;
  paidCents: number;
  paidAt: number | null;
  note: string | null;
  createdAt: number;
  itemCount: number;
};

export type PayrollItem = {
  id: string;
  runId: string;
  employeeId: string;
  employeeName: string | null;
  salaryCents: number;
  allowancesCents: number;
  deductionsCents: number;
  netCents: number;
  paidAt: number | null;
  createdAt: number;
};

export type PayrollRunInput = {
  branchId: string | null;
  periodStart: number;
  periodEnd: number;
  note?: string;
  items: {
    employeeId: string;
    salaryCents: number;
    allowancesCents: number;
    deductionsCents: number;
  }[];
};

export function fetchPayrollRuns(
  workspaceId: string,
  params: { status?: string; branchId?: string } = {},
) {
  return apiRequest<{ runs: PayrollRun[] }>(
    withQuery(`/workspaces/${workspaceId}/payroll`, params),
  );
}

export function fetchPayrollRun(workspaceId: string, runId: string) {
  return apiRequest<{
    run: PayrollRun & { itemCount: number };
    items: PayrollItem[];
  }>(`/workspaces/${workspaceId}/payroll/${runId}`);
}

export function createPayrollRun(workspaceId: string, input: PayrollRunInput) {
  return apiRequest<{
    runId: string;
    number: string;
    totalCents: number;
    status: string;
  }>(`/workspaces/${workspaceId}/payroll`, {
    method: "POST",
    body: JSON.stringify(input),
  });
}

export function payPayrollRun(
  workspaceId: string,
  runId: string,
  paidCents: number,
) {
  return apiRequest<{
    runId: string;
    status: string;
    paidCents: number;
    balanceCents: number;
  }>(`/workspaces/${workspaceId}/payroll/${runId}/pay`, {
    method: "POST",
    body: JSON.stringify({ paidCents }),
  });
}

export function cancelPayrollRun(workspaceId: string, runId: string) {
  return apiRequest<{ runId: string; status: string }>(
    `/workspaces/${workspaceId}/payroll/${runId}`,
    { method: "DELETE" },
  );
}

export function cancelTransfer(workspaceId: string, transferId: string) {
  return apiRequest<{ transferId: string; status: string }>(
    `/workspaces/${workspaceId}/transfers/${transferId}/cancel`,
    { method: "POST" },
  );
}

export function fetchOrders(
  workspaceId: string,
  params: {
    search?: string;
    status?: string;
    branchId?: string;
    customerId?: string;
    from?: number;
    to?: number;
  } = {},
) {
  return apiRequest<OrderListResponse>(
    withQuery(`/workspaces/${workspaceId}/orders`, params),
  );
}

export function fetchOrder(workspaceId: string, orderId: string) {
  return apiRequest<OrderDetail>(
    `/workspaces/${workspaceId}/orders/${orderId}`,
  );
}

export type OrderInput = {
  branchId: string;
  customerId?: string | null;
  customerName?: string;
  paymentMethod: string;
  discountCents: number;
  paidCents: number;
  orderDate?: number;
  expectedDate?: number | null;
  note?: string;
  items: {
    productId: string;
    quantity: number;
    unitPriceCents?: number;
    discountCents?: number;
  }[];
};

export function createOrder(workspaceId: string, input: OrderInput) {
  return apiRequest<{
    order: {
      id: string;
      number: string;
      status: string;
      totalCents: number;
      paidCents: number;
    };
  }>(`/workspaces/${workspaceId}/orders`, {
    method: "POST",
    body: JSON.stringify(input),
  });
}

export function updateOrderStatus(
  workspaceId: string,
  orderId: string,
  status: "pending" | "confirmed" | "cancelled",
) {
  return apiRequest<{ orderId: string; status: string }>(
    `/workspaces/${workspaceId}/orders/${orderId}/status`,
    { method: "PATCH", body: JSON.stringify({ status }) },
  );
}

export function fulfillOrder(workspaceId: string, orderId: string) {
  return apiRequest<{
    orderId: string;
    status: string;
    saleId: string;
    saleNumber: string;
    totalCents: number;
    paidCents: number;
    balanceCents: number;
  }>(`/workspaces/${workspaceId}/orders/${orderId}/fulfill`, {
    method: "POST",
  });
}

export function fetchSubscription(workspaceId: string) {
  return apiRequest<SubscriptionResponse>(
    `/workspaces/${workspaceId}/subscription`,
  );
}

export function changeSubscription(
  workspaceId: string,
  input: { planId: string; billingCycle: "monthly" | "yearly" },
) {
  return apiRequest<{
    subscription: {
      id: string;
      planId: string;
      status: string;
      billingCycle: string;
      currentPeriodStart?: number;
      currentPeriodEnd: number;
      trialEndsAt?: number | null;
    };
  }>(`/workspaces/${workspaceId}/subscription/change`, {
    method: "POST",
    body: JSON.stringify(input),
  });
}

export function cancelSubscription(workspaceId: string) {
  return apiRequest<{ subscriptionId: string; cancelAtPeriodEnd: boolean }>(
    `/workspaces/${workspaceId}/subscription/cancel`,
    { method: "POST" },
  );
}

export type AiStatus = {
  enabled: boolean;
  configured: boolean;
  creditsUsed: number;
  creditsLimit: number | null;
  creditsRemaining: number | null;
  model: string;
};

export type AiChatMessage = {
  role: "user" | "assistant";
  content: string;
};

export type AiChatResponse = {
  reply: string;
  model: string;
  usage: {
    promptTokens: number;
    completionTokens: number;
    totalTokens: number;
  };
  credits: {
    used: number;
    limit: number | null;
    remaining: number | null;
  };
};

export function fetchAiStatus(workspaceId: string) {
  return apiRequest<AiStatus>(`/workspaces/${workspaceId}/ai`);
}

export function chatWithAi(
  workspaceId: string,
  input: {
    message: string;
    locale: "en" | "km";
    history: AiChatMessage[];
  },
) {
  return apiRequest<AiChatResponse>(`/workspaces/${workspaceId}/ai/chat`, {
    method: "POST",
    body: JSON.stringify(input),
  });
}

export type NotificationRecord = {
  id: string;
  workspaceId: string;
  userId: string | null;
  type: string;
  title: string;
  body: string | null;
  channel: string;
  status: string;
  readAt: number | null;
  createdAt: number;
};

export type NotificationsResponse = {
  notifications: NotificationRecord[];
  unreadCount: number;
};

export function fetchNotifications(
  workspaceId: string,
  params: { limit?: number } = {},
) {
  return apiRequest<NotificationsResponse>(
    withQuery(`/workspaces/${workspaceId}/notifications`, params),
  );
}

export function markNotificationRead(
  workspaceId: string,
  notificationId: string,
) {
  return apiRequest<{ notificationId: string; readAt: number }>(
    `/workspaces/${workspaceId}/notifications/${notificationId}/read`,
    { method: "PATCH" },
  );
}

export function markAllNotificationsRead(workspaceId: string) {
  return apiRequest<{ marked: number; readAt: number }>(
    `/workspaces/${workspaceId}/notifications/read-all`,
    { method: "POST" },
  );
}

export type AuditLog = {
  id: string;
  action: string;
  entityType: string | null;
  entityId: string | null;
  metadata: unknown;
  ip: string | null;
  userAgent: string | null;
  createdAt: number;
  actorUserId: string | null;
  actorName: string | null;
  actorEmail: string | null;
};

export function fetchAuditLogs(
  workspaceId: string,
  params: {
    limit?: number;
    action?: string;
    entityType?: string;
    search?: string;
  } = {},
) {
  return apiRequest<{ logs: AuditLog[] }>(
    withQuery(`/workspaces/${workspaceId}/audit-logs`, params),
  );
}

export async function exportReportCsv(
  workspaceId: string,
  params: { from?: number; to?: number; branchId?: string } = {},
) {
  const response = await fetch(
    `/api/v1${withQuery(`/workspaces/${workspaceId}/reports/export`, params)}`,
    { credentials: "include" },
  );

  if (!response.ok) {
    let message = "Unable to export report.";
    try {
      const payload = (await response.json()) as {
        ok?: boolean;
        error?: { message?: string };
      };
      if (payload.ok === false && payload.error?.message) {
        message = payload.error.message;
      }
    } catch {
      // Keep the generic export error.
    }
    throw new ApiClientError(message, "export_failed", response.status);
  }

  const blob = await response.blob();
  const disposition = response.headers.get("content-disposition");
  const filename =
    disposition?.match(/filename="?([^"]+)"?/)?.[1] ??
    `report-${new Date().toISOString().slice(0, 10)}.csv`;
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  link.remove();
  URL.revokeObjectURL(url);
}
