import { and, desc, eq, isNull, lt, or } from "drizzle-orm";
import { plans, subscriptions } from "@mba/db";
import type { Env } from "../env";
import { getDb } from "../db";
import { writeAudit } from "./audit";

const DAY_MS = 24 * 60 * 60 * 1_000;
const GRACE_DAYS = 7;
const PERIOD_DAYS = 30;

export type SubscriptionRow = typeof subscriptions.$inferSelect;
export type PlanRow = typeof plans.$inferSelect;

export type ResolvedSubscription = {
  subscription: SubscriptionRow;
  plan: PlanRow;
  transitioned: boolean;
};

async function findFreePlan(db: ReturnType<typeof getDb>) {
  const rows = await db
    .select()
    .from(plans)
    .where(eq(plans.slug, "free"))
    .limit(1);
  return rows[0] ?? null;
}

async function downgradeToFree(
  env: Env,
  subscription: SubscriptionRow,
  action: string,
  now: number,
) {
  const db = getDb(env);
  const freePlan = await findFreePlan(db);

  if (!freePlan) {
    throw new Error("Free plan is not initialized.");
  }

  const periodEnd = now + PERIOD_DAYS * DAY_MS;

  const updated = await db
    .update(subscriptions)
    .set({
      planId: freePlan.id,
      status: "active",
      billingCycle: "monthly",
      currentPeriodStart: now,
      currentPeriodEnd: periodEnd,
      trialEndsAt: null,
      graceEndsAt: null,
      cancelAtPeriodEnd: false,
      updatedAt: now,
    })
    .where(eq(subscriptions.id, subscription.id))
    .returning();

  await writeAudit(env, {
    workspaceId: subscription.workspaceId,
    action,
    entityType: "subscription",
    entityId: subscription.id,
    metadata: {
      fromPlanId: subscription.planId,
      toPlanId: freePlan.id,
    },
  });

  return {
    subscription: updated[0] ?? {
      ...subscription,
      planId: freePlan.id,
      status: "active",
      billingCycle: "monthly",
      currentPeriodStart: now,
      currentPeriodEnd: periodEnd,
      trialEndsAt: null,
      graceEndsAt: null,
      cancelAtPeriodEnd: false,
      updatedAt: now,
    },
    plan: freePlan,
  };
}

async function resolveOne(
  env: Env,
  db: ReturnType<typeof getDb>,
  workspaceId: string,
): Promise<ResolvedSubscription> {
  const rows = await db
    .select({ subscription: subscriptions, plan: plans })
    .from(subscriptions)
    .innerJoin(plans, eq(plans.id, subscriptions.planId))
    .where(eq(subscriptions.workspaceId, workspaceId))
    .orderBy(desc(subscriptions.createdAt))
    .limit(1);

  const current = rows[0];
  if (!current) {
    const freePlan = await findFreePlan(db);
    if (!freePlan) {
      throw new Error("Free plan is not initialized.");
    }
    return {
      subscription: {
        id: "",
        workspaceId,
        planId: freePlan.id,
        status: "active",
        billingCycle: "monthly",
        currentPeriodStart: null,
        currentPeriodEnd: null,
        trialEndsAt: null,
        graceEndsAt: null,
        cancelAtPeriodEnd: false,
        createdAt: 0,
        updatedAt: 0,
      },
      plan: freePlan,
      transitioned: false,
    };
  }

  const { subscription, plan } = current;
  const now = Date.now();

  if (
    subscription.status === "trialing" &&
    subscription.trialEndsAt !== null &&
    subscription.trialEndsAt <= now
  ) {
    const result = await downgradeToFree(
      env,
      subscription,
      "subscription.trial_expired",
      now,
    );
    return { ...result, transitioned: true };
  }

  if (
    subscription.status === "past_due" &&
    subscription.graceEndsAt !== null &&
    subscription.graceEndsAt <= now
  ) {
    const result = await downgradeToFree(
      env,
      subscription,
      "subscription.suspended",
      now,
    );
    return { ...result, transitioned: true };
  }

  if (
    subscription.status === "active" &&
    subscription.currentPeriodEnd !== null &&
    subscription.currentPeriodEnd <= now
  ) {
    if (plan.slug === "free") {
      const updated = await db
        .update(subscriptions)
        .set({
          currentPeriodStart: now,
          currentPeriodEnd: now + PERIOD_DAYS * DAY_MS,
          updatedAt: now,
        })
        .where(eq(subscriptions.id, subscription.id))
        .returning();

      return { subscription: updated[0] ?? subscription, plan, transitioned: true };
    }

    if (subscription.cancelAtPeriodEnd) {
      const result = await downgradeToFree(
        env,
        subscription,
        "subscription.ended",
        now,
      );
      return { ...result, transitioned: true };
    }

    const updated = await db
      .update(subscriptions)
      .set({
        status: "past_due",
        graceEndsAt: now + GRACE_DAYS * DAY_MS,
        updatedAt: now,
      })
      .where(eq(subscriptions.id, subscription.id))
      .returning();

    await writeAudit(env, {
      workspaceId: subscription.workspaceId,
      action: "subscription.payment_past_due",
      entityType: "subscription",
      entityId: subscription.id,
      metadata: { graceEndsAt: now + GRACE_DAYS * DAY_MS },
    });

    return { subscription: updated[0] ?? subscription, plan, transitioned: true };
  }

  return { subscription, plan, transitioned: false };
}

export async function resolveSubscription(
  env: Env,
  workspaceId: string,
): Promise<ResolvedSubscription> {
  const db = getDb(env);
  return resolveOne(env, db, workspaceId);
}

export async function runBillingSweep(env: Env): Promise<number> {
  const db = getDb(env);
  const now = Date.now();

  const expired = await db
    .select({ workspaceId: subscriptions.workspaceId })
    .from(subscriptions)
    .where(
      or(
        and(
          eq(subscriptions.status, "trialing"),
          lt(subscriptions.trialEndsAt, now),
        ),
        and(
          eq(subscriptions.status, "active"),
          lt(subscriptions.currentPeriodEnd, now),
        ),
        and(
          eq(subscriptions.status, "past_due"),
          or(
            lt(subscriptions.graceEndsAt, now),
            and(isNull(subscriptions.graceEndsAt), lt(subscriptions.currentPeriodEnd, now)),
          )!,
        ),
      )!,
    )
    .limit(500);

  let processed = 0;
  for (const row of expired) {
    try {
      const result = await resolveOne(env, db, row.workspaceId);
      if (result.transitioned) processed += 1;
    } catch (error) {
      console.error(
        `Billing sweep failed for workspace ${row.workspaceId}`,
        error,
      );
    }
  }

  return processed;
}
