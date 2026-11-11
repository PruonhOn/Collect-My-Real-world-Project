import { Hono } from "hono";
import { and, desc, eq, isNull, sql } from "drizzle-orm";
import { notifications } from "@mba/db";
import type { AppEnv } from "../env";
import { getDb } from "../db";
import { notFound } from "../lib/errors";
import { ok } from "../lib/response";
import { requirePermission } from "../middleware/workspace";

export const notificationRoutes = new Hono<AppEnv>();

notificationRoutes.get(
  "/",
  requirePermission("notification:read"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const db = getDb(context.env);
    const workspaceId = access.workspace.id;
    const limit = Math.min(
      50,
      Math.max(1, Number(context.req.query("limit")) || 30),
    );

    const rows = await db
      .select()
      .from(notifications)
      .where(
        and(
          eq(notifications.workspaceId, workspaceId),
          eq(notifications.userId, user.id),
        ),
      )
      .orderBy(desc(notifications.createdAt))
      .limit(limit);

    const unread = await db
      .select({ total: sql<number>`count(*)` })
      .from(notifications)
      .where(
        and(
          eq(notifications.workspaceId, workspaceId),
          eq(notifications.userId, user.id),
          isNull(notifications.readAt),
        ),
      );

    return ok(context, {
      notifications: rows,
      unreadCount: Number(unread[0]?.total ?? 0),
    });
  },
);

notificationRoutes.patch(
  "/:notificationId/read",
  requirePermission("notification:read"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const notificationId = context.req.param("notificationId");
    const db = getDb(context.env);
    const now = Date.now();

    const rows = await db
      .update(notifications)
      .set({ readAt: now, status: "read" })
      .where(
        and(
          eq(notifications.id, notificationId),
          eq(notifications.workspaceId, access.workspace.id),
          eq(notifications.userId, user.id),
        ),
      )
      .returning({ id: notifications.id });

    if (rows.length === 0) throw notFound("Notification not found.");

    return ok(context, { notificationId, readAt: now });
  },
);

notificationRoutes.post(
  "/read-all",
  requirePermission("notification:read"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const db = getDb(context.env);
    const now = Date.now();

    const rows = await db
      .update(notifications)
      .set({ readAt: now, status: "read" })
      .where(
        and(
          eq(notifications.workspaceId, access.workspace.id),
          eq(notifications.userId, user.id),
          isNull(notifications.readAt),
        ),
      )
      .returning({ id: notifications.id });

    return ok(context, { marked: rows.length, readAt: now });
  },
);
