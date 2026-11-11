import { and, eq } from "drizzle-orm";
import { usageCounters } from "@mba/db";
import { AI_USAGE_METRIC } from "@mba/shared";
import type { Env } from "../env";
import { getDb } from "../db";
import { ApiError } from "../lib/errors";
import { newId } from "../lib/ids";
import { localDateKey, startOfMonth } from "../lib/dates";
import { getPlanLimits } from "./entitlements";

function currentPeriod() {
  return localDateKey(startOfMonth()).slice(0, 7);
}

async function readUsage(env: Env, workspaceId: string, period: string) {
  const db = getDb(env);
  const rows = await db
    .select({ used: usageCounters.used })
    .from(usageCounters)
    .where(
      and(
        eq(usageCounters.workspaceId, workspaceId),
        eq(usageCounters.metric, AI_USAGE_METRIC),
        eq(usageCounters.period, period),
      ),
    )
    .limit(1);

  return Number(rows[0]?.used ?? 0);
}

export async function getAiUsageStatus(env: Env, workspaceId: string) {
  const [limits, used] = await Promise.all([
    getPlanLimits(env, workspaceId),
    readUsage(env, workspaceId, currentPeriod()),
  ]);
  const limit = limits.aiCreditsPerMonth;
  const enabled = limits.modules.includes("ai") && limit !== 0;

  return {
    enabled,
    configured: Boolean(env.OPENAI_API_KEY),
    creditsUsed: used,
    creditsLimit: limit,
    creditsRemaining:
      limit === null ? null : Math.max(0, limit - used),
    model: env.OPENAI_MODEL ?? "gpt-4o-mini",
  };
}

export async function assertAiCredits(env: Env, workspaceId: string) {
  const status = await getAiUsageStatus(env, workspaceId);

  if (!status.enabled) {
    throw new ApiError(
      403,
      "plan_limit_reached",
      "The AI assistant is available on Pro and Enterprise plans.",
    );
  }

  if (
    status.creditsLimit !== null &&
    status.creditsUsed >= status.creditsLimit
  ) {
    throw new ApiError(
      403,
      "plan_limit_reached",
      "You have used all AI credits for this month. Upgrade your plan or try again next month.",
    );
  }

  return status;
}

export async function recordAiUsage(
  env: Env,
  workspaceId: string,
  amount = 1,
) {
  const db = getDb(env);
  const period = currentPeriod();
  const now = Date.now();
  const existing = await db
    .select({ id: usageCounters.id, used: usageCounters.used })
    .from(usageCounters)
    .where(
      and(
        eq(usageCounters.workspaceId, workspaceId),
        eq(usageCounters.metric, AI_USAGE_METRIC),
        eq(usageCounters.period, period),
      ),
    )
    .limit(1);

  const row = existing[0];
  if (row) {
    const used = Number(row.used) + amount;
    await db
      .update(usageCounters)
      .set({ used, updatedAt: now })
      .where(eq(usageCounters.id, row.id));
    return used;
  }

  await db.insert(usageCounters).values({
    id: newId("usg"),
    workspaceId,
    metric: AI_USAGE_METRIC,
    period,
    used: amount,
    updatedAt: now,
  });

  return amount;
}
