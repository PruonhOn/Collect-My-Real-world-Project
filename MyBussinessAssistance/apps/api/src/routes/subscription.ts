import { Hono } from "hono";
import { eq, inArray } from "drizzle-orm";
import {
  planEntitlements,
  plans,
  subscriptions,
} from "@mba/db";
import { changeSubscriptionSchema } from "@mba/shared";
import type { AppEnv } from "../env";
import { getDb } from "../db";
import { badRequest, notFound } from "../lib/errors";
import { ok } from "../lib/response";
import { newId } from "../lib/ids";
import { parseJson } from "../lib/validate";
import { requirePermission } from "../middleware/workspace";
import { writeAudit } from "../services/audit";
import {
  getPlanLimits,
  getPlanUsage,
} from "../services/entitlements";

const DAY_MS = 24 * 60 * 60 * 1_000;

export const subscriptionRoutes = new Hono<AppEnv>();

subscriptionRoutes.get(
  "/",
  requirePermission("subscription:read"),
  async (context) => {
    const access = context.get("workspace");
    const db = getDb(context.env);
    const workspaceId = access.workspace.id;

    const subscriptionRows = await db
      .select({ subscription: subscriptions, plan: plans })
      .from(subscriptions)
      .innerJoin(plans, eq(plans.id, subscriptions.planId))
      .where(eq(subscriptions.workspaceId, workspaceId))
      .limit(1);

    const planRows = await db
      .select()
      .from(plans)
      .where(eq(plans.isActive, true))
      .orderBy(plans.sortOrder);

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

    const entitlementsByPlan = new Map<string, Record<string, string>>();
    for (const entitlement of entitlementRows) {
      const current = entitlementsByPlan.get(entitlement.planId) ?? {};
      current[entitlement.key] = entitlement.value;
      entitlementsByPlan.set(entitlement.planId, current);
    }

    const [limits, usage] = await Promise.all([
      getPlanLimits(context.env, workspaceId),
      getPlanUsage(context.env, workspaceId),
    ]);

    return ok(context, {
      subscription: subscriptionRows[0] ?? null,
      plans: planRows.map((plan) => ({
        ...plan,
        entitlements: entitlementsByPlan.get(plan.id) ?? {},
      })),
      limits,
      usage,
    });
  },
);

subscriptionRoutes.post(
  "/change",
  requirePermission("subscription:manage"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const data = await parseJson(context, changeSubscriptionSchema);
    const db = getDb(context.env);
    const workspaceId = access.workspace.id;

    const planRows = await db
      .select()
      .from(plans)
      .where(eq(plans.id, data.planId))
      .limit(1);
    const plan = planRows[0];
    if (!plan || !plan.isActive) {
      throw badRequest("Select a valid plan.");
    }

    const existingRows = await db
      .select()
      .from(subscriptions)
      .where(eq(subscriptions.workspaceId, workspaceId))
      .limit(1);
    const existing = existingRows[0];

    const now = Date.now();
    const periodLengthDays = data.billingCycle === "yearly" ? 365 : 30;
    const currentPeriodEnd = now + periodLengthDays * DAY_MS;

    if (!existing) {
      const id = newId("sub");
      await db.insert(subscriptions).values({
        id,
        workspaceId,
        planId: plan.id,
        status: "active",
        billingCycle: data.billingCycle,
        currentPeriodStart: now,
        currentPeriodEnd,
      });

      await writeAudit(context.env, {
        workspaceId,
        actorUserId: user.id,
        action: "subscription.created",
        entityType: "subscription",
        entityId: id,
        metadata: { planId: plan.id, billingCycle: data.billingCycle },
      });

      return ok(context, {
        subscription: {
          id,
          planId: plan.id,
          status: "active",
          billingCycle: data.billingCycle,
          currentPeriodEnd,
        },
      });
    }

    const keepTrial =
      existing.status === "trialing" &&
      existing.trialEndsAt !== null &&
      existing.trialEndsAt > now;
    const status = keepTrial ? "trialing" : "active";

    await db
      .update(subscriptions)
      .set({
        planId: plan.id,
        status,
        billingCycle: data.billingCycle,
        currentPeriodStart: now,
        currentPeriodEnd,
        trialEndsAt: keepTrial ? existing.trialEndsAt : null,
        cancelAtPeriodEnd: false,
        updatedAt: now,
      })
      .where(eq(subscriptions.id, existing.id));

    await writeAudit(context.env, {
      workspaceId,
      actorUserId: user.id,
      action: "subscription.changed",
      entityType: "subscription",
      entityId: existing.id,
      metadata: {
        fromPlanId: existing.planId,
        toPlanId: plan.id,
        billingCycle: data.billingCycle,
      },
    });

    return ok(context, {
      subscription: {
        id: existing.id,
        planId: plan.id,
        status,
        billingCycle: data.billingCycle,
        currentPeriodStart: now,
        currentPeriodEnd,
        trialEndsAt: keepTrial ? existing.trialEndsAt : null,
      },
    });
  },
);

subscriptionRoutes.post(
  "/cancel",
  requirePermission("subscription:manage"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const db = getDb(context.env);

    const existingRows = await db
      .select()
      .from(subscriptions)
      .where(eq(subscriptions.workspaceId, access.workspace.id))
      .limit(1);
    const existing = existingRows[0];
    if (!existing) throw notFound("Subscription not found.");

    await db
      .update(subscriptions)
      .set({ cancelAtPeriodEnd: true, updatedAt: Date.now() })
      .where(eq(subscriptions.id, existing.id));

    await writeAudit(context.env, {
      workspaceId: access.workspace.id,
      actorUserId: user.id,
      action: "subscription.cancel_scheduled",
      entityType: "subscription",
      entityId: existing.id,
    });

    return ok(context, {
      subscriptionId: existing.id,
      cancelAtPeriodEnd: true,
    });
  },
);
