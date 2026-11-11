import { createMiddleware } from "hono/factory";
import { and, eq } from "drizzle-orm";
import { platformAdmins } from "@mba/db";
import type { AppEnv } from "../env";
import { getDb } from "../db";
import { forbidden } from "../lib/errors";

export const requirePlatformAdmin = createMiddleware<AppEnv>(
  async (context, next) => {
    const user = context.get("user");

    if (!user) {
      throw forbidden();
    }

    const rows = await getDb(context.env)
      .select({ role: platformAdmins.role })
      .from(platformAdmins)
      .where(eq(platformAdmins.userId, user.id))
      .limit(1);

    if (
      rows.length === 0 ||
      (rows[0].role !== "super_admin" && rows[0].role !== "support_admin")
    ) {
      throw forbidden("Platform administrator access is required.");
    }

    context.set("platformRole", rows[0].role);

    await next();
  },
);

export const requirePlatformSuperAdmin = createMiddleware<AppEnv>(
  async (context, next) => {
    const user = context.get("user");

    if (!user) {
      throw forbidden();
    }

    const rows = await getDb(context.env)
      .select({ role: platformAdmins.role })
      .from(platformAdmins)
      .where(
        and(
          eq(platformAdmins.userId, user.id),
          eq(platformAdmins.role, "super_admin"),
        ),
      )
      .limit(1);

    if (rows.length === 0) {
      throw forbidden("Super admin access is required.");
    }

    context.set("platformRole", rows[0].role);

    await next();
  },
);
