import { and, desc, eq, gte, inArray, lt, lte, sql } from "drizzle-orm";
import {
  branches,
  customers,
  expenses,
  inventoryLevels,
  orders,
  products,
  saleItems,
  sales,
  stockTransfers,
} from "@mba/db";
import type { Env } from "../env";
import { getDb } from "../db";
import { localDateKey, startOfMonth, startOfNextMonth } from "../lib/dates";

type WorkspaceProfile = {
  name: string;
  currency: string;
  locale: string;
};

function money(cents: number) {
  return Math.round(cents) / 100;
}

export async function buildAiBusinessContext(
  env: Env,
  workspaceId: string,
  workspace: WorkspaceProfile,
) {
  const db = getDb(env);
  const monthStart = startOfMonth();
  const monthEnd = startOfNextMonth();

  const [
    branchRows,
    activeProductRows,
    customerRows,
    monthSales,
    monthExpenses,
    lowStockRows,
    orderStatusRows,
    recentOrders,
    transferStatusRows,
    pendingTransfers,
  ] = await Promise.all([
    db
      .select({
        name: branches.name,
        code: branches.code,
        status: branches.status,
        isDefault: branches.isDefault,
      })
      .from(branches)
      .where(eq(branches.workspaceId, workspaceId)),
    db
      .select({ count: sql<number>`count(*)` })
      .from(products)
      .where(
        and(
          eq(products.workspaceId, workspaceId),
          eq(products.status, "active"),
        ),
      ),
    db
      .select({ count: sql<number>`count(*)` })
      .from(customers)
      .where(eq(customers.workspaceId, workspaceId)),
    db
      .select()
      .from(sales)
      .where(
        and(
          eq(sales.workspaceId, workspaceId),
          eq(sales.status, "completed"),
          gte(sales.saleDate, monthStart),
          lt(sales.saleDate, monthEnd),
        ),
      ),
    db
      .select()
      .from(expenses)
      .where(
        and(
          eq(expenses.workspaceId, workspaceId),
          sql`${expenses.status} <> 'void'`,
          gte(expenses.expenseDate, monthStart),
          lt(expenses.expenseDate, monthEnd),
        ),
      ),
    db
      .select({
        productName: products.name,
        unit: products.unit,
        branchName: branches.name,
        quantity: inventoryLevels.quantity,
        threshold: products.lowStockThreshold,
      })
      .from(inventoryLevels)
      .innerJoin(products, eq(inventoryLevels.productId, products.id))
      .innerJoin(branches, eq(inventoryLevels.branchId, branches.id))
      .where(
        and(
          eq(inventoryLevels.workspaceId, workspaceId),
          eq(products.trackInventory, true),
          eq(products.status, "active"),
          lte(inventoryLevels.quantity, products.lowStockThreshold),
        ),
      )
      .orderBy(inventoryLevels.quantity)
      .limit(10),
    db
      .select({
        status: orders.status,
        count: sql<number>`count(*)`,
      })
      .from(orders)
      .where(eq(orders.workspaceId, workspaceId))
      .groupBy(orders.status),
    db
      .select({
        number: orders.number,
        customerName: orders.customerName,
        status: orders.status,
        totalCents: orders.totalCents,
        paidCents: orders.paidCents,
        orderDate: orders.orderDate,
        expectedDate: orders.expectedDate,
      })
      .from(orders)
      .where(eq(orders.workspaceId, workspaceId))
      .orderBy(desc(orders.orderDate))
      .limit(5),
    db
      .select({
        status: stockTransfers.status,
        count: sql<number>`count(*)`,
      })
      .from(stockTransfers)
      .where(eq(stockTransfers.workspaceId, workspaceId))
      .groupBy(stockTransfers.status),
    db
      .select({
        number: stockTransfers.number,
        status: stockTransfers.status,
        note: stockTransfers.note,
        createdAt: stockTransfers.createdAt,
      })
      .from(stockTransfers)
      .where(
        and(
          eq(stockTransfers.workspaceId, workspaceId),
          eq(stockTransfers.status, "pending"),
        ),
      )
      .orderBy(desc(stockTransfers.createdAt))
      .limit(5),
  ]);

  const saleIds = monthSales.map((sale) => sale.id);
  const items =
    saleIds.length > 0
      ? await db
          .select({
            productId: saleItems.productId,
            productName: saleItems.productName,
            quantity: saleItems.quantity,
            totalCents: saleItems.totalCents,
            costPriceCents: saleItems.costPriceCents,
          })
          .from(saleItems)
          .where(inArray(saleItems.saleId, saleIds))
      : [];

  const revenueCents = monthSales.reduce(
    (sum, sale) => sum + sale.totalCents,
    0,
  );
  const costCents = items.reduce(
    (sum, item) => sum + item.costPriceCents * item.quantity,
    0,
  );
  const expenseCents = monthExpenses.reduce(
    (sum, expense) => sum + expense.amountCents,
    0,
  );
  const outstandingCents = monthSales.reduce(
    (sum, sale) => sum + sale.balanceCents,
    0,
  );

  const productMap = new Map<
    string,
    { productName: string; quantity: number; revenueCents: number }
  >();
  for (const item of items) {
    const key = item.productId ?? item.productName;
    const entry = productMap.get(key) ?? {
      productName: item.productName,
      quantity: 0,
      revenueCents: 0,
    };
    entry.quantity += item.quantity;
    entry.revenueCents += item.totalCents;
    productMap.set(key, entry);
  }

  const expenseByCategory = new Map<string, number>();
  for (const expense of monthExpenses) {
    expenseByCategory.set(
      expense.category,
      (expenseByCategory.get(expense.category) ?? 0) + expense.amountCents,
    );
  }

  const orderCounts = Object.fromEntries(
    orderStatusRows.map((row) => [row.status, Number(row.count)]),
  );
  const transferCounts = Object.fromEntries(
    transferStatusRows.map((row) => [row.status, Number(row.count)]),
  );

  const snapshot = {
    generatedAt: new Date().toISOString(),
    period: `${localDateKey(monthStart)} to ${localDateKey(monthEnd - 1)}`,
    workspace: {
      name: workspace.name,
      currency: workspace.currency,
      locale: workspace.locale,
      branches: branchRows,
    },
    financials: {
      revenue: money(revenueCents),
      costOfGoods: money(costCents),
      grossProfit: money(revenueCents - costCents),
      expenses: money(expenseCents),
      netProfit: money(revenueCents - costCents - expenseCents),
      outstanding: money(outstandingCents),
      salesCount: monthSales.length,
      averageSale:
        monthSales.length > 0 ? money(revenueCents / monthSales.length) : 0,
      activeProducts: Number(activeProductRows[0]?.count ?? 0),
      customers: Number(customerRows[0]?.count ?? 0),
      lowStockCount: lowStockRows.length,
    },
    topProducts: Array.from(productMap.values())
      .sort((a, b) => b.revenueCents - a.revenueCents)
      .slice(0, 8)
      .map((entry) => ({
        productName: entry.productName,
        quantity: entry.quantity,
        revenue: money(entry.revenueCents),
      })),
    lowStock: lowStockRows.map((row) => ({
      productName: row.productName,
      branchName: row.branchName,
      quantity: row.quantity,
      threshold: row.threshold,
      unit: row.unit,
    })),
    expensesByCategory: Array.from(expenseByCategory.entries())
      .map(([category, amountCents]) => ({
        category,
        amount: money(amountCents),
      }))
      .sort((a, b) => b.amount - a.amount),
    orders: {
      counts: orderCounts,
      recent: recentOrders.map((order) => ({
        number: order.number,
        customerName: order.customerName,
        status: order.status,
        total: money(order.totalCents),
        paid: money(order.paidCents),
        orderDate: localDateKey(order.orderDate),
        expectedDate: order.expectedDate
          ? localDateKey(order.expectedDate)
          : null,
      })),
    },
    transfers: {
      counts: transferCounts,
      pending: pendingTransfers.map((transfer) => ({
        number: transfer.number,
        note: transfer.note,
        createdAt: localDateKey(transfer.createdAt),
      })),
    },
  };

  return JSON.stringify(snapshot).slice(0, 12_000);
}
