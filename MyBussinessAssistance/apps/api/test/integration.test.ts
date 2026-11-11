import { describe, expect, it, beforeAll } from "vitest";
import { SELF } from "cloudflare:test";
import { env } from "cloudflare:workers";
import { and, eq, sql } from "drizzle-orm";
import { planEntitlements, plans } from "@mba/db";
import { getDb } from "../src/db";
import {
  API,
  createTestWorkspace,
  registerTestUser,
  setWorkspacePlan,
  type TestUser,
} from "./helpers";

let user: TestUser;
let workspaceId: string;

beforeAll(async () => {
  user = await registerTestUser();
  workspaceId = await createTestWorkspace(user);
});

async function setEntitlement(planSlug: string, key: string, value: string) {
  const db = getDb(env as never);
  const plan = await db
    .select({ id: plans.id })
    .from(plans)
    .where(eq(plans.slug, planSlug))
    .limit(1);

  await db
    .update(planEntitlements)
    .set({ value })
    .where(
      and(
        eq(planEntitlements.planId, plan[0]!.id),
        eq(planEntitlements.key, key),
      ),
    );
}

describe("auth flow", () => {
  it("registers a user and reports email delivery status", async () => {
    const response = await SELF.fetch(`${API}/auth/register`, {
      method: "POST",
      headers: { "content-type": "application/json" },
      body: JSON.stringify({
        email: "fresh@test.local",
        password: "Sup3rSecurePass!",
        name: "Fresh User",
        locale: "en",
      }),
    });

    expect(response.status).toBe(201);
    const body = (await response.json()) as {
      data: { user: unknown; emailSent: boolean };
    };
    expect(body.data.user).toBeDefined();
    // Email is not configured in the test environment, so delivery fails.
    expect(body.data.emailSent).toBe(false);
  });

  it("rejects duplicate registrations", async () => {
    const response = await SELF.fetch(`${API}/auth/register`, {
      method: "POST",
      headers: { "content-type": "application/json" },
      body: JSON.stringify({
        email: user.email,
        password: "Sup3rSecurePass!",
        name: "Duplicate",
        locale: "en",
      }),
    });

    expect(response.status).toBe(409);
  });

  it("lists workspaces with plan modules", async () => {
    const response = await SELF.fetch(`${API}/workspaces`, {
      headers: { cookie: user.cookie },
    });

    expect(response.status).toBe(200);
    const body = (await response.json()) as {
      data: {
        workspaces: {
          workspaceId: string;
          planSlug: string | null;
          planModules: string[];
          permissions: string[];
        }[];
      };
    };

    const mine = body.data.workspaces.find(
      (entry) => entry.workspaceId === workspaceId,
    );
    expect(mine).toBeDefined();
    // New workspaces start on a Pro trial.
    expect(mine!.planSlug).toBe("pro");
    expect(mine!.planModules).toContain("orders");
    expect(mine!.permissions.length).toBeGreaterThan(0);
  });

  it("rejects unauthenticated requests", async () => {
    const response = await SELF.fetch(`${API}/workspaces`);
    expect(response.status).toBe(401);
  });
});

const appUrl = (env as unknown as { APP_URL: string }).APP_URL;

describe("google auth flow", () => {
  it("redirects to Google with a state cookie", async () => {
    const response = await SELF.fetch(`${API}/auth/google`, {
      redirect: "manual",
    });

    expect(response.status).toBe(302);
    const location = response.headers.get("location") ?? "";
    expect(location).toContain("https://accounts.google.com/o/oauth2/v2/auth");
    expect(location).toContain(
      `client_id=${(env as unknown as { GOOGLE_CLIENT_ID: string }).GOOGLE_CLIENT_ID}`,
    );
    expect(location).toContain(
      encodeURIComponent(`${appUrl}/api/v1/auth/google/callback`),
    );
    expect(location).toContain("scope=openid+email+profile");

    const setCookie = response.headers.get("set-cookie") ?? "";
    expect(setCookie).toContain("mba_google_state=");
    expect(setCookie).toContain("HttpOnly");
  });

  it("rejects a callback whose state does not match the cookie", async () => {
    const response = await SELF.fetch(
      `${API}/auth/google/callback?code=fake&state=attacker-state`,
      {
        headers: { cookie: "mba_google_state=real-state" },
        redirect: "manual",
      },
    );

    expect(response.status).toBe(302);
    expect(response.headers.get("location")).toBe(
      `${appUrl}/login?error=google`,
    );
  });

  it("redirects to the login page when Google reports an error", async () => {
    const response = await SELF.fetch(
      `${API}/auth/google/callback?error=access_denied`,
      { redirect: "manual" },
    );

    expect(response.status).toBe(302);
    expect(response.headers.get("location")).toBe(
      `${appUrl}/login?error=google`,
    );
  });
});

describe("plan module gating", () => {
  it("allows paid modules while on the Pro trial", async () => {
    const response = await SELF.fetch(
      `${API}/workspaces/${workspaceId}/orders`,
      { headers: { cookie: user.cookie } },
    );
    expect(response.status).toBe(200);
  });

  it("blocks paid modules on the Free plan", async () => {
    await setWorkspacePlan(env, workspaceId, "free");

    const response = await SELF.fetch(
      `${API}/workspaces/${workspaceId}/orders`,
      { headers: { cookie: user.cookie } },
    );

    expect(response.status).toBe(403);
    const body = (await response.json()) as { error: { code: string } };
    expect(body.error.code).toBe("module_not_in_plan");
  });

  it("blocks audit and transfers on the Free plan", async () => {
    const auditResponse = await SELF.fetch(
      `${API}/workspaces/${workspaceId}/audit-logs`,
      { headers: { cookie: user.cookie } },
    );
    expect(auditResponse.status).toBe(403);

    const transferResponse = await SELF.fetch(
      `${API}/workspaces/${workspaceId}/transfers`,
      { headers: { cookie: user.cookie } },
    );
    expect(transferResponse.status).toBe(403);
  });
});

describe("plan limits", () => {
  it("enforces the product limit", async () => {
    await setEntitlement("free", "max_products", "2");
    await setWorkspacePlan(env, workspaceId, "free");

    const createProduct = async (sku: string) =>
      SELF.fetch(`${API}/workspaces/${workspaceId}/products`, {
        method: "POST",
        headers: { "content-type": "application/json", cookie: user.cookie },
        body: JSON.stringify({
          name: `Product ${sku}`,
          sku,
          unit: "pcs",
          costPriceCents: 100,
          sellingPriceCents: 200,
          trackInventory: false,
          lowStockThreshold: 5,
        }),
      });

    const first = await createProduct("SKU-LIMIT-1");
    expect(first.status).toBe(201);

    const second = await createProduct("SKU-LIMIT-2");
    expect(second.status).toBe(201);

    const third = await createProduct("SKU-LIMIT-3");
    expect(third.status).toBe(403);
    const body = (await third.json()) as { error: { code: string } };
    expect(body.error.code).toBe("plan_limit_reached");

    // Restore headroom for later tests.
    await setEntitlement("free", "max_products", "10");
  });

  it("enforces the monthly transaction limit", async () => {
    await setEntitlement("free", "max_transactions_per_month", "0");

    const productResponse = await SELF.fetch(
      `${API}/workspaces/${workspaceId}/products`,
      {
        method: "POST",
        headers: { "content-type": "application/json", cookie: user.cookie },
        body: JSON.stringify({
          name: "Sale product",
          sku: "SKU-SALE-1",
          unit: "pcs",
          costPriceCents: 100,
          sellingPriceCents: 500,
          trackInventory: false,
          lowStockThreshold: 0,
        }),
      },
    );
    expect(productResponse.status).toBe(201);
    const product = (await productResponse.json()) as {
      data: { product: { id: string } };
    };

    const branchResponse = await SELF.fetch(`${API}/workspaces/${workspaceId}`, {
      headers: { cookie: user.cookie },
    });
    const detail = (await branchResponse.json()) as {
      data: { branches: { id: string }[] };
    };

    const saleResponse = await SELF.fetch(
      `${API}/workspaces/${workspaceId}/sales`,
      {
        method: "POST",
        headers: { "content-type": "application/json", cookie: user.cookie },
        body: JSON.stringify({
          branchId: detail.data.branches[0]!.id,
          items: [{ productId: product.data.product.id, quantity: 1 }],
        }),
      },
    );

    expect(saleResponse.status).toBe(403);
    const body = (await saleResponse.json()) as { error: { code: string } };
    expect(body.error.code).toBe("plan_limit_reached");
  });
});

describe("billing lifecycle", () => {
  it("expires an ended trial down to the Free plan", async () => {
    await setWorkspacePlan(env, workspaceId, "pro", {
      status: "trialing",
      trialEndsAt: Date.now() - 1_000,
      periodEndsIn: -1_000,
    });

    const response = await SELF.fetch(`${API}/workspaces/${workspaceId}`, {
      headers: { cookie: user.cookie },
    });

    expect(response.status).toBe(200);
    const body = (await response.json()) as {
      data: { subscription: { planSlug: string; status: string } | null };
    };
    expect(body.data.subscription?.planSlug).toBe("free");
    expect(body.data.subscription?.status).toBe("active");
  });

  it("downgrades to Free when a cancelled period ends", async () => {
    await setWorkspacePlan(env, workspaceId, "pro", {
      cancelAtPeriodEnd: true,
      periodEndsIn: -1_000,
    });

    const response = await SELF.fetch(`${API}/workspaces/${workspaceId}`, {
      headers: { cookie: user.cookie },
    });

    const body = (await response.json()) as {
      data: {
        subscription: { planSlug: string; cancelAtPeriodEnd: boolean } | null;
      };
    };
    expect(body.data.subscription?.planSlug).toBe("free");
    expect(body.data.subscription?.cancelAtPeriodEnd).toBe(false);
  });

  it("marks a lapsed paid subscription past due with a grace period", async () => {
    await setWorkspacePlan(env, workspaceId, "pro", { periodEndsIn: -1_000 });

    const response = await SELF.fetch(`${API}/workspaces/${workspaceId}`, {
      headers: { cookie: user.cookie },
    });

    const body = (await response.json()) as {
      data: {
        subscription: {
          planSlug: string;
          status: string;
          graceEndsAt: number | null;
        } | null;
      };
    };
    expect(body.data.subscription?.planSlug).toBe("pro");
    expect(body.data.subscription?.status).toBe("past_due");
    expect(body.data.subscription?.graceEndsAt).toBeGreaterThan(Date.now());
  });

  it("suspends a subscription whose grace period has ended", async () => {
    const { subscriptions } = await import("@mba/db");
    const db = getDb(env as never);
    await setWorkspacePlan(env, workspaceId, "pro", { periodEndsIn: -1_000 });

    await db
      .update(subscriptions)
      .set({ status: "past_due", graceEndsAt: Date.now() - 1_000 })
      .where(eq(subscriptions.workspaceId, workspaceId));

    const response = await SELF.fetch(`${API}/workspaces/${workspaceId}`, {
      headers: { cookie: user.cookie },
    });

    const body = (await response.json()) as {
      data: { subscription: { planSlug: string; status: string } | null };
    };
    expect(body.data.subscription?.planSlug).toBe("free");
    expect(body.data.subscription?.status).toBe("active");
  });
});

describe("platform admin", () => {
  it("rejects non-admin users", async () => {
    const response = await SELF.fetch(`${API}/admin/overview`, {
      headers: { cookie: user.cookie },
    });
    expect(response.status).toBe(403);
  });

  it("allows platform admins to view the overview", async () => {
    const db = getDb(env as never);
    await db.run(
      sql`INSERT OR IGNORE INTO platform_admins (id, user_id, role) VALUES ('pa_test', ${user.userId}, 'super_admin')`,
    );

    const response = await SELF.fetch(`${API}/admin/overview`, {
      headers: { cookie: user.cookie },
    });

    expect(response.status).toBe(200);
    const body = (await response.json()) as {
      data: { workspaces: number; users: number };
    };
    expect(body.data.workspaces).toBeGreaterThan(0);
  });

  it("support_admin can view but not mutate", async () => {
    const support = await registerTestUser("-support");
    const db = getDb(env as never);
    await db.run(
      sql`INSERT OR IGNORE INTO platform_admins (id, user_id, role) VALUES ('pa_support', ${support.userId}, 'support_admin')`,
    );

    const overview = await SELF.fetch(`${API}/admin/overview`, {
      headers: { cookie: support.cookie },
    });
    expect(overview.status).toBe(200);

    const planResponse = await SELF.fetch(`${API}/admin/plans/plan_free`, {
      method: "PATCH",
      headers: { "content-type": "application/json", cookie: support.cookie },
      body: JSON.stringify({ description: "Support cannot edit" }),
    });
    expect(planResponse.status).toBe(403);

    const workspaceResponse = await SELF.fetch(
      `${API}/admin/workspaces/${workspaceId}`,
      {
        method: "PATCH",
        headers: { "content-type": "application/json", cookie: support.cookie },
        body: JSON.stringify({ status: "suspended" }),
      },
    );
    expect(workspaceResponse.status).toBe(403);

    const entitlementResponse = await SELF.fetch(
      `${API}/admin/plans/plan_free/entitlements`,
      {
        method: "PUT",
        headers: { "content-type": "application/json", cookie: support.cookie },
        body: JSON.stringify({ key: "max_products", value: "1" }),
      },
    );
    expect(entitlementResponse.status).toBe(403);
  });

  it("super_admin can mutate plans", async () => {
    const db = getDb(env as never);
    await db.run(
      sql`INSERT OR IGNORE INTO platform_admins (id, user_id, role) VALUES ('pa_test', ${user.userId}, 'super_admin')`,
    );

    const response = await SELF.fetch(`${API}/admin/plans/plan_free`, {
      method: "PATCH",
      headers: { "content-type": "application/json", cookie: user.cookie },
      body: JSON.stringify({ description: "Super admin can edit" }),
    });

    expect(response.status).toBe(200);
  });
});
