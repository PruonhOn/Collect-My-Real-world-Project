import { and, eq, gte, sql } from "drizzle-orm";
import {
  branches,
  employees,
  planEntitlements,
  plans,
  products,
  purchases,
  sales,
  subscriptions,
} from "@mba/db";
import type { Env } from "../env";
import { getDb } from "../db";
import { ApiError } from "../lib/errors";
import { startOfMonth } from "../lib/dates";
import { resolveSubscription } from "./billing";

export type PlanLimits = {
  maxProducts: number | null;
  maxEmployees: number | null;
  maxBranches: number | null;
  maxTransactionsPerMonth: number | null;
  maxWorkspaces: number | null;
  storageMb: number | null;
  aiCreditsPerMonth: number | null;
  modules: string[];
};

export type PlanUsage = {
  products: number;
  employees: number;
  branches: number;
  transactionsThisMonth: number;
};

const LIMIT_KEYS = {
  maxProducts: "max_products",
  maxEmployees: "max_employees",
  maxBranches: "max_branches",
  maxTransactionsPerMonth: "max_transactions_per_month",
  maxWorkspaces: "max_workspaces",
  storageMb: "storage_mb",
  aiCreditsPerMonth: "ai_credits_per_month",
} as const;

const LIMIT_LABELS: Record<keyof typeof LIMIT_KEYS, string> = {
  maxProducts: "products",
  maxEmployees: "employees",
  maxBranches: "branches",
  maxTransactionsPerMonth: "transactions",
  maxWorkspaces: "workspaces",
  storageMb: "storage",
  aiCreditsPerMonth: "AI credits",
};

function parseLimit(value: string | undefined): number | null {
  if (value === undefined) return null;
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : null;
}

function parseModules(value: string | undefined): string[] {
  if (!value) return [];
  try {
    const parsed = JSON.parse(value);
    return Array.isArray(parsed)
      ? parsed.filter((entry): entry is string => typeof entry === "string")
      : [];
  } catch {
    return [];
  }
}

async function resolveEntitlements(env: Env, workspaceId: string) {
  const db = getDb(env);
  const resolved = await resolveSubscription(env, workspaceId);
  const planId = resolved.plan.id;

  const rows = await db
    .select({ key: planEntitlements.key, value: planEntitlements.value })
    .from(planEntitlements)
    .where(eq(planEntitlements.planId, planId));

  return new Map(rows.map((row) => [row.key, row.value]));
}

export async function getAllowedModules(
  env: Env,
  workspaceId: string,
): Promise<string[]> {
  const entitlements = await resolveEntitlements(env, workspaceId);
  return parseModules(entitlements.get("modules"));
}

export async function getPlanLimits(
  env: Env,
  workspaceId: string,
): Promise<PlanLimits> {
  const entitlements = await resolveEntitlements(env, workspaceId);

  return {
    maxProducts: parseLimit(entitlements.get(LIMIT_KEYS.maxProducts)),
    maxEmployees: parseLimit(entitlements.get(LIMIT_KEYS.maxEmployees)),
    maxBranches: parseLimit(entitlements.get(LIMIT_KEYS.maxBranches)),
    maxTransactionsPerMonth: parseLimit(
      entitlements.get(LIMIT_KEYS.maxTransactionsPerMonth),
    ),
    maxWorkspaces: parseLimit(entitlements.get(LIMIT_KEYS.maxWorkspaces)),
    storageMb: parseLimit(entitlements.get(LIMIT_KEYS.storageMb)),
    aiCreditsPerMonth: parseLimit(
      entitlements.get(LIMIT_KEYS.aiCreditsPerMonth),
    ),
    modules: parseModules(entitlements.get("modules")),
  };
}

export async function getPlanUsage(
  env: Env,
  workspaceId: string,
): Promise<PlanUsage> {
  const db = getDb(env);
  const monthStart = startOfMonth();

  const [productRow, employeeRow, branchRow, saleRow, purchaseRow] =
    await Promise.all([
      db
        .select({ count: sql<number>`count(*)` })
        .from(products)
        .where(
          and(
            eq(products.workspaceId, workspaceId),
            eq(products.status, "active"),
          ),
        ),
      db
        .select({ count: sql<number>`count(*)` })
        .from(employees)
        .where(
          and(
            eq(employees.workspaceId, workspaceId),
            eq(employees.status, "active"),
          ),
        ),
      db
        .select({ count: sql<number>`count(*)` })
        .from(branches)
        .where(
          and(
            eq(branches.workspaceId, workspaceId),
            eq(branches.status, "active"),
          ),
        ),
      db
        .select({ count: sql<number>`count(*)` })
        .from(sales)
        .where(
          and(
            eq(sales.workspaceId, workspaceId),
            eq(sales.status, "completed"),
            gte(sales.saleDate, monthStart),
          ),
        ),
      db
        .select({ count: sql<number>`count(*)` })
        .from(purchases)
        .where(
          and(
            eq(purchases.workspaceId, workspaceId),
            gte(purchases.purchaseDate, monthStart),
          ),
        ),
    ]);

  return {
    products: Number(productRow[0]?.count ?? 0),
    employees: Number(employeeRow[0]?.count ?? 0),
    branches: Number(branchRow[0]?.count ?? 0),
    transactionsThisMonth:
      Number(saleRow[0]?.count ?? 0) + Number(purchaseRow[0]?.count ?? 0),
  };
}

export async function assertWithinLimit(
  env: Env,
  workspaceId: string,
  key: keyof typeof LIMIT_KEYS,
  current: number,
) {
  const limits = await getPlanLimits(env, workspaceId);
  const limit = limits[key];

  if (limit !== null && current >= limit) {
    throw new ApiError(
      403,
      "plan_limit_reached",
      `Your current plan allows up to ${limit} ${LIMIT_LABELS[key]}. Upgrade your plan to add more.`,
    );
  }
}

export async function assertTransactionsAvailable(
  env: Env,
  workspaceId: string,
) {
  const [limits, usage] = await Promise.all([
    getPlanLimits(env, workspaceId),
    getPlanUsage(env, workspaceId),
  ]);
  const limit = limits.maxTransactionsPerMonth;

  if (limit !== null && usage.transactionsThisMonth >= limit) {
    throw new ApiError(
      403,
      "plan_limit_reached",
      `Your current plan allows up to ${limit} transactions per month. Upgrade your plan for a higher limit.`,
    );
  }
}
