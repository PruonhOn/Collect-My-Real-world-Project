import { Hono } from "hono";
import { and, asc, count, desc, eq, inArray, like, or, sql, type SQL } from "drizzle-orm";
import {
  planEntitlements,
  plans,
  products,
  subscriptions,
  users,
  workspaceMembers,
  workspaces,
} from "@mba/db";
import {
  adminUpdateEntitlementSchema,
  adminUpdatePlanSchema,
  adminUpdateWorkspaceSchema,
} from "@mba/shared";
import type { AppEnv } from "../env";
import { getDb } from "../db";
import { badRequest, notFound } from "../lib/errors";
import { ok } from "../lib/response";
import { newId } from "../lib/ids";
import { parseJson } from "../lib/validate";
import { requireAuth } from "../middleware/auth";
import { requirePlatformAdmin, requirePlatformSuperAdmin } from "../middleware/platform";
import { writeAudit } from "../services/audit";

export const adminRoutes = new Hono<AppEnv>();

adminRoutes.use("*", requireAuth, requirePlatformAdmin);

adminRoutes.get("/overview", async (context) => {
  const db = getDb(context.env);

  const [workspaceRow, userRow, subscriptionRows, productRow] =
    await Promise.all([
      db.select({ total: count() }).from(workspaces),
      db.select({ total: count() }).from(users),
      db
        .select({ planId: subscriptions.planId, status: subscriptions.status })
        .from(subscriptions),
      db.select({ total: count() }).from(products),
    ]);

  const planRows = await db.select().from(plans).orderBy(asc(plans.sortOrder));

  const byPlan = new Map<string, number>();
  for (const row of subscriptionRows) {
    byPlan.set(row.planId, (byPlan.get(row.planId) ?? 0) + 1);
  }

  const monthlyRevenueCents = planRows.reduce((sum, plan) => {
    const subscribers = byPlan.get(plan.id) ?? 0;
    if (subscribers === 0) return sum;
    const yearlyShare = Math.round(plan.yearlyPriceCents / 12);
    return (
      sum +
      subscribers *
        Math.max(plan.monthlyPriceCents, plan.yearlyPriceCents > 0 ? yearlyShare : 0)
    );
  }, 0);

  return ok(context, {
    workspaces: Number(workspaceRow[0]?.total ?? 0),
    users: Number(userRow[0]?.total ?? 0),
    products: Number(productRow[0]?.total ?? 0),
    payingWorkspaces: planRows.reduce(
      (sum, plan) =>
        plan.monthlyPriceCents > 0 ? sum + (byPlan.get(plan.id) ?? 0) : sum,
      0,
    ),
    estimatedMonthlyRevenueCents: monthlyRevenueCents,
    subscriptionsByPlan: planRows.map((plan) => ({
      planId: plan.id,
      planName: plan.name,
      planSlug: plan.slug,
      count: byPlan.get(plan.id) ?? 0,
    })),
  });
});

adminRoutes.get("/workspaces", async (context) => {
  const db = getDb(context.env);
  const search = context.req.query("search")?.trim();
  const statusFilter = context.req.query("status");

  const owner = users;
  const filters: SQL[] = [];
  if (search) {
    const term = `%${search}%`;
    filters.push(
      or(like(workspaces.name, term), like(owner.email, term))!,
    );
  }
  if (statusFilter === "active" || statusFilter === "suspended") {
    filters.push(eq(workspaces.status, statusFilter));
  }

  const rows = await db
    .select({
      id: workspaces.id,
      name: workspaces.name,
      slug: workspaces.slug,
      status: workspaces.status,
      defaultCurrency: workspaces.defaultCurrency,
      createdAt: workspaces.createdAt,
      ownerEmail: owner.email,
      ownerName: owner.name,
      planId: plans.id,
      planName: plans.name,
      planSlug: plans.slug,
      subscriptionStatus: subscriptions.status,
      trialEndsAt: subscriptions.trialEndsAt,
      currentPeriodEnd: subscriptions.currentPeriodEnd,
      cancelAtPeriodEnd: subscriptions.cancelAtPeriodEnd,
      memberCount: sql<number>`(
        select count(*) from ${workspaceMembers}
        where ${workspaceMembers.workspaceId} = ${workspaces.id}
          and ${workspaceMembers.status} = 'active'
      )`,
      productCount: sql<number>`(
        select count(*) from ${products}
        where ${products.workspaceId} = ${workspaces.id}
          and ${products.status} = 'active'
      )`,
    })
    .from(workspaces)
    .leftJoin(owner, eq(owner.id, workspaces.ownerUserId))
    .leftJoin(subscriptions, eq(subscriptions.workspaceId, workspaces.id))
    .leftJoin(plans, eq(plans.id, subscriptions.planId))
    .where(filters.length > 0 ? and(...filters) : undefined)
    .orderBy(desc(workspaces.createdAt))
    .limit(200);

  return ok(context, {
    workspaces: rows.map((row) => ({
      ...row,
      memberCount: Number(row.memberCount ?? 0),
      productCount: Number(row.productCount ?? 0),
    })),
  });
});

adminRoutes.patch(
  "/workspaces/:workspaceId",
  requirePlatformSuperAdmin,
  async (context) => {
  const admin = context.get("user");
  const workspaceId = context.req.param("workspaceId");
  const data = await parseJson(context, adminUpdateWorkspaceSchema);
  const db = getDb(context.env);

  const existing = await db
    .select({ id: workspaces.id, status: workspaces.status })
    .from(workspaces)
    .where(eq(workspaces.id, workspaceId))
    .limit(1);

  if (!existing[0]) throw notFound("Workspace not found.");

  await db
    .update(workspaces)
    .set({ status: data.status, updatedAt: Date.now() })
    .where(eq(workspaces.id, workspaceId));

  await writeAudit(context.env, {
    actorUserId: admin.id,
    action: `admin.workspace_${data.status === "active" ? "activated" : "suspended"}`,
    entityType: "workspace",
    entityId: workspaceId,
  });

  return ok(context, { workspaceId, status: data.status });
  },
);

adminRoutes.get("/plans", async (context) => {
  const db = getDb(context.env);

  const planRows = await db
    .select()
    .from(plans)
    .orderBy(asc(plans.sortOrder));

  const entitlementRows =
    planRows.length > 0
      ? await db
          .select()
          .from(planEntitlements)
          .where(
            inArray(
              planEntitlements.planId,
              planRows.map((plan) => plan.id),
            ),
          )
      : [];

  const byPlan = new Map<string, { key: string; value: string }[]>();
  for (const row of entitlementRows) {
    const list = byPlan.get(row.planId) ?? [];
    list.push({ key: row.key, value: row.value });
    byPlan.set(row.planId, list);
  }

  const subscriptionCounts = await db
    .select({ planId: subscriptions.planId, total: count() })
    .from(subscriptions)
    .groupBy(subscriptions.planId);
  const countMap = new Map(
    subscriptionCounts.map((row) => [row.planId, Number(row.total)]),
  );

  return ok(context, {
    plans: planRows.map((plan) => ({
      ...plan,
      subscriberCount: countMap.get(plan.id) ?? 0,
      entitlements: byPlan.get(plan.id) ?? [],
    })),
  });
});

adminRoutes.patch(
  "/plans/:planId",
  requirePlatformSuperAdmin,
  async (context) => {
  const admin = context.get("user");
  const planId = context.req.param("planId");
  const data = await parseJson(context, adminUpdatePlanSchema);
  const db = getDb(context.env);

  const existing = await db
    .select()
    .from(plans)
    .where(eq(plans.id, planId))
    .limit(1);

  if (!existing[0]) throw notFound("Plan not found.");

  const updated = await db
    .update(plans)
    .set({
      ...(data.name ? { name: data.name } : {}),
      ...(data.description !== undefined
        ? { description: data.description ?? null }
        : {}),
      ...(data.monthlyPriceCents !== undefined
        ? { monthlyPriceCents: data.monthlyPriceCents }
        : {}),
      ...(data.yearlyPriceCents !== undefined
        ? { yearlyPriceCents: data.yearlyPriceCents }
        : {}),
      ...(data.isActive !== undefined ? { isActive: data.isActive } : {}),
      updatedAt: Date.now(),
    })
    .where(eq(plans.id, planId))
    .returning();

  await writeAudit(context.env, {
    actorUserId: admin.id,
    action: "admin.plan_updated",
    entityType: "plan",
    entityId: planId,
    metadata: data,
  });

  return ok(context, { plan: updated[0] });
  },
);

adminRoutes.put(
  "/plans/:planId/entitlements",
  requirePlatformSuperAdmin,
  async (context) => {
  const admin = context.get("user");
  const planId = context.req.param("planId");
  const data = await parseJson(context, adminUpdateEntitlementSchema);
  const db = getDb(context.env);

  const existing = await db
    .select({ id: plans.id })
    .from(plans)
    .where(eq(plans.id, planId))
    .limit(1);

  if (!existing[0]) throw notFound("Plan not found.");

  const entitlementId = `ent_${planId}_${data.key}`.replace(
    /[^a-z0-9_]/gi,
    "_",
  );

  if (data.value === null) {
    await db
      .delete(planEntitlements)
      .where(
        and(
          eq(planEntitlements.planId, planId),
          eq(planEntitlements.key, data.key),
        ),
      );
  } else {
    await db
      .insert(planEntitlements)
      .values({
        id: `${entitlementId}_${newId("e").slice(0, 8)}`,
        planId,
        key: data.key,
        value: data.value,
      })
      .onConflictDoUpdate({
        target: [planEntitlements.planId, planEntitlements.key],
        set: { value: data.value },
      });
  }

  await writeAudit(context.env, {
    actorUserId: admin.id,
    action: "admin.entitlement_updated",
    entityType: "plan",
    entityId: planId,
    metadata: { key: data.key, value: data.value },
  });

  return ok(context, {
    planId,
    key: data.key,
    value: data.value,
  });
  },
);
