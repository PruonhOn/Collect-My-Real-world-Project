import { Hono } from "hono";
import {
  and,
  desc,
  eq,
  gte,
  inArray,
  like,
  or,
  sql,
} from "drizzle-orm";
import { alias } from "drizzle-orm/sqlite-core";
import {
  branches,
  inventoryLevels,
  products,
  stockMovements,
  stockTransferItems,
  stockTransfers,
} from "@mba/db";
import { createTransferSchema } from "@mba/shared";
import type { AppEnv } from "../env";
import { getDb } from "../db";
import { badRequest, conflict, notFound } from "../lib/errors";
import { created, ok } from "../lib/response";
import { newId } from "../lib/ids";
import { parseJson } from "../lib/validate";
import { requirePermission } from "../middleware/workspace";
import { writeAudit } from "../services/audit";
import { notifyWorkspaceMembers } from "../services/notifications";

export const transferRoutes = new Hono<AppEnv>();

const fromBranch = alias(branches, "from_branch");
const toBranch = alias(branches, "to_branch");

transferRoutes.get(
  "/",
  requirePermission("inventory:read"),
  async (context) => {
    const access = context.get("workspace");
    const db = getDb(context.env);
    const workspaceId = access.workspace.id;
    const status = context.req.query("status");
    const branchId = context.req.query("branchId");
    const search = context.req.query("search")?.trim();

    const filters = [eq(stockTransfers.workspaceId, workspaceId)];
    if (status === "pending" || status === "completed" || status === "cancelled") {
      filters.push(eq(stockTransfers.status, status));
    }
    if (branchId) {
      filters.push(
        or(
          eq(stockTransfers.fromBranchId, branchId),
          eq(stockTransfers.toBranchId, branchId),
        )!,
      );
    }
    if (search) {
      const term = `%${search}%`;
      filters.push(
        or(
          like(stockTransfers.number, term),
          like(stockTransfers.note, term),
        )!,
      );
    }

    const rows = await db
      .select({
        id: stockTransfers.id,
        number: stockTransfers.number,
        status: stockTransfers.status,
        note: stockTransfers.note,
        fromBranchId: stockTransfers.fromBranchId,
        fromBranchName: fromBranch.name,
        toBranchId: stockTransfers.toBranchId,
        toBranchName: toBranch.name,
        completedAt: stockTransfers.completedAt,
        createdAt: stockTransfers.createdAt,
        updatedAt: stockTransfers.updatedAt,
        itemCount: sql<number>`(select count(*) from stock_transfer_items where transfer_id = ${stockTransfers.id})`,
        totalQuantity: sql<number>`(select coalesce(sum(quantity), 0) from stock_transfer_items where transfer_id = ${stockTransfers.id})`,
      })
      .from(stockTransfers)
      .innerJoin(fromBranch, eq(stockTransfers.fromBranchId, fromBranch.id))
      .innerJoin(toBranch, eq(stockTransfers.toBranchId, toBranch.id))
      .where(and(...filters))
      .orderBy(desc(stockTransfers.createdAt))
      .limit(300);

    return ok(context, {
      transfers: rows.map((row) => ({
        ...row,
        itemCount: Number(row.itemCount),
        totalQuantity: Number(row.totalQuantity),
      })),
      summary: {
        pending: rows.filter((row) => row.status === "pending").length,
        completed: rows.filter((row) => row.status === "completed").length,
        count: rows.length,
      },
    });
  },
);

transferRoutes.get(
  "/:transferId",
  requirePermission("inventory:read"),
  async (context) => {
    const access = context.get("workspace");
    const db = getDb(context.env);
    const transferId = context.req.param("transferId");

    const rows = await db
      .select({
        transfer: stockTransfers,
        fromBranchName: fromBranch.name,
        toBranchName: toBranch.name,
      })
      .from(stockTransfers)
      .innerJoin(fromBranch, eq(stockTransfers.fromBranchId, fromBranch.id))
      .innerJoin(toBranch, eq(stockTransfers.toBranchId, toBranch.id))
      .where(
        and(
          eq(stockTransfers.id, transferId),
          eq(stockTransfers.workspaceId, access.workspace.id),
        ),
      )
      .limit(1);

    const row = rows[0];
    if (!row) throw notFound("Stock transfer not found.");

    const items = await db
      .select()
      .from(stockTransferItems)
      .where(eq(stockTransferItems.transferId, transferId));

    return ok(context, {
      transfer: row.transfer,
      fromBranchName: row.fromBranchName,
      toBranchName: row.toBranchName,
      items,
    });
  },
);

transferRoutes.post(
  "/",
  requirePermission("inventory:transfer"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const data = await parseJson(context, createTransferSchema);
    const db = getDb(context.env);
    const workspaceId = access.workspace.id;

    if (data.fromBranchId === data.toBranchId) {
      throw badRequest("Source and destination branches must be different.");
    }

    const branchRows = await db
      .select()
      .from(branches)
      .where(
        and(
          eq(branches.workspaceId, workspaceId),
          inArray(branches.id, [data.fromBranchId, data.toBranchId]),
        ),
      );
    if (branchRows.length !== 2) {
      throw badRequest("Select valid source and destination branches.");
    }

    const merged = new Map<string, number>();
    for (const item of data.items) {
      merged.set(item.productId, (merged.get(item.productId) ?? 0) + item.quantity);
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
      const product = productMap.get(productId);
      if (!product) {
        throw badRequest("One of the selected products is no longer available.");
      }
      if (!product.trackInventory) {
        throw badRequest(`${product.name} does not track inventory.`);
      }
    }

    const countRow = (
      await db
        .select({ total: sql<number>`count(*)` })
        .from(stockTransfers)
        .where(eq(stockTransfers.workspaceId, workspaceId))
    )[0];
    const number = `TRF-${String(Number(countRow?.total ?? 0) + 1).padStart(
      4,
      "0",
    )}`;

    const transferId = newId("trf");
    const queries = [
      db.insert(stockTransfers).values({
        id: transferId,
        workspaceId,
        fromBranchId: data.fromBranchId,
        toBranchId: data.toBranchId,
        number,
        status: "pending",
        note: data.note ?? null,
        createdByUserId: user.id,
      }),
    ];

    for (const [productId, quantity] of merged) {
      const product = productMap.get(productId)!;
      queries.push(
        db.insert(stockTransferItems).values({
          id: newId("tri"),
          workspaceId,
          transferId,
          productId,
          productName: product.name,
          sku: product.sku,
          quantity,
        }) as never,
      );
    }

    await db.batch(queries as never);

    await writeAudit(context.env, {
      workspaceId,
      actorUserId: user.id,
      action: "transfer.created",
      entityType: "stock_transfer",
      entityId: transferId,
      metadata: {
        number,
        fromBranchId: data.fromBranchId,
        toBranchId: data.toBranchId,
        itemCount: merged.size,
      },
    });

    const fromName =
      branchRows.find((branch) => branch.id === data.fromBranchId)?.name ??
      "source branch";
    const toName =
      branchRows.find((branch) => branch.id === data.toBranchId)?.name ??
      "destination branch";

    await notifyWorkspaceMembers(context.env, {
      workspaceId,
      actorUserId: user.id,
      type: "transfer.created",
      title: `Stock transfer ${number} created`,
      body: `${merged.size} product${merged.size === 1 ? "" : "s"} from ${fromName} to ${toName}.`,
    });

    return created(context, {
      transfer: {
        id: transferId,
        number,
        status: "pending",
      },
    });
  },
);

transferRoutes.post(
  "/:transferId/complete",
  requirePermission("inventory:transfer"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const transferId = context.req.param("transferId");
    const db = getDb(context.env);
    const workspaceId = access.workspace.id;

    const transferRows = await db
      .select()
      .from(stockTransfers)
      .where(
        and(
          eq(stockTransfers.id, transferId),
          eq(stockTransfers.workspaceId, workspaceId),
        ),
      )
      .limit(1);
    const transfer = transferRows[0];
    if (!transfer) throw notFound("Stock transfer not found.");
    if (transfer.status !== "pending") {
      throw conflict("Only pending transfers can be completed.");
    }

    const items = await db
      .select()
      .from(stockTransferItems)
      .where(eq(stockTransferItems.transferId, transferId));
    if (items.length === 0) {
      throw badRequest("This transfer has no items.");
    }

    const productIds = items
      .map((item) => item.productId)
      .filter((id): id is string => Boolean(id));

    const productRows = await db
      .select()
      .from(products)
      .where(and(inArray(products.id, productIds), eq(products.workspaceId, workspaceId)));
    const productMap = new Map(
      productRows.map((product) => [product.id, product]),
    );

    const levels = await db
      .select()
      .from(inventoryLevels)
      .where(
        and(
          eq(inventoryLevels.workspaceId, workspaceId),
          inArray(inventoryLevels.branchId, [
            transfer.fromBranchId,
            transfer.toBranchId,
          ]),
          inArray(inventoryLevels.productId, productIds),
        ),
      );

    const levelMap = new Map(
      levels.map((level) => [`${level.branchId}:${level.productId}`, level]),
    );

    for (const item of items) {
      if (!item.productId) continue;
      const source = levelMap.get(
        `${transfer.fromBranchId}:${item.productId}`,
      );
      const available = source?.quantity ?? 0;
      if (available < item.quantity) {
        throw badRequest(
          `Not enough stock for ${item.productName}. Available: ${available}.`,
        );
      }
    }

    const now = Date.now();
    const guard = await db
      .update(stockTransfers)
      .set({ status: "completed", completedAt: now, updatedAt: now })
      .where(
        and(
          eq(stockTransfers.id, transferId),
          eq(stockTransfers.status, "pending"),
        ),
      );
    if (Number(guard?.meta?.changes ?? 0) !== 1) {
      throw conflict("Only pending transfers can be completed.");
    }

    const appliedSource: { levelId: string; quantity: number }[] = [];
    const revertSource = async () => {
      for (const entry of appliedSource) {
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
            "Failed to revert transfer stock decrement",
            entry.levelId,
            cause,
          );
        }
      }
    };
    const revertStatus = async () => {
      try {
        await db
          .update(stockTransfers)
          .set({ status: "pending", completedAt: null, updatedAt: now })
          .where(eq(stockTransfers.id, transferId));
      } catch (cause) {
        console.error("Failed to revert transfer status", transferId, cause);
      }
    };

    try {
      for (const item of items) {
        if (!item.productId) continue;
        const source = levelMap.get(
          `${transfer.fromBranchId}:${item.productId}`,
        );
        if (!source) {
          throw badRequest(
            `No stock record found for ${item.productName} in the source branch.`,
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
              eq(inventoryLevels.id, source.id),
              gte(inventoryLevels.quantity, item.quantity),
            ),
          );
        if (Number(degraded?.meta?.changes ?? 0) !== 1) {
          throw badRequest(
            `Not enough stock for ${item.productName}. Please refresh and try again.`,
          );
        }
        appliedSource.push({ levelId: source.id, quantity: item.quantity });
      }
    } catch (cause) {
      await revertSource();
      await revertStatus();
      throw cause;
    }

    const queries: unknown[] = [];

    for (const item of items) {
      if (!item.productId) continue;
      const product = productMap.get(item.productId);
      const destination = levelMap.get(
        `${transfer.toBranchId}:${item.productId}`,
      );

      if (destination) {
        queries.push(
          db
            .update(inventoryLevels)
            .set({
              quantity: sql`${inventoryLevels.quantity} + ${item.quantity}`,
              updatedAt: now,
            })
            .where(eq(inventoryLevels.id, destination.id)) as never,
        );
      } else {
        queries.push(
          db.insert(inventoryLevels).values({
            id: newId("inv"),
            workspaceId,
            branchId: transfer.toBranchId,
            productId: item.productId,
            quantity: item.quantity,
            updatedAt: now,
          }) as never,
        );
      }

      queries.push(
        db.insert(stockMovements).values({
          id: newId("stk"),
          workspaceId,
          branchId: transfer.fromBranchId,
          productId: item.productId,
          type: "transfer_out",
          quantity: -item.quantity,
          unitCostCents: product?.costPriceCents ?? null,
          referenceType: "stock_transfer",
          referenceId: transferId,
          note: `Transfer ${transfer.number}`,
          createdByUserId: user.id,
        }) as never,
      );
      queries.push(
        db.insert(stockMovements).values({
          id: newId("stk"),
          workspaceId,
          branchId: transfer.toBranchId,
          productId: item.productId,
          type: "transfer_in",
          quantity: item.quantity,
          unitCostCents: product?.costPriceCents ?? null,
          referenceType: "stock_transfer",
          referenceId: transferId,
          note: `Transfer ${transfer.number}`,
          createdByUserId: user.id,
        }) as never,
      );
    }

    try {
      await db.batch(queries as never);
    } catch (cause) {
      await revertSource();
      await revertStatus();
      throw cause;
    }

    await writeAudit(context.env, {
      workspaceId,
      actorUserId: user.id,
      action: "transfer.completed",
      entityType: "stock_transfer",
      entityId: transferId,
      metadata: { number: transfer.number, itemCount: items.length },
    });

    await notifyWorkspaceMembers(context.env, {
      workspaceId,
      actorUserId: user.id,
      type: "transfer.completed",
      title: `Stock transfer ${transfer.number} completed`,
      body: "Stock has been moved between branches.",
    });

    return ok(context, {
      transferId,
      status: "completed",
      completedAt: now,
    });
  },
);

transferRoutes.post(
  "/:transferId/cancel",
  requirePermission("inventory:transfer"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const transferId = context.req.param("transferId");
    const db = getDb(context.env);

    const transferRows = await db
      .select()
      .from(stockTransfers)
      .where(
        and(
          eq(stockTransfers.id, transferId),
          eq(stockTransfers.workspaceId, access.workspace.id),
        ),
      )
      .limit(1);
    const transfer = transferRows[0];
    if (!transfer) throw notFound("Stock transfer not found.");
    if (transfer.status !== "pending") {
      throw conflict("Only pending transfers can be cancelled.");
    }

    const cancelled = await db
      .update(stockTransfers)
      .set({ status: "cancelled", updatedAt: Date.now() })
      .where(
        and(
          eq(stockTransfers.id, transferId),
          eq(stockTransfers.status, "pending"),
        ),
      );
    if (Number(cancelled?.meta?.changes ?? 0) !== 1) {
      throw conflict("Only pending transfers can be cancelled.");
    }

    await writeAudit(context.env, {
      workspaceId: access.workspace.id,
      actorUserId: user.id,
      action: "transfer.cancelled",
      entityType: "stock_transfer",
      entityId: transferId,
      metadata: { number: transfer.number },
    });

    await notifyWorkspaceMembers(context.env, {
      workspaceId: access.workspace.id,
      actorUserId: user.id,
      type: "transfer.cancelled",
      title: `Stock transfer ${transfer.number} cancelled`,
      body: "This pending transfer will not be processed.",
    });

    return ok(context, { transferId, status: "cancelled" });
  },
);
