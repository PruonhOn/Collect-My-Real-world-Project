import { Hono } from "hono";
import { and, desc, eq, like, or } from "drizzle-orm";
import { branches, employees } from "@mba/db";
import {
  createEmployeeSchema,
  updateEmployeeSchema,
} from "@mba/shared";
import type { AppEnv } from "../env";
import { getDb } from "../db";
import { badRequest, notFound } from "../lib/errors";
import { created, ok } from "../lib/response";
import { newId } from "../lib/ids";
import { parseJson } from "../lib/validate";
import { requirePermission } from "../middleware/workspace";
import { writeAudit } from "../services/audit";
import { assertWithinLimit, getPlanUsage } from "../services/entitlements";

export const employeeRoutes = new Hono<AppEnv>();

employeeRoutes.get("/", requirePermission("employee:read"), async (context) => {
  const access = context.get("workspace");
  const db = getDb(context.env);
  const workspaceId = access.workspace.id;
  const search = context.req.query("search")?.trim();
  const status = context.req.query("status");
  const branchId = context.req.query("branchId");

  const filters = [eq(employees.workspaceId, workspaceId)];
  if (status === "active" || status === "inactive") {
    filters.push(eq(employees.status, status));
  }
  if (branchId) filters.push(eq(employees.branchId, branchId));
  if (search) {
    const term = `%${search}%`;
    filters.push(
      or(
        like(employees.name, term),
        like(employees.phone, term),
        like(employees.email, term),
        like(employees.position, term),
        like(employees.department, term),
      )!,
    );
  }

  const rows = await db
    .select({
      id: employees.id,
      workspaceId: employees.workspaceId,
      name: employees.name,
      phone: employees.phone,
      email: employees.email,
      position: employees.position,
      department: employees.department,
      salaryCents: employees.salaryCents,
      currency: employees.currency,
      hireDate: employees.hireDate,
      status: employees.status,
      notes: employees.notes,
      branchId: employees.branchId,
      branchName: branches.name,
      createdAt: employees.createdAt,
      updatedAt: employees.updatedAt,
    })
    .from(employees)
    .leftJoin(branches, eq(employees.branchId, branches.id))
    .where(and(...filters))
    .orderBy(desc(employees.createdAt));

  const active = rows.filter((row) => row.status === "active");

  return ok(context, {
    employees: rows,
    summary: {
      count: rows.length,
      activeCount: active.length,
      monthlySalaryCents: active.reduce(
        (sum, row) => sum + row.salaryCents,
        0,
      ),
    },
  });
});

employeeRoutes.post(
  "/",
  requirePermission("employee:create"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const data = await parseJson(context, createEmployeeSchema);
    const db = getDb(context.env);
    const workspaceId = access.workspace.id;

    if (data.branchId) {
      const branch = await db
        .select({ id: branches.id })
        .from(branches)
        .where(
          and(
            eq(branches.id, data.branchId),
            eq(branches.workspaceId, workspaceId),
          ),
        )
        .limit(1);
      if (!branch[0]) throw badRequest("Select a valid branch.");
    }

    const usage = await getPlanUsage(context.env, workspaceId);
    await assertWithinLimit(
      context.env,
      workspaceId,
      "maxEmployees",
      usage.employees,
    );

    const id = newId("emp");
    await db.insert(employees).values({
      id,
      workspaceId,
      branchId: data.branchId ?? null,
      name: data.name,
      phone: data.phone ?? null,
      email: data.email ?? null,
      position: data.position ?? null,
      department: data.department ?? null,
      salaryCents: data.salaryCents,
      currency: data.currency,
      hireDate: data.hireDate ?? null,
      notes: data.notes ?? null,
      status: data.status ?? "active",
    });

    await writeAudit(context.env, {
      workspaceId,
      actorUserId: user.id,
      action: "employee.created",
      entityType: "employee",
      entityId: id,
      metadata: { name: data.name },
    });

    const inserted = await db
      .select()
      .from(employees)
      .where(eq(employees.id, id))
      .limit(1);

    return created(context, { employee: inserted[0] });
  },
);

employeeRoutes.patch(
  "/:employeeId",
  requirePermission("employee:update"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const employeeId = context.req.param("employeeId");
    const data = await parseJson(context, updateEmployeeSchema);
    const db = getDb(context.env);
    const workspaceId = access.workspace.id;

    const existing = await db
      .select({ id: employees.id })
      .from(employees)
      .where(
        and(
          eq(employees.id, employeeId),
          eq(employees.workspaceId, workspaceId),
        ),
      )
      .limit(1);
    if (!existing[0]) throw notFound("Employee not found.");

    if (data.branchId) {
      const branch = await db
        .select({ id: branches.id })
        .from(branches)
        .where(
          and(
            eq(branches.id, data.branchId),
            eq(branches.workspaceId, workspaceId),
          ),
        )
        .limit(1);
      if (!branch[0]) throw badRequest("Select a valid branch.");
    }

    await db
      .update(employees)
      .set({
        ...(data.name ? { name: data.name } : {}),
        ...(data.branchId !== undefined
          ? { branchId: data.branchId ?? null }
          : {}),
        ...(data.phone !== undefined ? { phone: data.phone ?? null } : {}),
        ...(data.email !== undefined ? { email: data.email ?? null } : {}),
        ...(data.position !== undefined
          ? { position: data.position ?? null }
          : {}),
        ...(data.department !== undefined
          ? { department: data.department ?? null }
          : {}),
        ...(data.salaryCents !== undefined
          ? { salaryCents: data.salaryCents }
          : {}),
        ...(data.currency ? { currency: data.currency } : {}),
        ...(data.hireDate !== undefined
          ? { hireDate: data.hireDate ?? null }
          : {}),
        ...(data.notes !== undefined ? { notes: data.notes ?? null } : {}),
        ...(data.status ? { status: data.status } : {}),
        updatedAt: Date.now(),
      })
      .where(eq(employees.id, employeeId));

    await writeAudit(context.env, {
      workspaceId,
      actorUserId: user.id,
      action: "employee.updated",
      entityType: "employee",
      entityId: employeeId,
      metadata: data,
    });

    const updated = await db
      .select()
      .from(employees)
      .where(eq(employees.id, employeeId))
      .limit(1);

    return ok(context, { employee: updated[0] });
  },
);

employeeRoutes.delete(
  "/:employeeId",
  requirePermission("employee:delete"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const employeeId = context.req.param("employeeId");
    const db = getDb(context.env);

    const existing = await db
      .select({ id: employees.id })
      .from(employees)
      .where(
        and(
          eq(employees.id, employeeId),
          eq(employees.workspaceId, access.workspace.id),
        ),
      )
      .limit(1);
    if (!existing[0]) throw notFound("Employee not found.");

    await db
      .update(employees)
      .set({ status: "inactive", updatedAt: Date.now() })
      .where(eq(employees.id, employeeId));

    await writeAudit(context.env, {
      workspaceId: access.workspace.id,
      actorUserId: user.id,
      action: "employee.deactivated",
      entityType: "employee",
      entityId: employeeId,
    });

    return ok(context, { deactivated: true });
  },
);
