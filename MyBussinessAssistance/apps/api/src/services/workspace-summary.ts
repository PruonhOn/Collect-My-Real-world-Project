import { and, eq, inArray } from "drizzle-orm";
import {
  permissions,
  planEntitlements,
  plans,
  rolePermissions,
  roles,
  subscriptions,
  workspaceMembers,
  workspaces,
  type Database,
} from "@mba/db";

export type WorkspaceSummary = {
  memberId: string;
  workspaceId: string;
  name: string;
  slug: string;
  defaultCurrency: string;
  locale: string;
  status: string;
  roleId: string;
  roleKey: string;
  roleName: string;
  planName: string | null;
  planSlug: string | null;
  planModules: string[];
  subscriptionStatus: string | null;
  trialEndsAt: number | null;
  permissions: string[];
};

export async function listWorkspaceSummaries(
  db: Database,
  userId: string,
): Promise<WorkspaceSummary[]> {
  const rows = await db
    .select({
      memberId: workspaceMembers.id,
      workspaceId: workspaces.id,
      name: workspaces.name,
      slug: workspaces.slug,
      defaultCurrency: workspaces.defaultCurrency,
      locale: workspaces.locale,
      status: workspaces.status,
      roleId: roles.id,
      roleKey: roles.key,
      roleName: roles.name,
      planId: plans.id,
      planName: plans.name,
      planSlug: plans.slug,
      subscriptionStatus: subscriptions.status,
      trialEndsAt: subscriptions.trialEndsAt,
    })
    .from(workspaceMembers)
    .innerJoin(workspaces, eq(workspaceMembers.workspaceId, workspaces.id))
    .innerJoin(roles, eq(workspaceMembers.roleId, roles.id))
    .leftJoin(
      subscriptions,
      eq(subscriptions.workspaceId, workspaces.id),
    )
    .leftJoin(plans, eq(plans.id, subscriptions.planId))
    .where(
      and(
        eq(workspaceMembers.userId, userId),
        eq(workspaceMembers.status, "active"),
        eq(workspaces.status, "active"),
      ),
    );

  const roleIds = Array.from(new Set(rows.map((row) => row.roleId)));
  const permissionRows =
    roleIds.length > 0
      ? await db
          .select({
            roleId: rolePermissions.roleId,
            key: permissions.key,
          })
          .from(rolePermissions)
          .innerJoin(
            permissions,
            eq(rolePermissions.permissionId, permissions.id),
          )
          .where(inArray(rolePermissions.roleId, roleIds))
      : [];
  const permissionMap = new Map<string, string[]>();
  for (const permission of permissionRows) {
    const list = permissionMap.get(permission.roleId) ?? [];
    list.push(permission.key);
    permissionMap.set(permission.roleId, list);
  }

  const planIds = Array.from(
    new Set(rows.map((row) => row.planId).filter((id): id is string => !!id)),
  );
  const moduleRows =
    planIds.length > 0
      ? await db
          .select({
            planId: planEntitlements.planId,
            value: planEntitlements.value,
          })
          .from(planEntitlements)
          .where(
            and(
              inArray(planEntitlements.planId, planIds),
              eq(planEntitlements.key, "modules"),
            ),
          )
      : [];
  const modulesMap = new Map<string, string[]>();
  for (const row of moduleRows) {
    try {
      const parsed = JSON.parse(row.value);
      if (Array.isArray(parsed)) {
        modulesMap.set(
          row.planId,
          parsed.filter((entry): entry is string => typeof entry === "string"),
        );
      }
    } catch {
      modulesMap.set(row.planId, []);
    }
  }

  return rows.map(({ planId, ...row }) => ({
    ...row,
    planName: row.planName ?? null,
    planSlug: row.planSlug ?? null,
    planModules: planId ? (modulesMap.get(planId) ?? []) : [],
    permissions: permissionMap.get(row.roleId) ?? [],
  }));
}
