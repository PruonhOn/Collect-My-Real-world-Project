import { and, eq } from "drizzle-orm";
import { notifications, workspaceMembers } from "@mba/db";
import type { Env } from "../env";
import { getDb } from "../db";
import { newId } from "../lib/ids";

export type NotificationEntry = {
  workspaceId: string;
  userId: string;
  type: string;
  title: string;
  body?: string | null;
};

export async function createNotification(
  env: Env,
  entry: NotificationEntry,
) {
  try {
    const db = getDb(env);
    await db.insert(notifications).values({
      id: newId("ntf"),
      workspaceId: entry.workspaceId,
      userId: entry.userId,
      type: entry.type,
      title: entry.title,
      body: entry.body ?? null,
      channel: "in_app",
      status: "sent",
    });
  } catch (error) {
    console.error("Failed to create notification", error instanceof Error ? error.message : error);
  }
}

export async function notifyWorkspaceMembers(
  env: Env,
  entry: Omit<NotificationEntry, "userId"> & { actorUserId?: string | null },
) {
  try {
    const db = getDb(env);
    const members = await db
      .select({ userId: workspaceMembers.userId })
      .from(workspaceMembers)
      .where(
        and(
          eq(workspaceMembers.workspaceId, entry.workspaceId),
          eq(workspaceMembers.status, "active"),
        ),
      );

    const recipients = members.filter(
      (member) => member.userId !== entry.actorUserId,
    );

    if (recipients.length === 0) return;

    await db.insert(notifications).values(
      recipients.map((member) => ({
        id: newId("ntf"),
        workspaceId: entry.workspaceId,
        userId: member.userId,
        type: entry.type,
        title: entry.title,
        body: entry.body ?? null,
        channel: "in_app",
        status: "sent",
      })),
    );
  } catch (error) {
    console.error("Failed to notify workspace members", error instanceof Error ? error.message : error);
  }
}
