import { Hono } from "hono";
import {
  and,
  desc,
  eq,
  gte,
  inArray,
  lte,
  lt,
  sql,
} from "drizzle-orm";
import {
  branches,
  customers,
  expenses,
  inventoryLevels,
  products,
  purchases,
  saleItems,
  sales,
} from "@mba/db";
import type { AppEnv, Env } from "../env";
import { getDb } from "../db";
import { ok } from "../lib/response";
import { writeAudit } from "../services/audit";
import {
  defaultReportRange,
  localDateKey,
  startOfDaysAgo,
  startOfMonth,
  startOfNextMonth,
} from "../lib/dates";
import { requirePermission } from "../middleware/workspace";

export const reportRoutes = new Hono<AppEnv>();

function percentChange(current: number, previous: number) {
  if (previous === 0) return current > 0 ? 100 : 0;
  return Math.round(((current - previous) / previous) * 1000) / 10;
}

reportRoutes.get("/dashboard", requirePermission("report:read"), async (context) => {
  const access = context.get("workspace");
  const db = getDb(context.env);
  const workspaceId = access.workspace.id;
  const now = Date.now();
  const monthStart = startOfMonth(now);
  const monthEnd = startOfNextMonth(now);
  const prevMonthStart = startOfMonth(monthStart - 1);
  const weekStart = startOfDaysAgo(7, now);

  const monthSales = await db
    .select()
    .from(sales)
    .where(
      and(
        eq(sales.workspaceId, workspaceId),
        eq(sales.status, "completed"),
        gte(sales.saleDate, monthStart),
        lt(sales.saleDate, monthEnd),
      ),
    );

  const prevSales = await db
    .select({
      total: sql<number>`coalesce(sum(${sales.totalCents}), 0)`,
    })
    .from(sales)
    .where(
      and(
        eq(sales.workspaceId, workspaceId),
        eq(sales.status, "completed"),
        gte(sales.saleDate, prevMonthStart),
        lt(sales.saleDate, monthStart),
      ),
    );

  const monthExpenses = await db
    .select()
    .from(expenses)
    .where(
      and(
        eq(expenses.workspaceId, workspaceId),
        sql`${expenses.status} <> 'void'`,
        gte(expenses.expenseDate, monthStart),
        lt(expenses.expenseDate, monthEnd),
      ),
    );

  const activeProducts = await db
    .select({ total: sql<number>`count(*)` })
    .from(products)
    .where(
      and(
        eq(products.workspaceId, workspaceId),
        eq(products.status, "active"),
      ),
    );

  const lowStockRows = await db
    .select({
      productId: products.id,
      productName: products.name,
      quantity: inventoryLevels.quantity,
      threshold: products.lowStockThreshold,
      branchName: branches.name,
      unit: products.unit,
    })
    .from(inventoryLevels)
    .innerJoin(products, eq(inventoryLevels.productId, products.id))
    .innerJoin(branches, eq(inventoryLevels.branchId, branches.id))
    .where(
      and(
        eq(inventoryLevels.workspaceId, workspaceId),
        eq(products.trackInventory, true),
        lte(inventoryLevels.quantity, products.lowStockThreshold),
      ),
    )
    .orderBy(inventoryLevels.quantity)
    .limit(10);

  const weekSales = await db
    .select({
      id: sales.id,
      saleDate: sales.saleDate,
      totalCents: sales.totalCents,
    })
    .from(sales)
    .where(
      and(
        eq(sales.workspaceId, workspaceId),
        eq(sales.status, "completed"),
        gte(sales.saleDate, weekStart),
      ),
    );

  const weekItems =
    weekSales.length > 0
      ? await db
          .select({
            saleId: saleItems.saleId,
            quantity: saleItems.quantity,
            costPriceCents: saleItems.costPriceCents,
          })
          .from(saleItems)
          .where(
            inArray(
              saleItems.saleId,
              weekSales.map((sale) => sale.id),
            ),
          )
      : [];

  const costBySale = new Map<string, number>();
  for (const item of weekItems) {
    costBySale.set(
      item.saleId,
      (costBySale.get(item.saleId) ?? 0) +
        item.costPriceCents * item.quantity,
    );
  }

  const seriesMap = new Map<
    string,
    { date: string; salesCents: number; profitCents: number }
  >();
  for (let index = 0; index < 7; index += 1) {
    const key = localDateKey(startOfDaysAgo(7 - index, now));
    seriesMap.set(key, { date: key, salesCents: 0, profitCents: 0 });
  }
  for (const sale of weekSales) {
    const key = localDateKey(sale.saleDate);
    const entry = seriesMap.get(key);
    if (entry) {
      entry.salesCents += sale.totalCents;
      entry.profitCents +=
        sale.totalCents - (costBySale.get(sale.id) ?? 0);
    }
  }

  const recentSales = await db
    .select({
      id: sales.id,
      number: sales.number,
      customerName: sales.customerName,
      totalCents: sales.totalCents,
      status: sales.status,
      saleDate: sales.saleDate,
    })
    .from(sales)
    .where(eq(sales.workspaceId, workspaceId))
    .orderBy(desc(sales.saleDate))
    .limit(5);

  const topProducts =
    monthSales.length > 0
      ? await db
          .select({
            productId: saleItems.productId,
            productName: saleItems.productName,
            quantity: sql<number>`sum(${saleItems.quantity})`,
            revenueCents: sql<number>`sum(${saleItems.totalCents})`,
          })
          .from(saleItems)
          .where(
            inArray(
              saleItems.saleId,
              monthSales.map((sale) => sale.id),
            ),
          )
          .groupBy(saleItems.productId, saleItems.productName)
          .orderBy(desc(sql`sum(${saleItems.totalCents})`))
          .limit(5)
      : [];

  const revenueCents = monthSales.reduce(
    (sum, sale) => sum + sale.totalCents,
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

  return ok(context, {
    stats: {
      revenueCents,
      revenueChangePercent: percentChange(
        revenueCents,
        Number(prevSales[0]?.total ?? 0),
      ),
      salesCount: monthSales.length,
      expenseCents,
      netProfitCents: revenueCents - expenseCents,
      outstandingCents,
      activeProducts: Number(activeProducts[0]?.total ?? 0),
      lowStockCount: lowStockRows.length,
    },
    series: Array.from(seriesMap.values()),
    lowStock: lowStockRows,
    recentSales,
    topProducts: topProducts.map((row) => ({
      ...row,
      quantity: Number(row.quantity),
      revenueCents: Number(row.revenueCents),
    })),
  });
});

type ReportRange = { from: number; to: number; branchId: string | null };

async function buildReportSummary(
  env: Env,
  workspaceId: string,
  range: ReportRange,
) {
  const db = getDb(env);
  const { from, to, branchId } = range;

  const saleFilters = [
    eq(sales.workspaceId, workspaceId),
    eq(sales.status, "completed"),
    gte(sales.saleDate, from),
    lte(sales.saleDate, to),
  ];
  if (branchId) saleFilters.push(eq(sales.branchId, branchId));

  const salesRows = await db
    .select()
    .from(sales)
    .where(and(...saleFilters));

  const expenseFilters = [
    eq(expenses.workspaceId, workspaceId),
    sql`${expenses.status} <> 'void'`,
    gte(expenses.expenseDate, from),
    lte(expenses.expenseDate, to),
  ];
  if (branchId) expenseFilters.push(eq(expenses.branchId, branchId));

  const expenseRows = await db
    .select()
    .from(expenses)
    .where(and(...expenseFilters));

  const purchaseFilters = [
    eq(purchases.workspaceId, workspaceId),
    eq(purchases.status, "received"),
    gte(purchases.purchaseDate, from),
    lte(purchases.purchaseDate, to),
  ];
  if (branchId) purchaseFilters.push(eq(purchases.branchId, branchId));

  const purchaseRows = await db
    .select()
    .from(purchases)
    .where(and(...purchaseFilters));

  const items =
    salesRows.length > 0
      ? await db
          .select()
          .from(saleItems)
          .where(
            inArray(
              saleItems.saleId,
              salesRows.map((sale) => sale.id),
            ),
          )
      : [];

  const revenueCents = salesRows.reduce(
    (sum, sale) => sum + sale.totalCents,
    0,
  );
  const costCents = items.reduce(
    (sum, item) => sum + item.costPriceCents * item.quantity,
    0,
  );
  const expenseCents = expenseRows.reduce(
    (sum, expense) => sum + expense.amountCents,
    0,
  );
  const purchaseCents = purchaseRows.reduce(
    (sum, purchase) => sum + purchase.totalCents,
    0,
  );

  const seriesMap = new Map<
    string,
    { date: string; salesCents: number; expenseCents: number }
  >();
  const dayMs = 24 * 60 * 60 * 1_000;
  const startKey = localDateKey(from);
  let cursor = from;
  let guard = 0;
  while (cursor <= to && guard < 400) {
    const key = localDateKey(cursor);
    seriesMap.set(key, { date: key, salesCents: 0, expenseCents: 0 });
    cursor += dayMs;
    guard += 1;
  }
  if (!seriesMap.has(startKey)) {
    seriesMap.set(startKey, { date: startKey, salesCents: 0, expenseCents: 0 });
  }

  for (const sale of salesRows) {
    const key = localDateKey(sale.saleDate);
    const entry = seriesMap.get(key);
    if (entry) entry.salesCents += sale.totalCents;
  }
  for (const expense of expenseRows) {
    const key = localDateKey(expense.expenseDate);
    const entry = seriesMap.get(key);
    if (entry) entry.expenseCents += expense.amountCents;
  }

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
  for (const expense of expenseRows) {
    expenseByCategory.set(
      expense.category,
      (expenseByCategory.get(expense.category) ?? 0) + expense.amountCents,
    );
  }

  const paymentBreakdown = new Map<string, number>();
  for (const sale of salesRows) {
    paymentBreakdown.set(
      sale.paymentMethod,
      (paymentBreakdown.get(sale.paymentMethod) ?? 0) + sale.totalCents,
    );
  }

  const customerCount = await db
    .select({ total: sql<number>`count(*)` })
    .from(customers)
    .where(eq(customers.workspaceId, workspaceId));

  return {
    range: { from, to, branchId: branchId ?? null },
    summary: {
      revenueCents,
      costCents,
      grossProfitCents: revenueCents - costCents,
      expenseCents,
      netProfitCents: revenueCents - costCents - expenseCents,
      purchaseCents,
      salesCount: salesRows.length,
      averageSaleCents:
        salesRows.length > 0
          ? Math.round(revenueCents / salesRows.length)
          : 0,
      outstandingCents: salesRows.reduce(
        (sum, sale) => sum + sale.balanceCents,
        0,
      ),
      customerCount: Number(customerCount[0]?.total ?? 0),
    },
    series: Array.from(seriesMap.values()),
    topProducts: Array.from(productMap.values())
      .sort((a, b) => b.revenueCents - a.revenueCents)
      .slice(0, 10),
    expenseByCategory: Array.from(expenseByCategory.entries())
      .map(([category, amountCents]) => ({ category, amountCents }))
      .sort((a, b) => b.amountCents - a.amountCents),
    paymentBreakdown: Array.from(paymentBreakdown.entries()).map(
      ([method, totalCents]) => ({ method, totalCents }),
    ),
  };
}

function csvCell(value: string | number | null | undefined) {
  const text = value === null || value === undefined ? "" : String(value);
  return `"${text.replace(/"/g, '""')}"`;
}

function units(cents: number) {
  return (cents / 100).toFixed(2);
}

function buildReportCsv(
  payload: Awaited<ReturnType<typeof buildReportSummary>>,
) {
  const rows: Array<Array<string | number>> = [
    ["Report", `${localDateKey(payload.range.from)} to ${localDateKey(payload.range.to)}`],
    ["Branch", payload.range.branchId ?? "All branches"],
    ["Generated", new Date().toISOString()],
    [],
    ["Summary"],
    ["Metric", "Amount"],
    ["Revenue", units(payload.summary.revenueCents)],
    ["Cost of goods", units(payload.summary.costCents)],
    ["Gross profit", units(payload.summary.grossProfitCents)],
    ["Expenses", units(payload.summary.expenseCents)],
    ["Net profit", units(payload.summary.netProfitCents)],
    ["Purchases", units(payload.summary.purchaseCents)],
    ["Average sale", units(payload.summary.averageSaleCents)],
    ["Outstanding", units(payload.summary.outstandingCents)],
    ["Sales count", payload.summary.salesCount],
    ["Customer count", payload.summary.customerCount],
    [],
    ["Daily flow"],
    ["Date", "Sales", "Expenses"],
    ...payload.series.map((point) => [
      point.date,
      units(point.salesCents),
      units(point.expenseCents),
    ]),
    [],
    ["Top products"],
    ["Product", "Quantity", "Revenue"],
    ...payload.topProducts.map((product) => [
      product.productName,
      product.quantity,
      units(product.revenueCents),
    ]),
    [],
    ["Expenses by category"],
    ["Category", "Amount"],
    ...payload.expenseByCategory.map((entry) => [
      entry.category,
      units(entry.amountCents),
    ]),
    [],
    ["Sales by payment method"],
    ["Method", "Amount"],
    ...payload.paymentBreakdown.map((entry) => [
      entry.method,
      units(entry.totalCents),
    ]),
  ];

  return `\uFEFF${rows
    .map((row) => row.map((value) => csvCell(value)).join(","))
    .join("\r\n")}`;
}

reportRoutes.get("/summary", requirePermission("report:read"), async (context) => {
  const access = context.get("workspace");
  const defaults = defaultReportRange();
  const from = Number(context.req.query("from")) || defaults.from;
  const to = Number(context.req.query("to")) || defaults.to;
  const branchId = context.req.query("branchId") || null;

  const payload = await buildReportSummary(
    context.env,
    access.workspace.id,
    { from, to, branchId },
  );

  return ok(context, payload);
});

reportRoutes.get(
  "/reports/export",
  requirePermission("report:export"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const defaults = defaultReportRange();
    const from = Number(context.req.query("from")) || defaults.from;
    const to = Number(context.req.query("to")) || defaults.to;
    const branchId = context.req.query("branchId") || null;

    const payload = await buildReportSummary(
      context.env,
      access.workspace.id,
      { from, to, branchId },
    );
    const csv = buildReportCsv(payload);
    const filename = `report-${localDateKey(from)}-${localDateKey(to)}.csv`;

    await writeAudit(context.env, {
      workspaceId: access.workspace.id,
      actorUserId: user.id,
      action: "report.exported",
      entityType: "report",
      metadata: { from, to, branchId, format: "csv" },
    });

    return context.body(csv, 200, {
      "content-type": "text/csv; charset=utf-8",
      "content-disposition": `attachment; filename="${filename}"`,
    });
  },
);
