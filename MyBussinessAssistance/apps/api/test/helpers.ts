import { SELF } from "cloudflare:test";
import { getDb } from "../src/db";
import { plans, subscriptions } from "@mba/db";
import { eq } from "drizzle-orm";

export const API = "http://example.com/api/v1";

export type TestUser = {
  email: string;
  password: string;
  cookie: string;
  userId: string;
};

let counter = 0;

export async function registerTestUser(
  nameSuffix = "",
): Promise<TestUser> {
  counter += 1;
  const email = `owner${counter}${nameSuffix}@test.local`;
  const password = "Sup3rSecurePass!";

  const response = await SELF.fetch(`${API}/auth/register`, {
    method: "POST",
    headers: { "content-type": "application/json" },
    body: JSON.stringify({ email, password, name: `Owner ${counter}`, locale: "en" }),
  });

  if (response.status !== 201) {
    throw new Error(`register failed: ${response.status} ${await response.text()}`);
  }

  const body = (await response.json()) as { data: { user: { id: string } } };
  const setCookie = response.headers.get("set-cookie") ?? "";
  const cookie = setCookie.split(";")[0] ?? "";

  return { email, password, cookie, userId: body.data.user.id };
}

export async function createTestWorkspace(
  user: TestUser,
  name = "Test Business",
): Promise<string> {
  const response = await SELF.fetch(`${API}/workspaces`, {
    method: "POST",
    headers: {
      "content-type": "application/json",
      cookie: user.cookie,
    },
    body: JSON.stringify({ name, defaultCurrency: "USD", locale: "en" }),
  });

  if (response.status !== 201) {
    throw new Error(
      `workspace create failed: ${response.status} ${await response.text()}`,
    );
  }

  const body = (await response.json()) as { data: { workspace: { id: string } } };
  return body.data.workspace.id;
}

export async function setWorkspacePlan(
  env: unknown,
  workspaceId: string,
  planSlug: "free" | "pro" | "enterprise",
  options: { status?: string; trialEndsAt?: number | null; cancelAtPeriodEnd?: boolean; periodEndsIn?: number } = {},
) {
  const db = getDb(env as never);
  const planRows = await db
    .select({ id: plans.id })
    .from(plans)
    .where(eq(plans.slug, planSlug))
    .limit(1);
  const planId = planRows[0]!.id;
  const now = Date.now();

  const existing = await db
    .select({ id: subscriptions.id })
    .from(subscriptions)
    .where(eq(subscriptions.workspaceId, workspaceId))
    .limit(1);

  const values = {
    planId,
    status: options.status ?? "active",
    billingCycle: "monthly" as const,
    currentPeriodStart: now,
    currentPeriodEnd: now + (options.periodEndsIn ?? 30 * 24 * 60 * 60 * 1_000),
    trialEndsAt: options.trialEndsAt ?? null,
    cancelAtPeriodEnd: options.cancelAtPeriodEnd ?? false,
    updatedAt: now,
  };

  if (existing[0]) {
    await db
      .update(subscriptions)
      .set(values)
      .where(eq(subscriptions.id, existing[0].id));
  } else {
    await db.insert(subscriptions).values({
      id: `sub_test_${workspaceId}`,
      workspaceId,
      ...values,
    });
  }
}
