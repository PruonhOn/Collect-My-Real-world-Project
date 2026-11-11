import type { Context } from "hono";
import { deleteCookie, setCookie } from "hono/cookie";
import { and, eq, gt, isNull } from "drizzle-orm";
import { sessions, users } from "@mba/db";
import type { AuthUser, Env } from "../env";
import { getDb } from "../db";
import { newId } from "../lib/ids";
import { randomToken, sha256Hex } from "../lib/crypto";

export const SESSION_COOKIE = "mba_session";
const SESSION_TTL_MS = 30 * 24 * 60 * 60 * 1_000;

type SessionMeta = {
  ip?: string;
  userAgent?: string;
};

type UserRow = typeof users.$inferSelect;

export function toAuthUser(user: UserRow): AuthUser {
  return {
    id: user.id,
    email: user.email,
    name: user.name,
    locale: user.locale,
    status: user.status,
    emailVerifiedAt: user.emailVerifiedAt,
  };
}

export async function createSession(
  env: Env,
  userId: string,
  meta: SessionMeta = {},
) {
  const db = getDb(env);
  const token = randomToken(32);
  const tokenHash = await sha256Hex(token);
  const sessionId = newId("sess");
  const expiresAt = Date.now() + SESSION_TTL_MS;

  await db.insert(sessions).values({
    id: sessionId,
    userId,
    tokenHash,
    expiresAt,
    ip: meta.ip ?? null,
    userAgent: meta.userAgent ?? null,
  });

  return { token, expiresAt, sessionId };
}

export async function resolveSession(env: Env, token: string) {
  const db = getDb(env);
  const tokenHash = await sha256Hex(token);

  const rows = await db
    .select({ session: sessions, user: users })
    .from(sessions)
    .innerJoin(users, eq(sessions.userId, users.id))
    .where(
      and(
        eq(sessions.tokenHash, tokenHash),
        gt(sessions.expiresAt, Date.now()),
        isNull(sessions.revokedAt),
        eq(users.status, "active"),
      ),
    )
    .limit(1);

  const row = rows[0];
  if (!row) {
    return null;
  }

  return {
    sessionId: row.session.id,
    user: toAuthUser(row.user),
  };
}

export async function revokeSession(env: Env, sessionId: string) {
  const db = getDb(env);
  await db
    .update(sessions)
    .set({ revokedAt: Date.now() })
    .where(eq(sessions.id, sessionId));
}

export async function revokeAllUserSessions(env: Env, userId: string) {
  const db = getDb(env);
  await db
    .update(sessions)
    .set({ revokedAt: Date.now() })
    .where(and(eq(sessions.userId, userId), isNull(sessions.revokedAt)));
}

export function setSessionCookie(
  context: Context,
  env: Env,
  token: string,
  expiresAt: number,
) {
  setCookie(context, SESSION_COOKIE, token, {
    httpOnly: true,
    secure: env.ENVIRONMENT === "production",
    sameSite: "Lax",
    path: "/",
    maxAge: Math.max(1, Math.floor((expiresAt - Date.now()) / 1_000)),
  });
}

export function clearSessionCookie(context: Context, env: Env) {
  deleteCookie(context, SESSION_COOKIE, {
    path: "/",
    secure: env.ENVIRONMENT === "production",
    sameSite: "Lax",
  });
}
