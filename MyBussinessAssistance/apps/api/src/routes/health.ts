import { Hono } from "hono";
import { APP_TIMEZONE, type HealthPayload } from "@mba/shared";
import type { AppEnv } from "../env";
import { ok, fail } from "../lib/response";

export const healthRoutes = new Hono<AppEnv>();

const version = "0.1.0";

healthRoutes.get("/", (context) => {
  const payload: HealthPayload = {
    status: "ok",
    service: "mybusiness-assistant-api",
    version,
    environment: context.env.ENVIRONMENT,
    timezone: APP_TIMEZONE,
    timestamp: new Date().toISOString(),
    checks: { database: "ok" },
  };

  return ok(context, payload);
});

healthRoutes.get("/db", async (context) => {
  try {
    await context.env.DB.prepare("SELECT 1 AS ok").first();
    return ok(context, {
      database: "ok",
      checkedAt: new Date().toISOString(),
    });
  } catch (error) {
    console.error("Database health check failed", error instanceof Error ? error.message : error);
    return fail(
      context,
      503,
      "database_unavailable",
      "Database health check failed",
    );
  }
});
