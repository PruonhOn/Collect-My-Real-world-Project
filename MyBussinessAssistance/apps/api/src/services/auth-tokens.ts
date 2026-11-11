import { and, eq, gt, isNull } from "drizzle-orm";
import { authTokens } from "@mba/db";
import type { Env } from "../env";
import { getDb } from "../db";
import { newId } from "../lib/ids";
import { randomToken, sha256Hex } from "../lib/crypto";

export type AuthTokenType =
  | "email_verification"
  | "password_reset"
  | "workspace_invitation";

export const AUTH_TOKEN_TTL_MS: Record<AuthTokenType, number> = {
  email_verification: 24 * 60 * 60 * 1_000,
  password_reset: 60 * 60 * 1_000,
  workspace_invitation: 7 * 24 * 60 * 60 * 1_000,
};

export async function issueAuthToken(
  env: Env,
  userId: string,
  type: AuthTokenType,
  ttlMs = AUTH_TOKEN_TTL_MS[type],
) {
  const db = getDb(env);
  const token = randomToken(32);
  const tokenHash = await sha256Hex(token);

  await db.insert(authTokens).values({
    id: newId("tok"),
    userId,
    type,
    tokenHash,
    expiresAt: Date.now() + ttlMs,
  });

  return token;
}

export async function consumeAuthToken(
  env: Env,
  type: AuthTokenType,
  rawToken: string,
) {
  const db = getDb(env);
  const tokenHash = await sha256Hex(rawToken);

  const now = Date.now();
  const consumed = await db
    .update(authTokens)
    .set({ usedAt: now })
    .where(
      and(
        eq(authTokens.tokenHash, tokenHash),
        eq(authTokens.type, type),
        gt(authTokens.expiresAt, now),
        isNull(authTokens.usedAt),
      ),
    )
    .returning({ userId: authTokens.userId });

  const token = consumed[0];
  if (!token) {
    return null;
  }

  return { userId: token.userId };
}
