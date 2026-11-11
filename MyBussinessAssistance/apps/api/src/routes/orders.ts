import { Hono } from "hono";
import { and, desc, eq, gte, inArray, like, lte, notInArray, or, sql } from "drizzle-orm";
import {
  branches,
  customers,
  inventoryLevels,
  orderItems,
  orders,
  products,
  saleItems,
  sales,
  stockMovements,
  workspaceSettings,
} from "@mba/db";
import {
  createOrderSchema,
  updateOrderStatusSchema,
} from "@mba/shared";
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
import { notifyWorkspaceMembers } from "../services/notifications";

export const orderRoutes = new Hono<AppEnv>();

orderRoutes.get("/", requirePermission("sale:read"), async (context) => {
  const access = context.get("workspace");
  const db = getDb(context.env);
  const workspaceId = access.workspace.id;
  const search = context.req.query("search")?.trim();
  const status = context.req.query("status");
  const branchId = context.req.query("branchId");
  const customerId = context.req.query("customerId");
  const from = Number(context.req.query("from"));
  const to = Number(context.req.query("to"));

  const filters = [eq(orders.workspaceId, workspaceId)];
  if (
    status === "pending" ||
    status === "confirmed" ||
    status === "fulfilled" ||
    status === "cancelled"
  ) {
    filters.push(eq(orders.status, status));
  }
  if (branchId) filters.push(eq(orders.branchId, branchId));
  if (customerId) filters.push(eq(orders.customerId, customerId));
  if (Number.isFinite(from) && from > 0) filters.push(gte(orders.orderDate, from));
  if (Number.isFinite(to) && to > 0) filters.push(lte(orders.orderDate, to));
  if (search) {
    const term = `%${search}%`;
    filters.push(
      or(
        like(orders.number, term),
        like(orders.customerName, term),
        like(orders.note, term),
      )!,
    );
  }

  const rows = await db
    .select({
      id: orders.id,
      number: orders.number,
      status: orders.status,
      paymentMethod: orders.paymentMethod,
      customerId: orders.customerId,
      customerName: orders.customerName,
      branchId: orders.branchId,
      branchName: branches.name,
      currency: orders.currency,
      subtotalCents: orders.subtotalCents,
      discountCents: orders.discountCents,
      taxCents: orders.taxCents,
      totalCents: orders.totalCents,
      paidCents: orders.paidCents,
      orderDate: orders.orderDate,
      expectedDate: orders.expectedDate,
      saleId: orders.saleId,
      createdAt: orders.createdAt,
      itemCount: sql<number>`(select count(*) from order_items where order_id = ${orders.id})`,
    })
    .from(orders)
    .innerJoin(branches, eq(orders.branchId, branches.id))
    .where(and(...filters))
    .orderBy(desc(orders.orderDate))
    .limit(300);

  return ok(context, {
    orders: rows.map((row) => ({ ...row, itemCount: Number(row.itemCount) })),
    summary: {
      count: rows.length,
      pending: rows.filter((row) => row.status === "pending").length,
      confirmed: rows.filter((row) => row.status === "confirmed").length,
      fulfilled: rows.filter((row) => row.status === "fulfilled").length,
      totalCents: rows
        .filter((row) => row.status !== "cancelled")
        .reduce((sum, row) => sum + row.totalCents, 0),
    },
  });
});

orderRoutes.get(
  "/:orderId",
  requirePermission("sale:read"),
  async (context) => {
    const access = context.get("workspace");
    const db = getDb(context.env);
    const orderId = context.req.param("orderId");

    const rows = await db
      .select({
        order: orders,
        branchName: branches.name,
      })
      .from(orders)
      .innerJoin(branches, eq(orders.branchId, branches.id))
      .where(
        and(
          eq(orders.id, orderId),
          eq(orders.workspaceId, access.workspace.id),
        ),
      )
      .limit(1);

    const row = rows[0];
    if (!row) throw notFound("Order not found.");

    const items = await db
      .select()
      .from(orderItems)
      .where(eq(orderItems.orderId, orderId));

    return ok(context, {
      order: row.order,
      branchName: row.branchName,
      items,
    });
  },
);

orderRoutes.post("/", requirePermission("sale:create"), async (context) => {
  const access = context.get("workspace");
  const user = context.get("user");
  const data = await parseJson(context, createOrderSchema);
  const db = getDb(context.env);
  const workspaceId = access.workspace.id;
  const now = Date.now();
  const orderDate = data.orderDate ?? now;

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
      totalCents: lineSubtotal + lineTax,
    });
  }

  const orderDiscount = normalizeDiscount(
    data.discountCents ?? 0,
    subtotalCents,
  );
  const totalCents = Math.max(0, subtotalCents + taxCents - orderDiscount);
  if ((data.paidCents ?? 0) > totalCents) {
    throw badRequest("Paid amount cannot exceed the order total.");
  }
  const paidCents = data.paidCents ?? 0;

  const countRow = (
    await db
      .select({ total: sql<number>`count(*)` })
      .from(orders)
      .where(eq(orders.workspaceId, workspaceId))
  )[0];
  const number = `ORD-${String(Number(countRow?.total ?? 0) + 1).padStart(
    4,
    "0",
  )}`;

  const orderId = newId("ord");
  const queries = [
    db.insert(orders).values({
      id: orderId,
      workspaceId,
      branchId: data.branchId,
      customerId: data.customerId ?? null,
      customerName: customer?.name ?? data.customerName ?? null,
      number,
      status: "pending",
      paymentMethod: data.paymentMethod,
      currency: access.workspace.defaultCurrency,
      subtotalCents,
      discountCents: orderDiscount,
      taxCents,
      totalCents,
      paidCents,
      orderDate,
      expectedDate: data.expectedDate ?? null,
      note: data.note ?? null,
      createdByUserId: user.id,
    }),
  ];

  for (const line of calculated) {
    queries.push(
      db.insert(orderItems).values({
        id: newId("oit"),
        workspaceId,
        orderId,
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
  }

  await db.batch(queries as never);

  await writeAudit(context.env, {
    workspaceId,
    actorUserId: user.id,
    action: "order.created",
    entityType: "order",
    entityId: orderId,
    metadata: { number, totalCents, itemCount: calculated.length },
  });

  await notifyWorkspaceMembers(context.env, {
    workspaceId,
    actorUserId: user.id,
    type: "order.created",
    title: `New order ${number}`,
    body: `${customer?.name ?? data.customerName ?? "Walk-in customer"} · ${(
      totalCents / 100
    ).toFixed(2)} ${access.workspace.defaultCurrency}`,
  });

  return created(context, {
    order: {
      id: orderId,
      number,
      status: "pending",
      totalCents,
      paidCents,
    },
  });
});

orderRoutes.patch(
  "/:orderId/status",
  requirePermission("sale:update"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const orderId = context.req.param("orderId");
    const data = await parseJson(context, updateOrderStatusSchema);
    const db = getDb(context.env);

    const rows = await db
      .select()
      .from(orders)
      .where(
        and(
          eq(orders.id, orderId),
          eq(orders.workspaceId, access.workspace.id),
        ),
      )
      .limit(1);
    const order = rows[0];
    if (!order) throw notFound("Order not found.");

    if (order.status === "fulfilled") {
      throw conflict("Fulfilled orders cannot be changed.");
    }
    if (order.status === "cancelled") {
      throw conflict("Cancelled orders cannot be changed.");
    }

    const now = Date.now();
    await db
      .update(orders)
      .set({ status: data.status, updatedAt: now })
      .where(eq(orders.id, orderId));

    await writeAudit(context.env, {
      workspaceId: access.workspace.id,
      actorUserId: user.id,
      action: "order.status_changed",
      entityType: "order",
      entityId: orderId,
      metadata: { from: order.status, to: data.status },
    });

    await notifyWorkspaceMembers(context.env, {
      workspaceId: access.workspace.id,
      actorUserId: user.id,
      type: "order.status_changed",
      title: `Order ${order.number} is ${data.status}`,
      body: `Status changed from ${order.status} to ${data.status}.`,
    });

    return ok(context, { orderId, status: data.status });
  },
);

orderRoutes.post(
  "/:orderId/fulfill",
  requirePermission("sale:create"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const orderId = context.req.param("orderId");
    const db = getDb(context.env);
    const workspaceId = access.workspace.id;

    const orderRows = await db
      .select()
      .from(orders)
      .where(and(eq(orders.id, orderId), eq(orders.workspaceId, workspaceId)))
      .limit(1);
    const order = orderRows[0];
    if (!order) throw notFound("Order not found.");
    if (order.status === "fulfilled") {
      throw conflict("This order has already been fulfilled.");
    }
    if (order.status === "cancelled") {
      throw conflict("Cancelled orders cannot be fulfilled.");
    }

    const items = await db
      .select()
      .from(orderItems)
      .where(eq(orderItems.orderId, orderId));
    if (items.length === 0) {
      throw badRequest("This order has no items.");
    }

    const settings = (
      await db
        .select()
        .from(workspaceSettings)
        .where(eq(workspaceSettings.workspaceId, workspaceId))
        .limit(1)
    )[0];

    const productIds = items
      .map((item) => item.productId)
      .filter((id): id is string => Boolean(id));
    const productRows =
      productIds.length > 0
        ? await db
            .select()
            .from(products)
            .where(and(inArray(products.id, productIds), eq(products.workspaceId, workspaceId)))
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
                eq(inventoryLevels.workspaceId, workspaceId),
                eq(inventoryLevels.branchId, order.branchId),
                inArray(
                  inventoryLevels.productId,
                  tracked.map((item) => item.productId!),
                ),
              ),
            )
        : [];
    const levelMap = new Map(levels.map((level) => [level.productId, level]));

    for (const item of tracked) {
      const available = levelMap.get(item.productId!)?.quantity ?? 0;
      if (available < item.quantity) {
        throw badRequest(
          `Not enough stock for ${item.productName}. Available: ${available}.`,
        );
      }
    }

    const countRow = (
      await db
        .select({ total: sql<number>`count(*)` })
        .from(sales)
        .where(eq(sales.workspaceId, workspaceId))
    )[0];
    const prefix = settings?.invoicePrefix ?? "INV";
    const number = `${prefix}-${String(
      Number(countRow?.total ?? 0) + 1,
    ).padStart(4, "0")}`;

    const now = Date.now();
    const saleId = newId("sal");
    const paidCents = Math.min(order.paidCents, order.totalCents);
    const balanceCents = order.totalCents - paidCents;

    const guard = await db
      .update(orders)
      .set({ status: "fulfilled", saleId, updatedAt: now })
      .where(
        and(
          eq(orders.id, orderId),
          notInArray(orders.status, ["fulfilled", "cancelled"]),
        ),
      );
    if (Number(guard?.meta?.changes ?? 0) !== 1) {
      throw conflict("This order has already been fulfilled or cancelled.");
    }

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
          console.error(
            "Failed to revert order stock decrement",
            entry.levelId,
            cause,
          );
        }
      }
    };
    const revertStatus = async () => {
      try {
        await db
          .update(orders)
          .set({
            status: order.status,
            saleId: order.saleId ?? null,
            updatedAt: now,
          })
          .where(eq(orders.id, orderId));
      } catch (cause) {
        console.error("Failed to revert order status", orderId, cause);
      }
    };

    try {
      for (const item of tracked) {
        const level = levelMap.get(item.productId!);
        if (!level) {
          throw badRequest(
            `No stock record found for ${item.productName} in this branch.`,
          );
        }
        const degraded = await db
          .update(inventoryLevels)
          .set({
            quantity: sql`${inventoryLevels.quantity} - ${item.quantity}`,
            updatedAt: now,
          })
          .where(
            and(
              eq(inventoryLevels.id, level.id),
              gte(inventoryLevels.quantity, item.quantity),
            ),
          );
        if (Number(degraded?.meta?.changes ?? 0) !== 1) {
          throw badRequest(
            `Not enough stock for ${item.productName}. Please refresh and try again.`,
          );
        }
        appliedDecrements.push({ levelId: level.id, quantity: item.quantity });
      }
    } catch (cause) {
      await revertDecrements();
      await revertStatus();
      throw cause;
    }

    const queries = [
      db.insert(sales).values({
        id: saleId,
        workspaceId,
        branchId: order.branchId,
        customerId: order.customerId,
        customerName: order.customerName,
        number,
        status: "completed",
        paymentStatus: paymentStatus(order.totalCents, paidCents),
        paymentMethod: order.paymentMethod,
        currency: order.currency,
        exchangeRateKhrPerUsd: settings?.exchangeRateKhrPerUsd ?? 4100,
        subtotalCents: order.subtotalCents,
        discountCents: order.discountCents,
        taxCents: order.taxCents,
        totalCents: order.totalCents,
        paidCents,
        balanceCents,
        saleDate: now,
        note: order.note,
        createdByUserId: user.id,
      }),
    ];

    for (const item of items) {
      queries.push(
        db.insert(saleItems).values({
          id: newId("sit"),
          workspaceId,
          saleId,
          productId: item.productId,
          productName: item.productName,
          sku: item.sku,
          quantity: item.quantity,
          unitPriceCents: item.unitPriceCents,
          costPriceCents: item.costPriceCents,
          discountCents: item.discountCents,
          taxRateBasisPoints: item.taxRateBasisPoints,
          subtotalCents: item.subtotalCents,
          taxCents: item.taxCents,
          totalCents: item.totalCents,
        }) as never,
      );
    }

    for (const item of tracked) {
      queries.push(
        db.insert(stockMovements).values({
          id: newId("stk"),
          workspaceId,
          branchId: order.branchId,
          productId: item.productId!,
          type: "sale",
          quantity: -item.quantity,
          unitCostCents: item.costPriceCents,
          referenceType: "order",
          referenceId: orderId,
          note: `Order ${order.number} fulfilled as ${number}`,
          createdByUserId: user.id,
        }) as never,
      );
    }

    try {
      await db.batch(queries as never);
    } catch (cause) {
      await revertDecrements();
      await revertStatus();
      throw cause;
    }

    await writeAudit(context.env, {
      workspaceId,
      actorUserId: user.id,
      action: "order.fulfilled",
      entityType: "order",
      entityId: orderId,
      metadata: { number: order.number, saleId, saleNumber: number },
    });

    await notifyWorkspaceMembers(context.env, {
      workspaceId,
      actorUserId: user.id,
      type: "order.fulfilled",
      title: `Order ${order.number} fulfilled`,
      body: `Sale ${number} was created from this order.`,
    });

    return ok(context, {
      orderId,
      status: "fulfilled",
      saleId,
      saleNumber: number,
      totalCents: order.totalCents,
      paidCents,
      balanceCents,
    });
  },
);
