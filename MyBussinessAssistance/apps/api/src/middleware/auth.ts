import { getCookie } from "hono/cookie";
import { createMiddleware } from "hono/factory";
import type { AppEnv } from "../env";
import { ApiError, unauthorized } from "../lib/errors";
import {
  clearSessionCookie,
  resolveSession,
  SESSION_COOKIE,
} from "../services/auth";

export const requireAuth = createMiddleware<AppEnv>(async (context, next) => {
  const token = getCookie(context, SESSION_COOKIE);

  if (!token) {
    throw unauthorized();
  }

  const session = await resolveSession(context.env, token);
  if (!session) {
    clearSessionCookie(context, context.env);
    throw unauthorized("Your session has expired. Please sign in again.");
  }

  context.set("user", session.user);
  context.set("sessionId", session.sessionId);
  await next();
});

export const requireVerifiedEmail = createMiddleware<AppEnv>(
  async (context, next) => {
    const user = context.get("user");

    if (!user?.emailVerifiedAt) {
      throw new ApiError(
        403,
        "email_verification_required",
        "Please verify your email address before continuing.",
      );
    }

    await next();
  },
);
