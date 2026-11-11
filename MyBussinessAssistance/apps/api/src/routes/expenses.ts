import { Hono } from "hono";
import { and, desc, eq, gte, like, lte, or } from "drizzle-orm";
import { branches, expenses, users } from "@mba/db";
import {
  createExpenseSchema,
  updateExpenseSchema,
} from "@mba/shared";
import type { AppEnv } from "../env";
import { getDb } from "../db";
import { badRequest, notFound } from "../lib/errors";
import { created, ok } from "../lib/response";
import { newId } from "../lib/ids";
import { parseJson } from "../lib/validate";
import { requirePermission } from "../middleware/workspace";
import { writeAudit } from "../services/audit";

export const expenseRoutes = new Hono<AppEnv>();

expenseRoutes.get("/", requirePermission("expense:read"), async (context) => {
  const access = context.get("workspace");
  const db = getDb(context.env);
  const workspaceId = access.workspace.id;
  const search = context.req.query("search")?.trim();
  const category = context.req.query("category");
  const branchId = context.req.query("branchId");
  const from = Number(context.req.query("from"));
  const to = Number(context.req.query("to"));

  const filters = [eq(expenses.workspaceId, workspaceId)];
  if (category) filters.push(eq(expenses.category, category));
  if (branchId) filters.push(eq(expenses.branchId, branchId));
  if (Number.isFinite(from) && from > 0) {
    filters.push(gte(expenses.expenseDate, from));
  }
  if (Number.isFinite(to) && to > 0) {
    filters.push(lte(expenses.expenseDate, to));
  }
  if (search) {
    const term = `%${search}%`;
    filters.push(
      or(
        like(expenses.description, term),
        like(expenses.vendor, term),
        like(expenses.reference, term),
      )!,
    );
  }

  const rows = await db
    .select({
      id: expenses.id,
      category: expenses.category,
      description: expenses.description,
      amountCents: expenses.amountCents,
      currency: expenses.currency,
      paymentMethod: expenses.paymentMethod,
      vendor: expenses.vendor,
      reference: expenses.reference,
      expenseDate: expenses.expenseDate,
      status: expenses.status,
      branchId: expenses.branchId,
      branchName: branches.name,
      createdByName: users.name,
      createdAt: expenses.createdAt,
    })
    .from(expenses)
    .leftJoin(branches, eq(expenses.branchId, branches.id))
    .leftJoin(users, eq(expenses.createdByUserId, users.id))
    .where(and(...filters))
    .orderBy(desc(expenses.expenseDate))
    .limit(300);

  return ok(context, {
    expenses: rows,
    summary: {
      totalCents: rows
        .filter((row) => row.status !== "void")
        .reduce((sum, row) => sum + row.amountCents, 0),
      count: rows.filter((row) => row.status !== "void").length,
    },
  });
});

expenseRoutes.post("/", requirePermission("expense:create"), async (context) => {
  const access = context.get("workspace");
  const user = context.get("user");
  const data = await parseJson(context, createExpenseSchema);
  const db = getDb(context.env);

  if (data.branchId) {
    const branch = await db
      .select({ id: branches.id })
      .from(branches)
      .where(
        and(
          eq(branches.id, data.branchId),
          eq(branches.workspaceId, access.workspace.id),
        ),
      )
      .limit(1);
    if (!branch[0]) throw badRequest("Select a valid branch.");
  }

  const id = newId("exp");
  await db.insert(expenses).values({
    id,
    workspaceId: access.workspace.id,
    branchId: data.branchId ?? null,
    category: data.category,
    description: data.description,
    amountCents: data.amountCents,
    currency: access.workspace.defaultCurrency,
    paymentMethod: data.paymentMethod,
    vendor: data.vendor ?? null,
    reference: data.reference ?? null,
    note: data.note ?? null,
    expenseDate: data.expenseDate ?? Date.now(),
    createdByUserId: user.id,
  });

  await writeAudit(context.env, {
    workspaceId: access.workspace.id,
    actorUserId: user.id,
    action: "expense.created",
    entityType: "expense",
    entityId: id,
    metadata: {
      category: data.category,
      amountCents: data.amountCents,
    },
  });

  const inserted = await db
    .select()
    .from(expenses)
    .where(eq(expenses.id, id))
    .limit(1);

  return created(context, { expense: inserted[0] });
});

expenseRoutes.patch(
  "/:expenseId",
  requirePermission("expense:update"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const expenseId = context.req.param("expenseId");
    const data = await parseJson(context, updateExpenseSchema);
    const db = getDb(context.env);

    const existing = await db
      .select()
      .from(expenses)
      .where(
        and(
          eq(expenses.id, expenseId),
          eq(expenses.workspaceId, access.workspace.id),
        ),
      )
      .limit(1);
    if (!existing[0]) throw notFound("Expense not found.");

    if (data.branchId) {
      const branch = await db
        .select({ id: branches.id })
        .from(branches)
        .where(
          and(
            eq(branches.id, data.branchId),
            eq(branches.workspaceId, access.workspace.id),
          ),
        )
        .limit(1);
      if (!branch[0]) throw badRequest("Select a valid branch.");
    }

    await db
      .update(expenses)
      .set({
        ...(data.branchId !== undefined
          ? { branchId: data.branchId ?? null }
          : {}),
        ...(data.category ? { category: data.category } : {}),
        ...(data.description ? { description: data.description } : {}),
        ...(data.amountCents !== undefined
          ? { amountCents: data.amountCents }
          : {}),
        ...(data.paymentMethod
          ? { paymentMethod: data.paymentMethod }
          : {}),
        ...(data.vendor !== undefined ? { vendor: data.vendor ?? null } : {}),
        ...(data.reference !== undefined
          ? { reference: data.reference ?? null }
          : {}),
        ...(data.expenseDate ? { expenseDate: data.expenseDate } : {}),
        ...(data.note ? { note: data.note } : {}),
        ...(data.status ? { status: data.status } : {}),
        updatedAt: Date.now(),
      })
      .where(eq(expenses.id, expenseId));

    await writeAudit(context.env, {
      workspaceId: access.workspace.id,
      actorUserId: user.id,
      action: "expense.updated",
      entityType: "expense",
      entityId: expenseId,
      metadata: data,
    });

    const updated = await db
      .select()
      .from(expenses)
      .where(eq(expenses.id, expenseId))
      .limit(1);

    return ok(context, { expense: updated[0] });
  },
);

expenseRoutes.delete(
  "/:expenseId",
  requirePermission("expense:delete"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const expenseId = context.req.param("expenseId");
    const db = getDb(context.env);

    const existing = await db
      .select({ id: expenses.id })
      .from(expenses)
      .where(
        and(
          eq(expenses.id, expenseId),
          eq(expenses.workspaceId, access.workspace.id),
        ),
      )
      .limit(1);
    if (!existing[0]) throw notFound("Expense not found.");

    await db
      .update(expenses)
      .set({ status: "void", updatedAt: Date.now() })
      .where(eq(expenses.id, expenseId));

    await writeAudit(context.env, {
      workspaceId: access.workspace.id,
      actorUserId: user.id,
      action: "expense.voided",
      entityType: "expense",
      entityId: expenseId,
    });

    return ok(context, { voided: true });
  },
);
