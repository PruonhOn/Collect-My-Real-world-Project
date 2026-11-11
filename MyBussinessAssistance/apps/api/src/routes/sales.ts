import { Hono } from "hono";
import { and, desc, eq, gte, inArray, like, lte, ne, or, sql } from "drizzle-orm";
import {
  branches,
  customers,
  inventoryLevels,
  products,
  saleItems,
  sales,
  stockMovements,
  users,
  workspaceSettings,
} from "@mba/db";
import { createSaleSchema, salePaymentSchema } from "@mba/shared";
import type { AppEnv } from "../env";
import { getDb } from "../db";
import { badRequest, conflict, notFound } from "../lib/errors";
import { created, ok } from "../lib/response";
import { newId } from "../lib/ids";
import { parseJson } from "../lib/validate";
import {
  normalizeDiscount,
  paymentStatus,
  taxFromSubtotal,
} from "../lib/money";
import { requirePermission } from "../middleware/workspace";
import { writeAudit } from "../services/audit";
import { assertTransactionsAvailable } from "../services/entitlements";

export const saleRoutes = new Hono<AppEnv>();

saleRoutes.get("/", requirePermission("sale:read"), async (context) => {
  const access = context.get("workspace");
  const db = getDb(context.env);
  const workspaceId = access.workspace.id;
  const search = context.req.query("search")?.trim();
  const status = context.req.query("status");
  const branchId = context.req.query("branchId");
  const customerId = context.req.query("customerId");
  const from = Number(context.req.query("from"));
  const to = Number(context.req.query("to"));

  const filters = [eq(sales.workspaceId, workspaceId)];
  if (status === "completed" || status === "void") {
    filters.push(eq(sales.status, status));
  }
  if (branchId) filters.push(eq(sales.branchId, branchId));
  if (customerId) filters.push(eq(sales.customerId, customerId));
  if (Number.isFinite(from) && from > 0) filters.push(gte(sales.saleDate, from));
  if (Number.isFinite(to) && to > 0) filters.push(lte(sales.saleDate, to));
  if (search) {
    const term = `%${search}%`;
    filters.push(
      or(
        like(sales.number, term),
        like(sales.customerName, term),
        like(sales.note, term),
      )!,
    );
  }

  const rows = await db
    .select({
      id: sales.id,
      number: sales.number,
      status: sales.status,
      paymentStatus: sales.paymentStatus,
      paymentMethod: sales.paymentMethod,
      customerId: sales.customerId,
      customerName: sales.customerName,
      branchId: sales.branchId,
      branchName: branches.name,
      currency: sales.currency,
      subtotalCents: sales.subtotalCents,
      discountCents: sales.discountCents,
      taxCents: sales.taxCents,
      totalCents: sales.totalCents,
      paidCents: sales.paidCents,
      balanceCents: sales.balanceCents,
      saleDate: sales.saleDate,
      dueDate: sales.dueDate,
      createdAt: sales.createdAt,
      itemCount: sql<number>`(select count(*) from sale_items where sale_id = ${sales.id})`,
    })
    .from(sales)
    .innerJoin(branches, eq(sales.branchId, branches.id))
    .where(and(...filters))
    .orderBy(desc(sales.saleDate))
    .limit(300);

  return ok(context, {
    sales: rows.map((row) => ({ ...row, itemCount: Number(row.itemCount) })),
    summary: {
      totalCents: rows
        .filter((row) => row.status === "completed")
        .reduce((sum, row) => sum + row.totalCents, 0),
      outstandingCents: rows
        .filter((row) => row.status === "completed")
        .reduce((sum, row) => sum + row.balanceCents, 0),
      count: rows.length,
    },
  });
});

saleRoutes.get("/:saleId", requirePermission("sale:read"), async (context) => {
  const access = context.get("workspace");
  const db = getDb(context.env);
  const saleId = context.req.param("saleId");

  const rows = await db
    .select({
      sale: sales,
      branchName: branches.name,
      branchPhone: branches.phone,
      branchAddress: branches.address,
      createdByName: users.name,
    })
    .from(sales)
    .innerJoin(branches, eq(sales.branchId, branches.id))
    .leftJoin(users, eq(sales.createdByUserId, users.id))
    .where(
      and(eq(sales.id, saleId), eq(sales.workspaceId, access.workspace.id)),
    )
    .limit(1);

  const row = rows[0];
  if (!row) throw notFound("Sale not found.");

  const items = await db
    .select()
    .from(saleItems)
    .where(eq(saleItems.saleId, saleId));

  return ok(context, {
    sale: row.sale,
    branch: {
      name: row.branchName,
      phone: row.branchPhone,
      address: row.branchAddress,
    },
    createdByName: row.createdByName,
    items,
  });
});

saleRoutes.post("/", requirePermission("sale:create"), async (context) => {
  const access = context.get("workspace");
  const user = context.get("user");
  const data = await parseJson(context, createSaleSchema);
  const db = getDb(context.env);
  const workspaceId = access.workspace.id;
  const now = Date.now();
  const saleDate = data.saleDate ?? now;

  await assertTransactionsAvailable(context.env, workspaceId);

  const branch = await db
    .select()
    .from(branches)
    .where(
      and(eq(branches.id, data.branchId), eq(branches.workspaceId, workspaceId)),
    )
    .limit(1);
  if (!branch[0]) throw badRequest("Select a valid branch.");

  const customer = data.customerId
    ? (
        await db
          .select()
          .from(customers)
          .where(
            and(
              eq(customers.id, data.customerId),
              eq(customers.workspaceId, workspaceId),
            ),
          )
          .limit(1)
      )[0]
    : null;
  if (data.customerId && !customer) {
    throw badRequest("Select a valid customer.");
  }

  const settings = (
    await db
      .select()
      .from(workspaceSettings)
      .where(eq(workspaceSettings.workspaceId, workspaceId))
      .limit(1)
  )[0];

  const merged = new Map<
    string,
    { quantity: number; unitPriceCents?: number; discountCents: number }
  >();
  for (const item of data.items) {
    const existing = merged.get(item.productId);
    if (existing) {
      existing.quantity += item.quantity;
      existing.discountCents += item.discountCents ?? 0;
      if (item.unitPriceCents !== undefined) {
        existing.unitPriceCents = item.unitPriceCents;
      }
    } else {
      merged.set(item.productId, {
        quantity: item.quantity,
        unitPriceCents: item.unitPriceCents,
        discountCents: item.discountCents ?? 0,
      });
    }
  }

  const productRows = await db
    .select()
    .from(products)
    .where(
      and(
        eq(products.workspaceId, workspaceId),
        inArray(products.id, Array.from(merged.keys())),
      ),
    );
  const productMap = new Map(productRows.map((product) => [product.id, product]));

  for (const productId of merged.keys()) {
    if (!productMap.has(productId)) {
      throw badRequest("One of the selected products is no longer available.");
    }
  }

  const trackedIds = productRows
    .filter((product) => product.trackInventory)
    .map((product) => product.id);
  const levels =
    trackedIds.length > 0
      ? await db
          .select()
          .from(inventoryLevels)
          .where(
            and(
              eq(inventoryLevels.workspaceId, workspaceId),
              eq(inventoryLevels.branchId, data.branchId),
              inArray(inventoryLevels.productId, trackedIds),
            ),
          )
      : [];
  const levelMap = new Map(levels.map((level) => [level.productId, level]));

  const calculated = [];
  let subtotalCents = 0;
  let taxCents = 0;

  for (const [productId, input] of merged) {
    const product = productMap.get(productId)!;
    const unitPriceCents = input.unitPriceCents ?? product.sellingPriceCents;
    const lineSubtotal = Math.max(
      0,
      unitPriceCents * input.quantity - input.discountCents,
    );
    const rate =
      product.taxRateBasisPoints ??
      (settings?.taxEnabled ? settings.taxRateBasisPoints : 0);
    const lineTax = taxFromSubtotal(lineSubtotal, rate);
    const lineTotal = lineSubtotal + lineTax;

    if (product.trackInventory) {
      const available = levelMap.get(product.id)?.quantity ?? 0;
      if (available < input.quantity) {
        throw badRequest(
          `Not enough stock for ${product.name}. Available: ${available} ${product.unit}.`,
        );
      }
    }

    subtotalCents += lineSubtotal;
    taxCents += lineTax;
    calculated.push({
      product,
      quantity: input.quantity,
      unitPriceCents,
      discountCents: input.discountCents,
      rate,
      subtotalCents: lineSubtotal,
      taxCents: lineTax,
      totalCents: lineTotal,
    });
  }

  const saleDiscount = normalizeDiscount(data.discountCents ?? 0, subtotalCents);
  const totalCents = Math.max(0, subtotalCents + taxCents - saleDiscount);
  if ((data.paidCents ?? 0) > totalCents) {
    throw badRequest("Paid amount cannot exceed the sale total.");
  }
  const paidCents = data.paidCents ?? 0;
  const balanceCents = totalCents - paidCents;

  const numberRow = (
    await db
      .select({ total: sql<number>`count(*)` })
      .from(sales)
      .where(eq(sales.workspaceId, workspaceId))
  )[0];
  const prefix = settings?.invoicePrefix ?? "INV";
  const number = `${prefix}-${String(Number(numberRow?.total ?? 0) + 1).padStart(
    4,
    "0",
  )}`;

  const trackedLines = calculated.filter((line) => line.product.trackInventory);
  const appliedDecrements: { levelId: string; quantity: number }[] = [];
  const revertDecrements = async () => {
    for (const entry of appliedDecrements) {
      try {
        await db
          .update(inventoryLevels)
          .set({
            quantity: sql`${inventoryLevels.quantity} + ${entry.quantity}`,
            updatedAt: now,
          })
          .where(eq(inventoryLevels.id, entry.levelId));
      } catch (cause) {
        console.error("Failed to revert stock decrement", entry.levelId, cause);
      }
    }
  };

  try {
    for (const line of trackedLines) {
      const level = levelMap.get(line.product.id);
      if (!level) {
        throw badRequest(`No stock record found for ${line.product.name} in this branch.`);
      }
      const result = await db
        .update(inventoryLevels)
        .set({
          quantity: sql`${inventoryLevels.quantity} - ${line.quantity}`,
          updatedAt: now,
        })
        .where(
          and(
            eq(inventoryLevels.id, level.id),
            gte(inventoryLevels.quantity, line.quantity),
          ),
        );
      if (Number(result?.meta?.changes ?? 0) !== 1) {
        throw badRequest(
          `Not enough stock for ${line.product.name}. Please check current levels and try again.`,
        );
      }
      appliedDecrements.push({ levelId: level.id, quantity: line.quantity });
    }
  } catch (cause) {
    await revertDecrements();
    throw cause;
  }

  const saleId = newId("sal");
  const queries = [
    db.insert(sales).values({
      id: saleId,
      workspaceId,
      branchId: data.branchId,
      customerId: data.customerId ?? null,
      customerName: customer?.name ?? data.customerName ?? null,
      number,
      status: "completed",
      paymentStatus: paymentStatus(totalCents, paidCents),
      paymentMethod: data.paymentMethod,
      currency: access.workspace.defaultCurrency,
      exchangeRateKhrPerUsd: settings?.exchangeRateKhrPerUsd ?? 4100,
      subtotalCents,
      discountCents: saleDiscount,
      taxCents,
      totalCents,
      paidCents,
      balanceCents,
      saleDate,
      dueDate: data.dueDate ?? null,
      note: data.note ?? null,
      createdByUserId: user.id,
    }),
  ];

  for (const line of calculated) {
    const itemId = newId("sit");
    queries.push(
      db.insert(saleItems).values({
        id: itemId,
        workspaceId,
        saleId,
        productId: line.product.id,
        productName: line.product.name,
        sku: line.product.sku,
        quantity: line.quantity,
        unitPriceCents: line.unitPriceCents,
        costPriceCents: line.product.costPriceCents,
        discountCents: line.discountCents,
        taxRateBasisPoints: line.rate,
        subtotalCents: line.subtotalCents,
        taxCents: line.taxCents,
        totalCents: line.totalCents,
      }) as never,
    );

    if (line.product.trackInventory) {
      queries.push(
        db.insert(stockMovements).values({
          id: newId("stk"),
          workspaceId,
          branchId: data.branchId,
          productId: line.product.id,
          type: "sale",
          quantity: -line.quantity,
          unitCostCents: line.product.costPriceCents,
          referenceType: "sale",
          referenceId: saleId,
          note: `Sale ${number}`,
          createdByUserId: user.id,
        }) as never,
      );
    }
  }

  await db.batch(queries as never);

  await writeAudit(context.env, {
    workspaceId,
    actorUserId: user.id,
    action: "sale.created",
    entityType: "sale",
    entityId: saleId,
    metadata: { number, totalCents, itemCount: calculated.length },
  });

  return created(context, {
    sale: {
      id: saleId,
      number,
      totalCents,
      paidCents,
      balanceCents,
      paymentStatus: paymentStatus(totalCents, paidCents),
    },
  });
});

saleRoutes.patch(
  "/:saleId/payment",
  requirePermission("sale:update"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const saleId = context.req.param("saleId");
    const data = await parseJson(context, salePaymentSchema);
    const db = getDb(context.env);

    const rows = await db
      .select()
      .from(sales)
      .where(
        and(eq(sales.id, saleId), eq(sales.workspaceId, access.workspace.id)),
      )
      .limit(1);
    const sale = rows[0];
    if (!sale) throw notFound("Sale not found.");
    if (sale.status === "void") {
      throw badRequest("Void sales cannot receive payments.");
    }

    const paidCents = Math.min(data.paidCents, sale.totalCents);
    const balanceCents = sale.totalCents - paidCents;

    await db
      .update(sales)
      .set({
        paidCents,
        balanceCents,
        paymentStatus: paymentStatus(sale.totalCents, paidCents),
        updatedAt: Date.now(),
      })
      .where(eq(sales.id, saleId));

    await writeAudit(context.env, {
      workspaceId: access.workspace.id,
      actorUserId: user.id,
      action: "sale.payment_updated",
      entityType: "sale",
      entityId: saleId,
      metadata: { paidCents, balanceCents },
    });

    return ok(context, {
      saleId,
      paidCents,
      balanceCents,
      paymentStatus: paymentStatus(sale.totalCents, paidCents),
    });
  },
);

saleRoutes.post(
  "/:saleId/void",
  requirePermission("sale:delete"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const saleId = context.req.param("saleId");
    const db = getDb(context.env);

    const saleRows = await db
      .select()
      .from(sales)
      .where(
        and(eq(sales.id, saleId), eq(sales.workspaceId, access.workspace.id)),
      )
      .limit(1);
    const sale = saleRows[0];
    if (!sale) throw notFound("Sale not found.");
    if (sale.status === "void") throw conflict("This sale is already void.");

    const items = await db
      .select()
      .from(saleItems)
      .where(eq(saleItems.saleId, saleId));
    const productIds = items
      .map((item) => item.productId)
      .filter((id): id is string => Boolean(id));

    const productRows =
      productIds.length > 0
        ? await db
            .select()
            .from(products)
            .where(inArray(products.id, productIds))
        : [];
    const productMap = new Map(
      productRows.map((product) => [product.id, product]),
    );
    const tracked = items.filter(
      (item) =>
        item.productId && productMap.get(item.productId)?.trackInventory,
    );

    const levels =
      tracked.length > 0
        ? await db
            .select()
            .from(inventoryLevels)
            .where(
              and(
                eq(inventoryLevels.branchId, sale.branchId),
                inArray(
                  inventoryLevels.productId,
                  tracked.map((item) => item.productId!),
                ),
              ),
            )
        : [];
    const levelMap = new Map(levels.map((level) => [level.productId, level]));

    const now = Date.now();
    const voidResult = await db
      .update(sales)
      .set({
        status: "void",
        voidedAt: now,
        balanceCents: 0,
        updatedAt: now,
      })
      .where(and(eq(sales.id, saleId), ne(sales.status, "void")));
    if (Number(voidResult?.meta?.changes ?? 0) !== 1) {
      throw conflict("This sale is already void.");
    }

    const queries: unknown[] = [];

    for (const item of tracked) {
      const product = productMap.get(item.productId!)!;
      const level = levelMap.get(item.productId!);
      if (level) {
        queries.push(
          db
            .update(inventoryLevels)
            .set({
              quantity: sql`${inventoryLevels.quantity} + ${item.quantity}`,
              updatedAt: now,
            })
            .where(eq(inventoryLevels.id, level.id)) as never,
        );
      } else {
        queries.push(
          db.insert(inventoryLevels).values({
            id: newId("inv"),
            workspaceId: access.workspace.id,
            branchId: sale.branchId,
            productId: product.id,
            quantity: item.quantity,
            updatedAt: now,
          }) as never,
        );
      }
      queries.push(
        db.insert(stockMovements).values({
          id: newId("stk"),
          workspaceId: access.workspace.id,
          branchId: sale.branchId,
          productId: product.id,
          type: "void",
          quantity: item.quantity,
          unitCostCents: item.costPriceCents,
          referenceType: "sale",
          referenceId: saleId,
          note: `Void ${sale.number}`,
          createdByUserId: user.id,
        }) as never,
      );
    }

  try {
    await db.batch(queries as never);
  } catch (cause) {
    try {
      await db
        .update(sales)
        .set({
          status: sale.status,
          voidedAt: sale.voidedAt,
          balanceCents: sale.balanceCents,
          updatedAt: now,
        })
        .where(eq(sales.id, saleId));
    } catch (revertCause) {
      console.error("Failed to revert void after restock failure", saleId, revertCause);
    }
    throw cause;
  }

    await writeAudit(context.env, {
      workspaceId: access.workspace.id,
      actorUserId: user.id,
      action: "sale.voided",
      entityType: "sale",
      entityId: saleId,
      metadata: { number: sale.number },
    });

    return ok(context, { saleId, status: "void" });
  },
);
