import { Hono } from "hono";
import { eq } from "drizzle-orm";
import {
  platformAdmins,
  users,
} from "@mba/db";
import {
  forgotPasswordSchema,
  loginSchema,
  registerSchema,
  resetPasswordSchema,
  verifyEmailSchema,
} from "@mba/shared";
import type { AppEnv } from "../env";
import { getDb } from "../db";
import { ApiError, conflict, isUniqueViolation, unauthorized } from "../lib/errors";
import { created, ok } from "../lib/response";
import { hashPassword, verifyPassword } from "../lib/crypto";
import { newId } from "../lib/ids";
import { parseJson } from "../lib/validate";
import { requireAuth } from "../middleware/auth";
import { rateLimit } from "../middleware/rate-limit";
import {
  clearSessionCookie,
  createSession,
  revokeAllUserSessions,
  revokeSession,
  setSessionCookie,
  toAuthUser,
} from "../services/auth";
import {
  consumeAuthToken,
  issueAuthToken,
} from "../services/auth-tokens";
import {
  sendPasswordResetEmail,
  sendVerificationEmail,
} from "../services/email-templates";
import { writeAudit } from "../services/audit";
import { listWorkspaceSummaries } from "../services/workspace-summary";

export const authRoutes = new Hono<AppEnv>();

export function requestMeta(context: {
  req: { header: (name: string) => string | undefined };
}) {
  return {
    ip:
      context.req.header("cf-connecting-ip") ??
      context.req.header("x-forwarded-for"),
    userAgent: context.req.header("user-agent"),
  };
}

authRoutes.post(
  "/register",
  rateLimit({ prefix: "register", limit: 20, windowMs: 60 * 60 * 1_000 }),
  async (context) => {
  const data = await parseJson(context, registerSchema);
  const db = getDb(context.env);

  const existing = await db
    .select({ id: users.id })
    .from(users)
    .where(eq(users.email, data.email))
    .limit(1);

  if (existing.length > 0) {
    throw conflict("An account with this email already exists.");
  }

  const userId = newId("usr");
  const passwordHash = await hashPassword(data.password);
  const meta = requestMeta(context);

  let inserted;
  try {
    inserted = await db
      .insert(users)
      .values({
        id: userId,
        email: data.email,
        passwordHash,
        name: data.name,
        phone: data.phone ?? null,
        locale: data.locale,
      })
      .returning();
  } catch (error) {
    if (isUniqueViolation(error)) {
      throw conflict("An account with this email already exists.");
    }
    throw error;
  }

  const user = inserted[0];
  const session = await createSession(context.env, userId, meta);
  setSessionCookie(context, context.env, session.token, session.expiresAt);

  const verificationToken = await issueAuthToken(
    context.env,
    userId,
    "email_verification",
  );

  let emailSent = false;
  try {
    const result = await sendVerificationEmail(context.env, {
      to: data.email,
      name: data.name,
      token: verificationToken,
    });
    emailSent = !result.skipped;
  } catch (error) {
    console.error("Unable to send verification email", error instanceof Error ? error.message : error);
  }

  await writeAudit(context.env, {
    actorUserId: userId,
    action: "auth.registered",
    entityType: "user",
    entityId: userId,
    ...meta,
  });

  return created(context, {
    user: toAuthUser(user),
    emailSent,
    devVerificationToken:
      context.env.ENVIRONMENT === "development" ? verificationToken : undefined,
  });
});

authRoutes.post(
  "/login",
  rateLimit({ prefix: "login", limit: 10, windowMs: 5 * 60 * 1_000 }),
  async (context) => {
  const data = await parseJson(context, loginSchema);
  const db = getDb(context.env);

  const rows = await db
    .select()
    .from(users)
    .where(eq(users.email, data.email))
    .limit(1);

  const user = rows[0];
  if (!user) {
    throw unauthorized("Invalid email or password.");
  }

  if (user.passwordHash.startsWith("!google!")) {
    throw unauthorized(
      "This account uses Google sign-in. Use “Continue with Google” instead.",
    );
  }

  const valid = await verifyPassword(data.password, user.passwordHash);
  if (!valid) {
    throw unauthorized("Invalid email or password.");
  }

  if (user.status !== "active") {
    throw new ApiError(
      403,
      "account_inactive",
      "This account is not active. Contact support.",
    );
  }

  const meta = requestMeta(context);
  const session = await createSession(context.env, user.id, meta);
  setSessionCookie(context, context.env, session.token, session.expiresAt);

  await db
    .update(users)
    .set({ lastLoginAt: Date.now() })
    .where(eq(users.id, user.id));

  await writeAudit(context.env, {
    actorUserId: user.id,
    action: "auth.login",
    entityType: "user",
    entityId: user.id,
    ...meta,
  });

  return ok(context, { user: toAuthUser(user) });
});

authRoutes.post("/logout", requireAuth, async (context) => {
  const sessionId = context.get("sessionId");
  const user = context.get("user");

  await revokeSession(context.env, sessionId);
  clearSessionCookie(context, context.env);

  await writeAudit(context.env, {
    actorUserId: user.id,
    action: "auth.logout",
    entityType: "user",
    entityId: user.id,
  });

  return ok(context, { loggedOut: true });
});

authRoutes.get("/me", requireAuth, async (context) => {
  const user = context.get("user");
  const db = getDb(context.env);

  const memberships = await listWorkspaceSummaries(db, user.id);

  const platformRows = await db
    .select({ role: platformAdmins.role })
    .from(platformAdmins)
    .where(eq(platformAdmins.userId, user.id))
    .limit(1);

  return ok(context, {
    user,
    workspaces: memberships,
    platformRole: platformRows[0]?.role ?? null,
  });
});

authRoutes.post(
  "/verify-email",
  rateLimit({ prefix: "verify-email", limit: 30, windowMs: 60 * 60 * 1_000 }),
  async (context) => {
  const data = await parseJson(context, verifyEmailSchema);
  const result = await consumeAuthToken(
    context.env,
    "email_verification",
    data.token,
  );

  if (!result) {
    throw new ApiError(
      400,
      "invalid_token",
      "This verification link is invalid or has expired.",
    );
  }

  const db = getDb(context.env);
  await db
    .update(users)
    .set({ emailVerifiedAt: Date.now() })
    .where(eq(users.id, result.userId));

  await writeAudit(context.env, {
    actorUserId: result.userId,
    action: "auth.email_verified",
    entityType: "user",
    entityId: result.userId,
  });

  return ok(context, { verified: true });
});

authRoutes.post(
  "/resend-verification",
  requireAuth,
  rateLimit({
    prefix: "resend-verification",
    limit: 10,
    windowMs: 60 * 60 * 1_000,
  }),
  async (context) => {
  const user = context.get("user");

  if (user.emailVerifiedAt) {
    return ok(context, { verificationRequired: false });
  }

  const token = await issueAuthToken(
    context.env,
    user.id,
    "email_verification",
  );

  let emailSent = false;
  try {
    const result = await sendVerificationEmail(context.env, {
      to: user.email,
      name: user.name,
      token,
    });
    emailSent = !result.skipped;
  } catch (error) {
    console.error("Unable to resend verification email", error instanceof Error ? error.message : error);
  }

  return ok(context, {
    verificationRequired: true,
    emailSent,
    devVerificationToken:
      context.env.ENVIRONMENT === "development" ? token : undefined,
  });
});

authRoutes.post(
  "/forgot-password",
  rateLimit({ prefix: "forgot-password", limit: 10, windowMs: 60 * 60 * 1_000 }),
  async (context) => {
  const data = await parseJson(context, forgotPasswordSchema);
  const db = getDb(context.env);

  const rows = await db
    .select({ id: users.id, name: users.name, email: users.email })
    .from(users)
    .where(eq(users.email, data.email))
    .limit(1);

  const user = rows[0];
  let devResetToken: string | undefined;
  let emailSent = false;

  if (user) {
    const token = await issueAuthToken(
      context.env,
      user.id,
      "password_reset",
    );

    if (context.env.ENVIRONMENT === "development") {
      devResetToken = token;
    }

    try {
      const result = await sendPasswordResetEmail(context.env, {
        to: user.email,
        name: user.name,
        token,
      });
      emailSent = !result.skipped;
    } catch (error) {
      console.error("Unable to send password reset email", error instanceof Error ? error.message : error);
    }

    await writeAudit(context.env, {
      actorUserId: user.id,
      action: "auth.password_reset_requested",
      entityType: "user",
      entityId: user.id,
    });
  }

  return ok(context, {
    message:
      "If an account exists for that email, a password reset link has been sent.",
    emailSent,
    devResetToken,
  });
});

authRoutes.post(
  "/reset-password",
  rateLimit({ prefix: "reset-password", limit: 10, windowMs: 60 * 60 * 1_000 }),
  async (context) => {
  const data = await parseJson(context, resetPasswordSchema);
  const result = await consumeAuthToken(
    context.env,
    "password_reset",
    data.token,
  );

  if (!result) {
    throw new ApiError(
      400,
      "invalid_token",
      "This reset link is invalid or has expired.",
    );
  }

  const passwordHash = await hashPassword(data.password);
  const db = getDb(context.env);

  await db
    .update(users)
    .set({ passwordHash, updatedAt: Date.now() })
    .where(eq(users.id, result.userId));

  await revokeAllUserSessions(context.env, result.userId);
  clearSessionCookie(context, context.env);

  await writeAudit(context.env, {
    actorUserId: result.userId,
    action: "auth.password_reset",
    entityType: "user",
    entityId: result.userId,
  });

  return ok(context, { reset: true });
});
