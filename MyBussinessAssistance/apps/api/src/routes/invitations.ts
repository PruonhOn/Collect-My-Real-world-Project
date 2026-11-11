import { Hono } from "hono";
import { and, eq, gt } from "drizzle-orm";
import {
  invitations,
  roles,
  users,
  workspaceMembers,
  workspaces,
} from "@mba/db";
import { acceptInvitationSchema } from "@mba/shared";
import type { AppEnv } from "../env";
import { getDb } from "../db";
import { badRequest, forbidden, notFound } from "../lib/errors";
import { ok } from "../lib/response";
import { newId } from "../lib/ids";
import { sha256Hex } from "../lib/crypto";
import { parseJson } from "../lib/validate";
import { requireAuth } from "../middleware/auth";
import { writeAudit } from "../services/audit";

export const invitationRoutes = new Hono<AppEnv>();

async function findPendingInvitation(
  env: AppEnv["Bindings"],
  token: string,
) {
  const tokenHash = await sha256Hex(token);
  const db = getDb(env);

  const rows = await db
    .select({
      invitation: invitations,
      workspaceName: workspaces.name,
      workspaceSlug: workspaces.slug,
      workspaceStatus: workspaces.status,
      roleKey: roles.key,
      roleName: roles.name,
    })
    .from(invitations)
    .innerJoin(workspaces, eq(invitations.workspaceId, workspaces.id))
    .innerJoin(roles, eq(invitations.roleId, roles.id))
    .where(
      and(
        eq(invitations.tokenHash, tokenHash),
        eq(invitations.status, "pending"),
        gt(invitations.expiresAt, Date.now()),
      ),
    )
    .limit(1);

  return rows[0] ?? null;
}

invitationRoutes.get("/:token", async (context) => {
  const token = context.req.param("token");
  const invitation = await findPendingInvitation(context.env, token);

  if (!invitation) {
    throw notFound("This invitation is invalid or has expired.");
  }

  return ok(context, {
    invitation: {
      email: invitation.invitation.email,
      workspaceName: invitation.workspaceName,
      workspaceSlug: invitation.workspaceSlug,
      roleName: invitation.roleName,
      roleKey: invitation.roleKey,
      expiresAt: invitation.invitation.expiresAt,
    },
  });
});

invitationRoutes.post("/accept", requireAuth, async (context) => {
  const user = context.get("user");
  const data = await parseJson(context, acceptInvitationSchema);
  const invitation = await findPendingInvitation(context.env, data.token);

  if (!invitation) {
    throw notFound("This invitation is invalid or has expired.");
  }

  if (invitation.invitation.email !== user.email) {
    throw forbidden("This invitation was sent to a different email address.");
  }

  if (invitation.workspaceStatus !== "active") {
    throw badRequest("This workspace is no longer active.");
  }

  const db = getDb(context.env);
  const workspaceId = invitation.invitation.workspaceId;
  const now = Date.now();

  const existing = await db
    .select({ id: workspaceMembers.id })
    .from(workspaceMembers)
    .where(
      and(
        eq(workspaceMembers.workspaceId, workspaceId),
        eq(workspaceMembers.userId, user.id),
      ),
    )
    .limit(1);

  if (existing.length > 0) {
    await db
      .update(workspaceMembers)
      .set({
        roleId: invitation.invitation.roleId,
        status: "active",
        joinedAt: now,
      })
      .where(eq(workspaceMembers.id, existing[0].id));
  } else {
    await db.insert(workspaceMembers).values({
      id: newId("mem"),
      workspaceId,
      userId: user.id,
      roleId: invitation.invitation.roleId,
      status: "active",
      joinedAt: now,
    });
  }

  await db
    .update(invitations)
    .set({ status: "accepted" })
    .where(eq(invitations.id, invitation.invitation.id));

  await writeAudit(context.env, {
    workspaceId,
    actorUserId: user.id,
    action: "invitation.accepted",
    entityType: "invitation",
    entityId: invitation.invitation.id,
  });

  await db
    .update(users)
    .set({ updatedAt: now })
    .where(eq(users.id, user.id));

  return ok(context, {
    workspaceId,
    workspaceName: invitation.workspaceName,
    roleKey: invitation.roleKey,
  });
});
