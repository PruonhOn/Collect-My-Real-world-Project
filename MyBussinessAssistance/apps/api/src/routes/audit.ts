import { Hono } from "hono";
import { and, desc, eq, like, or } from "drizzle-orm";
import { auditLogs, users } from "@mba/db";
import type { AppEnv } from "../env";
import { getDb } from "../db";
import { ok } from "../lib/response";
import { requirePermission } from "../middleware/workspace";

export const auditRoutes = new Hono<AppEnv>();

function parseMetadata(value: string | null) {
  if (!value) return null;
  try {
    return JSON.parse(value) as unknown;
  } catch {
    return value;
  }
}

auditRoutes.get("/", requirePermission("settings:read"), async (context) => {
  const access = context.get("workspace");
  const db = getDb(context.env);
  const workspaceId = access.workspace.id;
  const limit = Math.min(
    100,
    Math.max(1, Number(context.req.query("limit")) || 50),
  );
  const action = context.req.query("action")?.trim();
  const entityType = context.req.query("entityType")?.trim();
  const search = context.req.query("search")?.trim();

  const filters = [eq(auditLogs.workspaceId, workspaceId)];
  if (action) filters.push(like(auditLogs.action, `%${action}%`));
  if (entityType) filters.push(like(auditLogs.entityType, `%${entityType}%`));
  if (search) {
    const term = `%${search}%`;
    filters.push(
      or(
        like(auditLogs.action, term),
        like(auditLogs.entityType, term),
        like(auditLogs.entityId, term),
      )!,
    );
  }

  const rows = await db
    .select({
      id: auditLogs.id,
      action: auditLogs.action,
      entityType: auditLogs.entityType,
      entityId: auditLogs.entityId,
      metadata: auditLogs.metadata,
      ip: auditLogs.ip,
      userAgent: auditLogs.userAgent,
      createdAt: auditLogs.createdAt,
      actorUserId: auditLogs.actorUserId,
      actorName: users.name,
      actorEmail: users.email,
    })
    .from(auditLogs)
    .leftJoin(users, eq(auditLogs.actorUserId, users.id))
    .where(and(...filters))
    .orderBy(desc(auditLogs.createdAt))
    .limit(limit);

  return ok(context, {
    logs: rows.map((row) => ({
      ...row,
      metadata: parseMetadata(row.metadata),
    })),
  });
});
