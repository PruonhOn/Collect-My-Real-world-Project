import { auditLogs } from "@mba/db";
import type { Env } from "../env";
import { getDb } from "../db";
import { newId } from "../lib/ids";

export type AuditEntry = {
  workspaceId?: string | null;
  actorUserId?: string | null;
  action: string;
  entityType?: string;
  entityId?: string;
  metadata?: Record<string, unknown>;
  ip?: string;
  userAgent?: string;
};

export async function writeAudit(env: Env, entry: AuditEntry) {
  try {
    const db = getDb(env);
    await db.insert(auditLogs).values({
      id: newId("audit"),
      workspaceId: entry.workspaceId ?? null,
      actorUserId: entry.actorUserId ?? null,
      action: entry.action,
      entityType: entry.entityType ?? null,
      entityId: entry.entityId ?? null,
      metadata: entry.metadata ? JSON.stringify(entry.metadata) : null,
      ip: entry.ip ?? null,
      userAgent: entry.userAgent ?? null,
    });
  } catch (error) {
    console.error("Failed to write audit log", error instanceof Error ? error.message : error);
  }
}
