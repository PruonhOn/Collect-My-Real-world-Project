import { Hono } from "hono";
import { and, desc, eq, gte, inArray, like, lte, or, sql } from "drizzle-orm";
import {
  branches,
  inventoryLevels,
  products,
  purchaseItems,
  purchases,
  stockMovements,
  suppliers,
  users,
  workspaceSettings,
} from "@mba/db";
import { createPurchaseSchema, purchasePaymentSchema } from "@mba/shared";
import type { AppEnv } from "../env";
import { getDb } from "../db";
import { badRequest, notFound } from "../lib/errors";
import { created, ok } from "../lib/response";
import { newId } from "../lib/ids";
import { parseJson } from "../lib/validate";
import { paymentStatus } from "../lib/money";
import { requirePermission } from "../middleware/workspace";
import { writeAudit } from "../services/audit";
import { assertTransactionsAvailable } from "../services/entitlements";

export const purchaseRoutes = new Hono<AppEnv>();

purchaseRoutes.get("/", requirePermission("purchase:read"), async (context) => {
  const access = context.get("workspace");
  const db = getDb(context.env);
  const workspaceId = access.workspace.id;
  const search = context.req.query("search")?.trim();
  const supplierId = context.req.query("supplierId");
  const from = Number(context.req.query("from"));
  const to = Number(context.req.query("to"));

  const filters = [eq(purchases.workspaceId, workspaceId)];
  if (supplierId) filters.push(eq(purchases.supplierId, supplierId));
  if (Number.isFinite(from) && from > 0) {
    filters.push(gte(purchases.purchaseDate, from));
  }
  if (Number.isFinite(to) && to > 0) {
    filters.push(lte(purchases.purchaseDate, to));
  }
  if (search) {
    const term = `%${search}%`;
    filters.push(
      or(
        like(purchases.number, term),
        like(purchases.supplierName, term),
        like(purchases.note, term),
      )!,
    );
  }

  const rows = await db
    .select({
      id: purchases.id,
      number: purchases.number,
      status: purchases.status,
      paymentStatus: purchases.paymentStatus,
      paymentMethod: purchases.paymentMethod,
      supplierId: purchases.supplierId,
      supplierName: purchases.supplierName,
      branchId: purchases.branchId,
      branchName: branches.name,
      currency: purchases.currency,
      subtotalCents: purchases.subtotalCents,
      totalCents: purchases.totalCents,
      paidCents: purchases.paidCents,
      balanceCents: purchases.balanceCents,
      purchaseDate: purchases.purchaseDate,
      createdAt: purchases.createdAt,
      itemCount: sql<number>`(select count(*) from purchase_items where purchase_id = ${purchases.id})`,
    })
    .from(purchases)
    .innerJoin(branches, eq(purchases.branchId, branches.id))
    .where(and(...filters))
    .orderBy(desc(purchases.purchaseDate))
    .limit(300);

  return ok(context, {
    purchases: rows.map((row) => ({
      ...row,
      itemCount: Number(row.itemCount),
    })),
    summary: {
      totalCents: rows
        .filter((row) => row.status === "received")
        .reduce((sum, row) => sum + row.totalCents, 0),
      outstandingCents: rows
        .filter((row) => row.status === "received")
        .reduce((sum, row) => sum + row.balanceCents, 0),
      count: rows.length,
    },
  });
});

purchaseRoutes.get(
  "/:purchaseId",
  requirePermission("purchase:read"),
  async (context) => {
    const access = context.get("workspace");
    const db = getDb(context.env);
    const purchaseId = context.req.param("purchaseId");

    const rows = await db
      .select({
        purchase: purchases,
        branchName: branches.name,
        createdByName: users.name,
      })
      .from(purchases)
      .innerJoin(branches, eq(purchases.branchId, branches.id))
      .leftJoin(users, eq(purchases.createdByUserId, users.id))
      .where(
        and(
          eq(purchases.id, purchaseId),
          eq(purchases.workspaceId, access.workspace.id),
        ),
      )
      .limit(1);

    if (!rows[0]) throw notFound("Purchase not found.");

    const items = await db
      .select()
      .from(purchaseItems)
      .where(eq(purchaseItems.purchaseId, purchaseId));

    return ok(context, {
      purchase: rows[0].purchase,
      branch: { name: rows[0].branchName },
      createdByName: rows[0].createdByName,
      items,
    });
  },
);

purchaseRoutes.post(
  "/",
  requirePermission("purchase:create"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const data = await parseJson(context, createPurchaseSchema);
    const db = getDb(context.env);
    const workspaceId = access.workspace.id;
    const now = Date.now();
    const purchaseDate = data.purchaseDate ?? now;

    await assertTransactionsAvailable(context.env, workspaceId);

    const branch = await db
      .select()
      .from(branches)
      .where(
        and(
          eq(branches.id, data.branchId),
          eq(branches.workspaceId, workspaceId),
        ),
      )
      .limit(1);
    if (!branch[0]) throw badRequest("Select a valid branch.");

    const supplier = data.supplierId
      ? (
          await db
            .select()
            .from(suppliers)
            .where(
              and(
                eq(suppliers.id, data.supplierId),
                eq(suppliers.workspaceId, workspaceId),
              ),
            )
            .limit(1)
        )[0]
      : null;
    if (data.supplierId && !supplier) {
      throw badRequest("Select a valid supplier.");
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
      { quantity: number; unitCostCents?: number }
    >();
    for (const item of data.items) {
      const existing = merged.get(item.productId);
      if (existing) {
        existing.quantity += item.quantity;
        if (item.unitCostCents !== undefined) {
          existing.unitCostCents = item.unitCostCents;
        }
      } else {
        merged.set(item.productId, {
          quantity: item.quantity,
          unitCostCents: item.unitCostCents,
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
    const productMap = new Map(
      productRows.map((product) => [product.id, product]),
    );
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
    const onHandRows =
      trackedIds.length > 0
        ? await db
            .select({
              productId: inventoryLevels.productId,
              quantity: sql<number>`coalesce(sum(${inventoryLevels.quantity}), 0)`,
            })
            .from(inventoryLevels)
            .where(
              and(
                eq(inventoryLevels.workspaceId, workspaceId),
                inArray(inventoryLevels.productId, trackedIds),
              ),
            )
            .groupBy(inventoryLevels.productId)
        : [];
    const onHandMap = new Map(
      onHandRows.map((row) => [row.productId, Number(row.quantity)]),
    );

    const calculated = [];
    let totalCents = 0;
    for (const [productId, input] of merged) {
      const product = productMap.get(productId)!;
      const unitCostCents = input.unitCostCents ?? product.costPriceCents;
      const lineTotal = unitCostCents * input.quantity;
      totalCents += lineTotal;
      calculated.push({
        product,
        quantity: input.quantity,
        unitCostCents,
        subtotalCents: lineTotal,
      });
    }

    if ((data.paidCents ?? 0) > totalCents) {
      throw badRequest("Paid amount cannot exceed the purchase total.");
    }
    const paidCents = data.paidCents ?? 0;
    const balanceCents = totalCents - paidCents;

    const numberRow = (
      await db
        .select({ total: sql<number>`count(*)` })
        .from(purchases)
        .where(eq(purchases.workspaceId, workspaceId))
    )[0];
    const number = `PO-${String(
      Number(numberRow?.total ?? 0) + 1,
    ).padStart(4, "0")}`;

    const purchaseId = newId("pur");
    const queries = [
      db.insert(purchases).values({
        id: purchaseId,
        workspaceId,
        branchId: data.branchId,
        supplierId: data.supplierId ?? null,
        supplierName: supplier?.name ?? data.supplierName ?? null,
        number,
        status: "received",
        paymentStatus: paymentStatus(totalCents, paidCents),
        paymentMethod: data.paymentMethod,
        currency: access.workspace.defaultCurrency,
        subtotalCents: totalCents,
        taxCents: 0,
        totalCents,
        paidCents,
        balanceCents,
        purchaseDate,
        note: data.note ?? null,
        stockApplied: true,
        createdByUserId: user.id,
      }),
    ];

    for (const line of calculated) {
      queries.push(
        db.insert(purchaseItems).values({
          id: newId("pit"),
          workspaceId,
          purchaseId,
          productId: line.product.id,
          productName: line.product.name,
          sku: line.product.sku,
          quantity: line.quantity,
          unitCostCents: line.unitCostCents,
          subtotalCents: line.subtotalCents,
        }) as never,
      );

      const oldQty = Math.max(0, onHandMap.get(line.product.id) ?? 0);
      const oldCost = line.product.costPriceCents;
      const receivedCost = line.unitCostCents * line.quantity;
      const newCost =
        oldQty + line.quantity > 0
          ? Math.round((oldQty * oldCost + receivedCost) / (oldQty + line.quantity))
          : line.unitCostCents;

      queries.push(
        db
          .update(products)
          .set({
            costPriceCents: newCost,
            updatedAt: now,
          })
          .where(eq(products.id, line.product.id)) as never,
      );

      if (line.product.trackInventory) {
        const level = levelMap.get(line.product.id);
        if (level) {
          queries.push(
            db
              .update(inventoryLevels)
              .set({
                quantity: sql`${inventoryLevels.quantity} + ${line.quantity}`,
                updatedAt: now,
              })
              .where(eq(inventoryLevels.id, level.id)) as never,
          );
        } else {
          queries.push(
            db.insert(inventoryLevels).values({
              id: newId("inv"),
              workspaceId,
              branchId: data.branchId,
              productId: line.product.id,
              quantity: line.quantity,
              updatedAt: now,
            }) as never,
          );
        }

        queries.push(
          db.insert(stockMovements).values({
            id: newId("stk"),
            workspaceId,
            branchId: data.branchId,
            productId: line.product.id,
            type: "purchase",
            quantity: line.quantity,
            unitCostCents: line.unitCostCents,
            referenceType: "purchase",
            referenceId: purchaseId,
            note: `Purchase ${number}`,
            createdByUserId: user.id,
          }) as never,
        );
      }
    }

    await db.batch(queries as never);

    await writeAudit(context.env, {
      workspaceId,
      actorUserId: user.id,
      action: "purchase.created",
      entityType: "purchase",
      entityId: purchaseId,
      metadata: { number, totalCents, itemCount: calculated.length },
    });

    return created(context, {
      purchase: {
        id: purchaseId,
        number,
        totalCents,
        paidCents,
        balanceCents,
        paymentStatus: paymentStatus(totalCents, paidCents),
      },
    });
  },
);

purchaseRoutes.patch(
  "/:purchaseId/payment",
  requirePermission("purchase:update"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const purchaseId = context.req.param("purchaseId");
    const data = await parseJson(context, purchasePaymentSchema);
    const db = getDb(context.env);

    const rows = await db
      .select()
      .from(purchases)
      .where(
        and(
          eq(purchases.id, purchaseId),
          eq(purchases.workspaceId, access.workspace.id),
        ),
      )
      .limit(1);
    const purchase = rows[0];
    if (!purchase) throw notFound("Purchase not found.");

    const paidCents = Math.min(data.paidCents, purchase.totalCents);
    const balanceCents = purchase.totalCents - paidCents;

    await db
      .update(purchases)
      .set({
        paidCents,
        balanceCents,
        paymentStatus: paymentStatus(purchase.totalCents, paidCents),
        updatedAt: Date.now(),
      })
      .where(eq(purchases.id, purchaseId));

    await writeAudit(context.env, {
      workspaceId: access.workspace.id,
      actorUserId: user.id,
      action: "purchase.payment_updated",
      entityType: "purchase",
      entityId: purchaseId,
      metadata: { paidCents, balanceCents },
    });

    return ok(context, {
      purchaseId,
      paidCents,
      balanceCents,
      paymentStatus: paymentStatus(purchase.totalCents, paidCents),
    });
  },
);
