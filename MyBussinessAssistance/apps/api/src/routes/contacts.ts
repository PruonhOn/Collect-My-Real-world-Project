import { Hono } from "hono";
import { and, desc, eq, like, or, sql } from "drizzle-orm";
import { customers, purchases, sales, suppliers } from "@mba/db";
import {
  createCustomerSchema,
  createSupplierSchema,
  updateCustomerSchema,
  updateSupplierSchema,
} from "@mba/shared";
import type { AppEnv } from "../env";
import { getDb } from "../db";
import { notFound } from "../lib/errors";
import { created, ok } from "../lib/response";
import { newId } from "../lib/ids";
import { parseJson } from "../lib/validate";
import { requirePermission } from "../middleware/workspace";
import { writeAudit } from "../services/audit";

export const customerRoutes = new Hono<AppEnv>();
export const supplierRoutes = new Hono<AppEnv>();

customerRoutes.get("/", requirePermission("customer:read"), async (context) => {
  const access = context.get("workspace");
  const db = getDb(context.env);
  const search = context.req.query("search")?.trim();
  const filters = [eq(customers.workspaceId, access.workspace.id)];

  if (search) {
    const term = `%${search}%`;
    filters.push(
      or(
        like(customers.name, term),
        like(customers.phone, term),
        like(customers.email, term),
      )!,
    );
  }

  const rows = await db
    .select()
    .from(customers)
    .where(and(...filters))
    .orderBy(desc(customers.createdAt));

  const balances = await db
    .select({
      customerId: sales.customerId,
      outstanding: sql<number>`coalesce(sum(${sales.balanceCents}), 0)`,
      totalSales: sql<number>`coalesce(sum(${sales.totalCents}), 0)`,
      saleCount: sql<number>`count(*)`,
    })
    .from(sales)
    .where(
      and(
        eq(sales.workspaceId, access.workspace.id),
        eq(sales.status, "completed"),
      ),
    )
    .groupBy(sales.customerId);

  const balanceMap = new Map(balances.map((row) => [row.customerId, row]));

  return ok(context, {
    customers: rows.map((row) => {
      const totals = balanceMap.get(row.id);
      return {
        ...row,
        outstandingCents: Number(totals?.outstanding ?? 0),
        totalSalesCents: Number(totals?.totalSales ?? 0),
        saleCount: Number(totals?.saleCount ?? 0),
      };
    }),
  });
});

customerRoutes.post("/", requirePermission("customer:create"), async (context) => {
  const access = context.get("workspace");
  const user = context.get("user");
  const data = await parseJson(context, createCustomerSchema);
  const db = getDb(context.env);
  const id = newId("cus");

  await db.insert(customers).values({
    id,
    workspaceId: access.workspace.id,
    name: data.name,
    phone: data.phone ?? null,
    email: data.email ?? null,
    address: data.address ?? null,
    type: data.type,
    creditLimitCents: data.creditLimitCents,
    notes: data.notes ?? null,
  });

  await writeAudit(context.env, {
    workspaceId: access.workspace.id,
    actorUserId: user.id,
    action: "customer.created",
    entityType: "customer",
    entityId: id,
    metadata: { name: data.name },
  });

  const inserted = await db
    .select()
    .from(customers)
    .where(eq(customers.id, id))
    .limit(1);

  return created(context, { customer: inserted[0] });
});

customerRoutes.patch(
  "/:customerId",
  requirePermission("customer:update"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const customerId = context.req.param("customerId");
    const data = await parseJson(context, updateCustomerSchema);
    const db = getDb(context.env);

    const existing = await db
      .select({ id: customers.id })
      .from(customers)
      .where(
        and(
          eq(customers.id, customerId),
          eq(customers.workspaceId, access.workspace.id),
        ),
      )
      .limit(1);
    if (!existing[0]) throw notFound("Customer not found.");

    await db
      .update(customers)
      .set({
        ...(data.name ? { name: data.name } : {}),
        ...(data.phone !== undefined ? { phone: data.phone ?? null } : {}),
        ...(data.email !== undefined ? { email: data.email ?? null } : {}),
        ...(data.address !== undefined
          ? { address: data.address ?? null }
          : {}),
        ...(data.type ? { type: data.type } : {}),
        ...(data.creditLimitCents !== undefined
          ? { creditLimitCents: data.creditLimitCents }
          : {}),
        ...(data.notes !== undefined ? { notes: data.notes ?? null } : {}),
        ...(data.status ? { status: data.status } : {}),
        updatedAt: Date.now(),
      })
      .where(eq(customers.id, customerId));

    await writeAudit(context.env, {
      workspaceId: access.workspace.id,
      actorUserId: user.id,
      action: "customer.updated",
      entityType: "customer",
      entityId: customerId,
      metadata: data,
    });

    const updated = await db
      .select()
      .from(customers)
      .where(eq(customers.id, customerId))
      .limit(1);

    return ok(context, { customer: updated[0] });
  },
);

customerRoutes.delete(
  "/:customerId",
  requirePermission("customer:delete"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const customerId = context.req.param("customerId");
    const db = getDb(context.env);

    const existing = await db
      .select({ id: customers.id })
      .from(customers)
      .where(
        and(
          eq(customers.id, customerId),
          eq(customers.workspaceId, access.workspace.id),
        ),
      )
      .limit(1);
    if (!existing[0]) throw notFound("Customer not found.");

    await db
      .update(customers)
      .set({ status: "inactive", updatedAt: Date.now() })
      .where(eq(customers.id, customerId));

    await writeAudit(context.env, {
      workspaceId: access.workspace.id,
      actorUserId: user.id,
      action: "customer.deactivated",
      entityType: "customer",
      entityId: customerId,
    });

    return ok(context, { deactivated: true });
  },
);

supplierRoutes.get("/", requirePermission("supplier:read"), async (context) => {
  const access = context.get("workspace");
  const db = getDb(context.env);
  const search = context.req.query("search")?.trim();
  const filters = [eq(suppliers.workspaceId, access.workspace.id)];

  if (search) {
    const term = `%${search}%`;
    filters.push(
      or(
        like(suppliers.name, term),
        like(suppliers.phone, term),
        like(suppliers.contactName, term),
      )!,
    );
  }

  const rows = await db
    .select()
    .from(suppliers)
    .where(and(...filters))
    .orderBy(desc(suppliers.createdAt));

  const totals = await db
    .select({
      supplierId: purchases.supplierId,
      totalPurchases: sql<number>`coalesce(sum(${purchases.totalCents}), 0)`,
      outstanding: sql<number>`coalesce(sum(${purchases.balanceCents}), 0)`,
      purchaseCount: sql<number>`count(*)`,
    })
    .from(purchases)
    .where(
      and(
        eq(purchases.workspaceId, access.workspace.id),
        eq(purchases.status, "received"),
      ),
    )
    .groupBy(purchases.supplierId);
  const totalMap = new Map(totals.map((row) => [row.supplierId, row]));

  return ok(context, {
    suppliers: rows.map((row) => {
      const totalsRow = totalMap.get(row.id);
      return {
        ...row,
        totalPurchasesCents: Number(totalsRow?.totalPurchases ?? 0),
        outstandingCents: Number(totalsRow?.outstanding ?? 0),
        purchaseCount: Number(totalsRow?.purchaseCount ?? 0),
      };
    }),
  });
});

supplierRoutes.post("/", requirePermission("supplier:create"), async (context) => {
  const access = context.get("workspace");
  const user = context.get("user");
  const data = await parseJson(context, createSupplierSchema);
  const db = getDb(context.env);
  const id = newId("sup");

  await db.insert(suppliers).values({
    id,
    workspaceId: access.workspace.id,
    name: data.name,
    contactName: data.contactName ?? null,
    phone: data.phone ?? null,
    email: data.email ?? null,
    address: data.address ?? null,
    notes: data.notes ?? null,
  });

  await writeAudit(context.env, {
    workspaceId: access.workspace.id,
    actorUserId: user.id,
    action: "supplier.created",
    entityType: "supplier",
    entityId: id,
    metadata: { name: data.name },
  });

  const inserted = await db
    .select()
    .from(suppliers)
    .where(eq(suppliers.id, id))
    .limit(1);

  return created(context, { supplier: inserted[0] });
});

supplierRoutes.patch(
  "/:supplierId",
  requirePermission("supplier:update"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const supplierId = context.req.param("supplierId");
    const data = await parseJson(context, updateSupplierSchema);
    const db = getDb(context.env);

    const existing = await db
      .select({ id: suppliers.id })
      .from(suppliers)
      .where(
        and(
          eq(suppliers.id, supplierId),
          eq(suppliers.workspaceId, access.workspace.id),
        ),
      )
      .limit(1);
    if (!existing[0]) throw notFound("Supplier not found.");

    await db
      .update(suppliers)
      .set({
        ...(data.name ? { name: data.name } : {}),
        ...(data.contactName !== undefined
          ? { contactName: data.contactName ?? null }
          : {}),
        ...(data.phone !== undefined ? { phone: data.phone ?? null } : {}),
        ...(data.email !== undefined ? { email: data.email ?? null } : {}),
        ...(data.address !== undefined
          ? { address: data.address ?? null }
          : {}),
        ...(data.notes !== undefined ? { notes: data.notes ?? null } : {}),
        ...(data.status ? { status: data.status } : {}),
        updatedAt: Date.now(),
      })
      .where(eq(suppliers.id, supplierId));

    await writeAudit(context.env, {
      workspaceId: access.workspace.id,
      actorUserId: user.id,
      action: "supplier.updated",
      entityType: "supplier",
      entityId: supplierId,
      metadata: data,
    });

    const updated = await db
      .select()
      .from(suppliers)
      .where(eq(suppliers.id, supplierId))
      .limit(1);

    return ok(context, { supplier: updated[0] });
  },
);

supplierRoutes.delete(
  "/:supplierId",
  requirePermission("supplier:delete"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const supplierId = context.req.param("supplierId");
    const db = getDb(context.env);

    const existing = await db
      .select({ id: suppliers.id })
      .from(suppliers)
      .where(
        and(
          eq(suppliers.id, supplierId),
          eq(suppliers.workspaceId, access.workspace.id),
        ),
      )
      .limit(1);
    if (!existing[0]) throw notFound("Supplier not found.");

    await db
      .update(suppliers)
      .set({ status: "inactive", updatedAt: Date.now() })
      .where(eq(suppliers.id, supplierId));

    await writeAudit(context.env, {
      workspaceId: access.workspace.id,
      actorUserId: user.id,
      action: "supplier.deactivated",
      entityType: "supplier",
      entityId: supplierId,
    });

    return ok(context, { deactivated: true });
  },
);
