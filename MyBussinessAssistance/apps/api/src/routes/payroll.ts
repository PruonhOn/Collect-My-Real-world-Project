import { Hono } from "hono";
import { and, desc, eq, inArray, notInArray, sql } from "drizzle-orm";
import {
  branches,
  employees,
  payrollItems,
  payrollRuns,
} from "@mba/db";
import { createPayrollRunSchema, payrollPaySchema } from "@mba/shared";
import type { AppEnv } from "../env";
import { getDb } from "../db";
import { badRequest, notFound } from "../lib/errors";
import { created, ok } from "../lib/response";
import { newId } from "../lib/ids";
import { parseJson } from "../lib/validate";
import { requirePermission } from "../middleware/workspace";
import { writeAudit } from "../services/audit";

export const payrollRoutes = new Hono<AppEnv>();

payrollRoutes.get("/", requirePermission("payroll:read"), async (context) => {
  const access = context.get("workspace");
  const db = getDb(context.env);
  const workspaceId = access.workspace.id;
  const status = context.req.query("status");
  const branchId = context.req.query("branchId");

  const filters = [eq(payrollRuns.workspaceId, workspaceId)];
  if (status) filters.push(eq(payrollRuns.status, status));
  if (branchId) filters.push(eq(payrollRuns.branchId, branchId));

  const rows = await db
    .select({
      id: payrollRuns.id,
      number: payrollRuns.number,
      branchId: payrollRuns.branchId,
      periodStart: payrollRuns.periodStart,
      periodEnd: payrollRuns.periodEnd,
      status: payrollRuns.status,
      totalCents: payrollRuns.totalCents,
      paidCents: payrollRuns.paidCents,
      paidAt: payrollRuns.paidAt,
      note: payrollRuns.note,
      createdAt: payrollRuns.createdAt,
      itemCount: sql<number>`(select count(*) from payroll_items where run_id = ${payrollRuns.id})`,
    })
    .from(payrollRuns)
    .where(and(...filters))
    .orderBy(desc(payrollRuns.createdAt))
    .limit(300);

  return ok(context, {
    runs: rows.map((row) => ({
      ...row,
      itemCount: Number(row.itemCount),
    })),
  });
});

payrollRoutes.post(
  "/",
  requirePermission("payroll:create"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const data = await parseJson(context, createPayrollRunSchema);
    const db = getDb(context.env);
    const workspaceId = access.workspace.id;
    const now = Date.now();

    if (data.periodEnd <= data.periodStart) {
      throw badRequest("Period end must be after period start.");
    }

    let branchId = data.branchId ?? null;
    if (branchId) {
      const branch = await db
        .select({ id: branches.id })
        .from(branches)
        .where(
          and(
            eq(branches.id, branchId),
            eq(branches.workspaceId, workspaceId),
          ),
        )
        .limit(1);
      if (!branch[0]) throw badRequest("Select a valid branch.");
    }

    const numberRow = (
      await db
        .select({ total: sql<number>`count(*)` })
        .from(payrollRuns)
        .where(eq(payrollRuns.workspaceId, workspaceId))
    )[0];
    const number = `PYL-${String(Number(numberRow?.total ?? 0) + 1).padStart(
      4,
      "0",
    )}`;

    const runId = newId("pyr");
    const items: Array<{
      salaryCents: number;
      allowancesCents: number;
      deductionsCents: number;
      netCents: number;
    }> = [];
    let totalCents = 0;

    const employeeIds = Array.from(
      new Set(data.items.map((item) => item.employeeId)),
    );
    const activeEmployees =
      employeeIds.length > 0
        ? await db
            .select({ id: employees.id })
            .from(employees)
            .where(
              and(
                inArray(employees.id, employeeIds),
                eq(employees.workspaceId, workspaceId),
                eq(employees.status, "active"),
              ),
            )
        : [];
    const activeSet = new Set(activeEmployees.map((row) => row.id));

    for (const item of data.items) {
      if (!activeSet.has(item.employeeId)) {
        throw badRequest(`Employee ${item.employeeId} is not active.`);
      }
      const salary = item.salaryCents ?? 0;
      const allowances = item.allowancesCents ?? 0;
      const deductions = item.deductionsCents ?? 0;
      const netCents = salary + allowances - deductions;
      items.push({
        salaryCents: salary,
        allowancesCents: allowances,
        deductionsCents: deductions,
        netCents: Math.max(0, netCents),
      });
      totalCents += Math.max(0, netCents);
    }

    const queries = [
      db.insert(payrollRuns).values({
        id: runId,
        workspaceId,
        branchId,
        number,
        periodStart: data.periodStart,
        periodEnd: data.periodEnd,
        status: "draft",
        totalCents,
        paidCents: 0,
        note: data.note ?? null,
        createdByUserId: user.id,
      }),
    ];

    for (const [index, item] of data.items.entries()) {
      queries.push(
        db.insert(payrollItems).values({
          id: newId("pyi"),
          workspaceId,
          runId,
          employeeId: item.employeeId,
          salaryCents: items[index].salaryCents,
          allowancesCents: items[index].allowancesCents,
          deductionsCents: items[index].deductionsCents,
          netCents: items[index].netCents,
        }) as never,
      );
    }

    await db.batch(queries as never);

    await writeAudit(context.env, {
      workspaceId,
      actorUserId: user.id,
      action: "payroll.run_created",
      entityType: "payroll_run",
      entityId: runId,
      metadata: { number, totalCents, itemCount: data.items.length },
    });

    return created(context, { runId, number, totalCents, status: "draft" });
  },
);

payrollRoutes.get(
  "/:runId",
  requirePermission("payroll:read"),
  async (context) => {
    const access = context.get("workspace");
    const db = getDb(context.env);
    const runId = context.req.param("runId");

    const rows = await db
      .select()
      .from(payrollRuns)
      .where(
        and(eq(payrollRuns.id, runId), eq(payrollRuns.workspaceId, access.workspace.id)),
      )
      .limit(1);
    const run = rows[0];
    if (!run) throw notFound("Payroll run not found.");

    const itemRows = await db
      .select({
        id: payrollItems.id,
        employeeId: payrollItems.employeeId,
        employeeName: employees.name,
        salaryCents: payrollItems.salaryCents,
        allowancesCents: payrollItems.allowancesCents,
        deductionsCents: payrollItems.deductionsCents,
        netCents: payrollItems.netCents,
        paidAt: payrollItems.paidAt,
        createdAt: payrollItems.createdAt,
      })
      .from(payrollItems)
      .leftJoin(employees, eq(payrollItems.employeeId, employees.id))
      .where(eq(payrollItems.runId, runId))
      .orderBy(employees.name);

    return ok(context, {
      run: {
        ...run,
        itemCount: itemRows.length,
      },
      items: itemRows,
    });
  },
);

payrollRoutes.post(
  "/:runId/pay",
  requirePermission("payroll:update"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const runId = context.req.param("runId");
    const data = await parseJson(context, payrollPaySchema);
    const db = getDb(context.env);
    const workspaceId = access.workspace.id;
    const now = Date.now();

    const rows = await db
      .select()
      .from(payrollRuns)
      .where(
        and(eq(payrollRuns.id, runId), eq(payrollRuns.workspaceId, workspaceId)),
      )
      .limit(1);
    const run = rows[0];
    if (!run) throw notFound("Payroll run not found.");
    if (run.status !== "draft") {
      throw badRequest("Only draft payroll runs can be paid.");
    }

    if (data.paidCents > run.totalCents) {
      throw badRequest("Paid amount cannot exceed the payroll total.");
    }
    const paidCents = data.paidCents;
    const paidStatus = paidCents >= run.totalCents ? "paid" : "partial";

    const paid = await db
      .update(payrollRuns)
      .set({
        status: paidStatus,
        paidCents,
        paidAt: paidCents > 0 ? now : null,
        updatedAt: now,
      })
      .where(and(eq(payrollRuns.id, runId), eq(payrollRuns.status, "draft")));
    if (Number(paid?.meta?.changes ?? 0) !== 1) {
      throw badRequest("Only draft payroll runs can be paid.");
    }

    try {
      await db
        .update(payrollItems)
        .set({ paidAt: paidCents > 0 ? now : null })
        .where(eq(payrollItems.runId, runId));
    } catch (cause) {
      console.error("Payroll run paid but item paidAt was not stamped", runId, cause);
    }

    await writeAudit(context.env, {
      workspaceId,
      actorUserId: user.id,
      action: "payroll.run_paid",
      entityType: "payroll_run",
      entityId: runId,
      metadata: { paidCents, totalCents: run.totalCents },
    });

    return ok(context, {
      runId,
      status: paidStatus,
      paidCents,
      balanceCents: run.totalCents - paidCents,
    });
  },
);

payrollRoutes.delete(
  "/:runId",
  requirePermission("payroll:delete"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const runId = context.req.param("runId");
    const db = getDb(context.env);
    const workspaceId = access.workspace.id;

    const rows = await db
      .select()
      .from(payrollRuns)
      .where(
        and(eq(payrollRuns.id, runId), eq(payrollRuns.workspaceId, workspaceId)),
      )
      .limit(1);
    const run = rows[0];
    if (!run) throw notFound("Payroll run not found.");
    if (run.status === "paid") {
      throw badRequest("A paid payroll run cannot be cancelled.");
    }

    const cancelled = await db
      .update(payrollRuns)
      .set({ status: "cancelled", updatedAt: Date.now() })
      .where(
        and(
          eq(payrollRuns.id, runId),
          notInArray(payrollRuns.status, ["paid", "cancelled"]),
        ),
      );
    if (Number(cancelled?.meta?.changes ?? 0) !== 1) {
      throw badRequest("This payroll run can no longer be cancelled.");
    }

    await writeAudit(context.env, {
      workspaceId,
      actorUserId: user.id,
      action: "payroll.run_cancelled",
      entityType: "payroll_run",
      entityId: runId,
      metadata: { number: run.number },
    });

    return ok(context, { runId, status: "cancelled" });
  },
);
