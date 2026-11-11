import { Hono } from "hono";
import { and, eq, gt, isNull, or } from "drizzle-orm";
import {
  branches,
  invitations,
  plans,
  roles,
  subscriptions,
  users,
  workspaceMembers,
  workspaceSettings,
  workspaces,
} from "@mba/db";
import {
  createBranchSchema,
  createWorkspaceSchema,
  inviteMemberSchema,
  updateBranchSchema,
  updateMemberSchema,
  updateWorkspaceSchema,
} from "@mba/shared";
import type { AppEnv } from "../env";
import { getDb } from "../db";
import { ApiError, badRequest, conflict, forbidden, notFound } from "../lib/errors";
import { created, ok } from "../lib/response";
import { randomToken, sha256Hex } from "../lib/crypto";
import { newId } from "../lib/ids";
import { buildWorkspaceSlug } from "../lib/slug";
import { parseJson } from "../lib/validate";
import { requireAuth, requireVerifiedEmail } from "../middleware/auth";
import {
  requireModule,
  requirePermission,
  requireWorkspace,
} from "../middleware/workspace";
import { writeAudit } from "../services/audit";
import { listWorkspaceSummaries } from "../services/workspace-summary";
import { sendInvitationEmail } from "../services/email-templates";
import { createNotification } from "../services/notifications";
import { resolveSubscription } from "../services/billing";
import { aiRoutes } from "./ai";
import { auditRoutes } from "./audit";
import {
  categoryRoutes,
  productRoutes,
  stockRoutes,
} from "./catalog";
import { customerRoutes, supplierRoutes } from "./contacts";
import { employeeRoutes } from "./employees";
import { expenseRoutes } from "./expenses";
import { notificationRoutes } from "./notifications";
import { orderRoutes } from "./orders";
import { payrollRoutes } from "./payroll";
import { purchaseRoutes } from "./purchases";
import { reportRoutes } from "./reports";
import { saleRoutes } from "./sales";
import { subscriptionRoutes } from "./subscription";
import { transferRoutes } from "./transfers";
import {
  assertWithinLimit,
  getAllowedModules,
  getPlanLimits,
  getPlanUsage,
} from "../services/entitlements";

const DAY_MS = 24 * 60 * 60 * 1_000;

function withModule<T extends Hono<AppEnv>>(moduleKey: string, router: T) {
  const wrapped = new Hono<AppEnv>();
  wrapped.use("*", requireModule(moduleKey));
  wrapped.route("/", router);
  return wrapped;
}

export const workspaceRoutes = new Hono<AppEnv>();

workspaceRoutes.get("/", requireAuth, async (context) => {
  const user = context.get("user");
  const db = getDb(context.env);
  const summaries = await listWorkspaceSummaries(db, user.id);
  return ok(context, { workspaces: summaries });
});

workspaceRoutes.post("/", requireAuth, async (context) => {
  const data = await parseJson(context, createWorkspaceSchema);
  const user = context.get("user");
  const db = getDb(context.env);

  const ownerRoles = await db
    .select()
    .from(roles)
    .where(and(eq(roles.key, "owner"), isNull(roles.workspaceId)))
    .limit(1);
  const ownerRole = ownerRoles[0];

  if (!ownerRole) {
    throw new ApiError(
      500,
      "seed_missing",
      "Workspace roles have not been initialized.",
    );
  }

  const proPlans = await db
    .select()
    .from(plans)
    .where(eq(plans.slug, "pro"))
    .limit(1);
  const freePlans = await db
    .select()
    .from(plans)
    .where(eq(plans.slug, "free"))
    .limit(1);
  const plan = proPlans[0] ?? freePlans[0];

  if (!plan) {
    throw new ApiError(
      500,
      "seed_missing",
      "Subscription plans have not been initialized.",
    );
  }

  let slug = buildWorkspaceSlug(data.name);
  for (let attempt = 0; attempt < 5; attempt += 1) {
    const duplicate = await db
      .select({ id: workspaces.id })
      .from(workspaces)
      .where(eq(workspaces.slug, slug))
      .limit(1);
    if (duplicate.length === 0) break;
    slug = buildWorkspaceSlug(data.name);
  }

  const now = Date.now();
  const workspaceId = newId("wsp");
  const branchId = newId("brn");
  const memberId = newId("mem");
  const subscriptionId = newId("sub");
  const trialEndsAt =
    plan.trialDays > 0 ? now + plan.trialDays * DAY_MS : null;
  const subscriptionStatus = trialEndsAt ? "trialing" : "active";
  const periodEnd = trialEndsAt ?? now + 30 * DAY_MS;

  await db.batch([
    db.insert(workspaces).values({
      id: workspaceId,
      name: data.name,
      slug,
      ownerUserId: user.id,
      defaultCurrency: data.defaultCurrency,
      locale: data.locale,
    }),
    db.insert(workspaceSettings).values({ workspaceId }),
    db.insert(branches).values({
      id: branchId,
      workspaceId,
      name: "Main branch",
      code: "MAIN",
      isDefault: true,
    }),
    db.insert(workspaceMembers).values({
      id: memberId,
      workspaceId,
      userId: user.id,
      roleId: ownerRole.id,
      status: "active",
      joinedAt: now,
    }),
    db.insert(subscriptions).values({
      id: subscriptionId,
      workspaceId,
      planId: plan.id,
      status: subscriptionStatus,
      billingCycle: "monthly",
      currentPeriodStart: now,
      currentPeriodEnd: periodEnd,
      trialEndsAt,
    }),
  ]);

  await writeAudit(context.env, {
    workspaceId,
    actorUserId: user.id,
    action: "workspace.created",
    entityType: "workspace",
    entityId: workspaceId,
    metadata: { name: data.name, plan: plan.slug },
  });

  await createNotification(context.env, {
    workspaceId,
    userId: user.id,
    type: "workspace.welcome",
    title: `Welcome to ${data.name}`,
    body: "Start by adding products, customers, and your team.",
  });

  return created(context, {
    workspace: {
      id: workspaceId,
      name: data.name,
      slug,
      defaultCurrency: data.defaultCurrency,
      locale: data.locale,
    },
    branchId,
    subscription: {
      id: subscriptionId,
      plan: plan.slug,
      status: subscriptionStatus,
      trialEndsAt,
      currentPeriodEnd: periodEnd,
    },
  });
});

workspaceRoutes.use("/:workspaceId", requireAuth, requireWorkspace);
workspaceRoutes.use("/:workspaceId/*", requireAuth, requireWorkspace);

workspaceRoutes.route("/:workspaceId/categories", categoryRoutes);
workspaceRoutes.route("/:workspaceId/products", productRoutes);
workspaceRoutes.route("/:workspaceId/inventory", stockRoutes);
workspaceRoutes.route("/:workspaceId/customers", customerRoutes);
workspaceRoutes.route("/:workspaceId/suppliers", supplierRoutes);
workspaceRoutes.route("/:workspaceId/sales", saleRoutes);
workspaceRoutes.route(
  "/:workspaceId/orders",
  withModule("orders", orderRoutes),
);
workspaceRoutes.route("/:workspaceId/purchases", purchaseRoutes);
workspaceRoutes.route("/:workspaceId/expenses", expenseRoutes);
workspaceRoutes.route("/:workspaceId/employees", employeeRoutes);
workspaceRoutes.route(
  "/:workspaceId/payroll",
  withModule("payroll", payrollRoutes),
);
workspaceRoutes.route(
  "/:workspaceId/transfers",
  withModule("transfers", transferRoutes),
);
workspaceRoutes.route("/:workspaceId/notifications", notificationRoutes);
workspaceRoutes.route("/:workspaceId/ai", aiRoutes);
workspaceRoutes.route(
  "/:workspaceId/audit-logs",
  withModule("audit", auditRoutes),
);
workspaceRoutes.route("/:workspaceId/subscription", subscriptionRoutes);
workspaceRoutes.route("/:workspaceId", reportRoutes);

workspaceRoutes.get(
  "/:workspaceId",
  requirePermission("settings:read"),
  async (context) => {
    const access = context.get("workspace");
    const db = getDb(context.env);
    const workspaceId = access.workspace.id;

    const branchRows = await db
      .select()
      .from(branches)
      .where(eq(branches.workspaceId, workspaceId));

    const settingsRows = await db
      .select()
      .from(workspaceSettings)
      .where(eq(workspaceSettings.workspaceId, workspaceId))
      .limit(1);

    const resolved = await resolveSubscription(context.env, workspaceId);
    const subscription = resolved.subscription;
    const subscriptionRow =
      subscription.id && subscription.createdAt
        ? {
            id: subscription.id,
            status: subscription.status,
            billingCycle: subscription.billingCycle,
            currentPeriodEnd: subscription.currentPeriodEnd,
            trialEndsAt: subscription.trialEndsAt,
            graceEndsAt: subscription.graceEndsAt,
            cancelAtPeriodEnd: subscription.cancelAtPeriodEnd,
            planSlug: resolved.plan.slug,
            planName: resolved.plan.name,
          }
        : null;

    const [limits, allowedModules] = await Promise.all([
      getPlanLimits(context.env, workspaceId),
      getAllowedModules(context.env, workspaceId),
    ]);

    return ok(context, {
      workspace: access.workspace,
      roleKey: access.roleKey,
      permissions: Array.from(access.permissions).sort(),
      branches: branchRows,
      settings: settingsRows[0] ?? null,
      subscription: subscriptionRow,
      limits,
      modules: allowedModules,
    });
  },
);

workspaceRoutes.patch(
  "/:workspaceId",
  requirePermission("settings:manage"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const data = await parseJson(context, updateWorkspaceSchema);
    const db = getDb(context.env);
    const workspaceId = access.workspace.id;
    const now = Date.now();

    if (data.name || data.defaultCurrency || data.locale) {
      await db
        .update(workspaces)
        .set({
          ...(data.name ? { name: data.name } : {}),
          ...(data.defaultCurrency
            ? { defaultCurrency: data.defaultCurrency }
            : {}),
          ...(data.locale ? { locale: data.locale } : {}),
          updatedAt: now,
        })
        .where(eq(workspaces.id, workspaceId));
    }

    const settingsPatch = {
      ...(data.taxEnabled !== undefined
        ? { taxEnabled: data.taxEnabled }
        : {}),
      ...(data.taxRateBasisPoints !== undefined
        ? { taxRateBasisPoints: data.taxRateBasisPoints }
        : {}),
      ...(data.invoicePrefix !== undefined
        ? { invoicePrefix: data.invoicePrefix }
        : {}),
      ...(data.exchangeRateKhrPerUsd !== undefined
        ? { exchangeRateKhrPerUsd: data.exchangeRateKhrPerUsd }
        : {}),
      updatedAt: now,
    };

    const hasSettingsPatch = Object.keys(settingsPatch).length > 1;
    if (hasSettingsPatch) {
      const existingSettings = await db
        .select({ workspaceId: workspaceSettings.workspaceId })
        .from(workspaceSettings)
        .where(eq(workspaceSettings.workspaceId, workspaceId))
        .limit(1);

      if (existingSettings.length > 0) {
        await db
          .update(workspaceSettings)
          .set(settingsPatch)
          .where(eq(workspaceSettings.workspaceId, workspaceId));
      } else {
        await db.insert(workspaceSettings).values({
          workspaceId,
          ...settingsPatch,
        });
      }
    }

    await writeAudit(context.env, {
      workspaceId,
      actorUserId: user.id,
      action: "workspace.updated",
      entityType: "workspace",
      entityId: workspaceId,
      metadata: data,
    });

    const updated = await db
      .select()
      .from(workspaces)
      .where(eq(workspaces.id, workspaceId))
      .limit(1);

    return ok(context, { workspace: updated[0] });
  },
);

workspaceRoutes.get(
  "/:workspaceId/members",
  requirePermission("member:read"),
  async (context) => {
    const access = context.get("workspace");
    const db = getDb(context.env);

    const rows = await db
      .select({
        memberId: workspaceMembers.id,
        userId: users.id,
        name: users.name,
        email: users.email,
        status: workspaceMembers.status,
        roleId: roles.id,
        roleKey: roles.key,
        roleName: roles.name,
        joinedAt: workspaceMembers.joinedAt,
      })
      .from(workspaceMembers)
      .innerJoin(users, eq(workspaceMembers.userId, users.id))
      .innerJoin(roles, eq(workspaceMembers.roleId, roles.id))
      .where(eq(workspaceMembers.workspaceId, access.workspace.id));

    const pending = await db
      .select({
        id: invitations.id,
        email: invitations.email,
        status: invitations.status,
        roleId: roles.id,
        roleName: roles.name,
        expiresAt: invitations.expiresAt,
        createdAt: invitations.createdAt,
      })
      .from(invitations)
      .innerJoin(roles, eq(invitations.roleId, roles.id))
      .where(
        and(
          eq(invitations.workspaceId, access.workspace.id),
          eq(invitations.status, "pending"),
          gt(invitations.expiresAt, Date.now()),
        ),
      );

    const availableRoles = await db
      .select({
        id: roles.id,
        key: roles.key,
        name: roles.name,
        isSystem: roles.isSystem,
      })
      .from(roles)
      .where(
        or(
          isNull(roles.workspaceId),
          eq(roles.workspaceId, access.workspace.id),
        ),
      );

    return ok(context, {
      members: rows,
      invitations: pending,
      roles: availableRoles,
    });
  },
);

workspaceRoutes.post(
  "/:workspaceId/invitations",
  requirePermission("member:invite"),
  requireVerifiedEmail,
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const data = await parseJson(context, inviteMemberSchema);
    const db = getDb(context.env);
    const workspaceId = access.workspace.id;

    const roleRows = await db
      .select()
      .from(roles)
      .where(eq(roles.id, data.roleId))
      .limit(1);
    const role = roleRows[0];

    if (!role || (role.workspaceId !== null && role.workspaceId !== workspaceId)) {
      throw badRequest("Select a valid role for this workspace.");
    }

    if (role.key === "owner") {
      throw forbidden("The owner role cannot be assigned through an invitation.");
    }

    const existingMembers = await db
      .select({ id: workspaceMembers.id })
      .from(workspaceMembers)
      .innerJoin(users, eq(workspaceMembers.userId, users.id))
      .where(
        and(
          eq(workspaceMembers.workspaceId, workspaceId),
          eq(users.email, data.email),
        ),
      )
      .limit(1);

    if (existingMembers.length > 0) {
      throw conflict("This person is already a member of the workspace.");
    }

    await db
      .delete(invitations)
      .where(
        and(
          eq(invitations.workspaceId, workspaceId),
          eq(invitations.email, data.email),
          eq(invitations.status, "pending"),
        ),
      );

    const token = randomToken(32);
    const tokenHash = await sha256Hex(token);
    const invitationId = newId("inv");
    const expiresAt = Date.now() + 7 * DAY_MS;

    await db.insert(invitations).values({
      id: invitationId,
      workspaceId,
      email: data.email,
      roleId: role.id,
      tokenHash,
      invitedByUserId: user.id,
      expiresAt,
    });

    let emailSent = true;
    try {
      await sendInvitationEmail(context.env, {
        to: data.email,
        workspaceName: access.workspace.name,
        inviterName: user.name,
        roleName: role.name,
        token,
      });
    } catch (error) {
      emailSent = false;
      console.error("Unable to send invitation email", error instanceof Error ? error.message : error);
    }

    await writeAudit(context.env, {
      workspaceId,
      actorUserId: user.id,
      action: "member.invited",
      entityType: "invitation",
      entityId: invitationId,
      metadata: { email: data.email, roleId: role.id },
    });

    return created(context, {
      invitation: {
        id: invitationId,
        email: data.email,
        roleId: role.id,
        roleName: role.name,
        expiresAt,
        status: "pending",
      },
      emailSent,
      devInvitationToken:
        context.env.ENVIRONMENT === "development" ? token : undefined,
    });
  },
);

workspaceRoutes.patch(
  "/:workspaceId/members/:memberId",
  requirePermission("member:manage"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const memberId = context.req.param("memberId");
    const data = await parseJson(context, updateMemberSchema);
    const db = getDb(context.env);

    const targetRows = await db
      .select({
        member: workspaceMembers,
        roleId: roles.id,
        roleKey: roles.key,
      })
      .from(workspaceMembers)
      .innerJoin(roles, eq(workspaceMembers.roleId, roles.id))
      .where(
        and(
          eq(workspaceMembers.id, memberId),
          eq(workspaceMembers.workspaceId, access.workspace.id),
        ),
      )
      .limit(1);

    const target = targetRows[0];
    if (!target) {
      throw notFound("Member not found in this workspace.");
    }

    if (target.roleKey === "owner") {
      throw forbidden("The workspace owner cannot be changed.");
    }

    const roleRows = await db
      .select()
      .from(roles)
      .where(eq(roles.id, data.roleId))
      .limit(1);
    const role = roleRows[0];

    if (
      !role ||
      (role.workspaceId !== null && role.workspaceId !== access.workspace.id)
    ) {
      throw badRequest("Select a valid role for this workspace.");
    }

    if (role.key === "owner") {
      throw forbidden("Ownership transfer is not supported here.");
    }

    await db
      .update(workspaceMembers)
      .set({ roleId: role.id })
      .where(eq(workspaceMembers.id, memberId));

    await writeAudit(context.env, {
      workspaceId: access.workspace.id,
      actorUserId: user.id,
      action: "member.role_changed",
      entityType: "workspace_member",
      entityId: memberId,
      metadata: { from: target.roleKey, to: role.key },
    });

    return ok(context, { memberId, roleId: role.id, roleKey: role.key });
  },
);

workspaceRoutes.delete(
  "/:workspaceId/members/:memberId",
  requirePermission("member:manage"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const memberId = context.req.param("memberId");
    const db = getDb(context.env);

    const targetRows = await db
      .select({ member: workspaceMembers, roleKey: roles.key })
      .from(workspaceMembers)
      .innerJoin(roles, eq(workspaceMembers.roleId, roles.id))
      .where(
        and(
          eq(workspaceMembers.id, memberId),
          eq(workspaceMembers.workspaceId, access.workspace.id),
        ),
      )
      .limit(1);

    const target = targetRows[0];
    if (!target) {
      throw notFound("Member not found in this workspace.");
    }

    if (target.roleKey === "owner") {
      throw forbidden("The workspace owner cannot be removed.");
    }

    await db
      .delete(workspaceMembers)
      .where(eq(workspaceMembers.id, memberId));

    await writeAudit(context.env, {
      workspaceId: access.workspace.id,
      actorUserId: user.id,
      action: "member.removed",
      entityType: "workspace_member",
      entityId: memberId,
    });

    return ok(context, { removed: true });
  },
);

workspaceRoutes.get(
  "/:workspaceId/branches",
  requirePermission("branch:read"),
  async (context) => {
    const access = context.get("workspace");
    const db = getDb(context.env);
    const rows = await db
      .select()
      .from(branches)
      .where(eq(branches.workspaceId, access.workspace.id));

    return ok(context, { branches: rows });
  },
);

workspaceRoutes.post(
  "/:workspaceId/branches",
  requirePermission("branch:manage"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const data = await parseJson(context, createBranchSchema);
    const db = getDb(context.env);

    const duplicate = await db
      .select({ id: branches.id })
      .from(branches)
      .where(
        and(
          eq(branches.workspaceId, access.workspace.id),
          eq(branches.code, data.code),
        ),
      )
      .limit(1);

    if (duplicate.length > 0) {
      throw conflict("A branch with this code already exists.");
    }

    const usage = await getPlanUsage(context.env, access.workspace.id);
    await assertWithinLimit(
      context.env,
      access.workspace.id,
      "maxBranches",
      usage.branches,
    );

    const branchId = newId("brn");
    const inserted = await db
      .insert(branches)
      .values({
        id: branchId,
        workspaceId: access.workspace.id,
        name: data.name,
        code: data.code,
        phone: data.phone ?? null,
        address: data.address ?? null,
      })
      .returning();

    await writeAudit(context.env, {
      workspaceId: access.workspace.id,
      actorUserId: user.id,
      action: "branch.created",
      entityType: "branch",
      entityId: branchId,
      metadata: { name: data.name, code: data.code },
    });

    return created(context, { branch: inserted[0] });
  },
);

workspaceRoutes.patch(
  "/:workspaceId/branches/:branchId",
  requirePermission("branch:manage"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const branchId = context.req.param("branchId");
    const data = await parseJson(context, updateBranchSchema);
    const db = getDb(context.env);

    const existing = await db
      .select({ id: branches.id })
      .from(branches)
      .where(
        and(
          eq(branches.id, branchId),
          eq(branches.workspaceId, access.workspace.id),
        ),
      )
      .limit(1);

    if (existing.length === 0) {
      throw notFound("Branch not found.");
    }

    await db
      .update(branches)
      .set({
        ...(data.name ? { name: data.name } : {}),
        ...(data.phone !== undefined ? { phone: data.phone } : {}),
        ...(data.address !== undefined ? { address: data.address } : {}),
        ...(data.status ? { status: data.status } : {}),
        updatedAt: Date.now(),
      })
      .where(eq(branches.id, branchId));

    await writeAudit(context.env, {
      workspaceId: access.workspace.id,
      actorUserId: user.id,
      action: "branch.updated",
      entityType: "branch",
      entityId: branchId,
      metadata: data,
    });

    const updated = await db
      .select()
      .from(branches)
      .where(eq(branches.id, branchId))
      .limit(1);

    return ok(context, { branch: updated[0] });
  },
);

workspaceRoutes.delete(
  "/:workspaceId/branches/:branchId",
  requirePermission("branch:manage"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const branchId = context.req.param("branchId");
    const db = getDb(context.env);

    const rows = await db
      .select()
      .from(branches)
      .where(
        and(
          eq(branches.id, branchId),
          eq(branches.workspaceId, access.workspace.id),
        ),
      )
      .limit(1);
    const branch = rows[0];
    if (!branch) throw notFound("Branch not found.");
    if (branch.isDefault) {
      throw badRequest("The default branch cannot be deactivated.");
    }
    if (branch.status === "inactive") {
      throw conflict("This branch is already inactive.");
    }

    await db
      .update(branches)
      .set({ status: "inactive", updatedAt: Date.now() })
      .where(eq(branches.id, branchId));

    await writeAudit(context.env, {
      workspaceId: access.workspace.id,
      actorUserId: user.id,
      action: "branch.deactivated",
      entityType: "branch",
      entityId: branchId,
      metadata: { name: branch.name, code: branch.code },
    });

    return ok(context, { deactivated: true });
  },
);
