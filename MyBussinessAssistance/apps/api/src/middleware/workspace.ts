import { createMiddleware } from "hono/factory";
import { and, eq, isNull } from "drizzle-orm";
import { permissions, rolePermissions, roles, workspaceMembers, workspaces } from "@mba/db";
import type { AppEnv } from "../env";
import { getDb } from "../db";
import { ApiError, badRequest, forbidden } from "../lib/errors";
import { getAllowedModules } from "../services/entitlements";

export const requireWorkspace = createMiddleware<AppEnv>(
  async (context, next) => {
    const user = context.get("user");
    const workspaceId = context.req.param("workspaceId");

    if (!workspaceId) {
      throw badRequest("Workspace is required.");
    }

    const db = getDb(context.env);

    const rows = await db
      .select({
        workspace: workspaces,
        roleId: roles.id,
        roleKey: roles.key,
      })
      .from(workspaceMembers)
      .innerJoin(workspaces, eq(workspaceMembers.workspaceId, workspaces.id))
      .innerJoin(roles, eq(workspaceMembers.roleId, roles.id))
      .where(
        and(
          eq(workspaceMembers.workspaceId, workspaceId),
          eq(workspaceMembers.userId, user.id),
          eq(workspaceMembers.status, "active"),
          eq(workspaces.status, "active"),
        ),
      )
      .limit(1);

    const row = rows[0];
    if (!row) {
      throw forbidden("You do not have access to this workspace.");
    }

    const granted = await db
      .select({ key: permissions.key })
      .from(rolePermissions)
      .innerJoin(permissions, eq(rolePermissions.permissionId, permissions.id))
      .where(eq(rolePermissions.roleId, row.roleId));

    context.set("workspace", {
      workspace: row.workspace,
      roleId: row.roleId,
      roleKey: row.roleKey,
      permissions: new Set(granted.map((entry) => entry.key)),
    });

    await next();
  },
);

export function requirePermission(permission: string) {
  return createMiddleware<AppEnv>(async (context, next) => {
    const access = context.get("workspace");

    if (!access) {
      throw forbidden();
    }

    if (!access.permissions.has(permission)) {
      throw forbidden(`Missing permission: ${permission}`);
    }

    await next();
  });
}

export function requireModule(moduleKey: string) {
  return createMiddleware<AppEnv>(async (context, next) => {
    const access = context.get("workspace");

    if (!access) {
      throw forbidden();
    }

    const modules = await getAllowedModules(
      context.env,
      access.workspace.id,
    );

    if (modules.length === 0 || !modules.includes(moduleKey)) {
      throw new ApiError(
        403,
        "module_not_in_plan",
        `The "${moduleKey}" module is not included in your current plan. Upgrade your plan to unlock it.`,
      );
    }

    await next();
  });
}

export async function findSystemRole(env: AppEnv["Bindings"], key: string) {
  const db = getDb(env);
  const rows = await db
    .select()
    .from(roles)
    .where(and(eq(roles.key, key), isNull(roles.workspaceId)))
    .limit(1);
  return rows[0] ?? null;
}
