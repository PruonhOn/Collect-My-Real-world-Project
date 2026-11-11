import { Hono } from "hono";
import { deleteCookie, getCookie, setCookie } from "hono/cookie";
import { eq } from "drizzle-orm";
import { users } from "@mba/db";
import type { AppEnv, Env } from "../env";
import { getDb } from "../db";
import { ApiError, isUniqueViolation } from "../lib/errors";
import { newId } from "../lib/ids";
import { randomToken } from "../lib/crypto";
import {
  createSession,
  setSessionCookie,
} from "../services/auth";
import { writeAudit } from "../services/audit";
import { requestMeta } from "./auth";

export const googleAuthRoutes = new Hono<AppEnv>();

const STATE_COOKIE = "mba_google_state";
const STATE_PATH = "/api/v1/auth/google";
const STATE_TTL_SECONDS = 10 * 60;

function googleConfig(env: Env) {
  const clientId = env.GOOGLE_CLIENT_ID;
  const clientSecret = env.GOOGLE_CLIENT_SECRET;

  if (!clientId || !clientSecret) {
    return null;
  }

  return {
    clientId,
    clientSecret,
    redirectUri: `${env.APP_URL}${STATE_PATH}/callback`,
  };
}

function failureRedirect(env: Env) {
  return `${env.APP_URL}/login?error=google`;
}

googleAuthRoutes.get("/google", (context) => {
  const config = googleConfig(context.env);

  if (!config) {
    throw new ApiError(
      503,
      "google_not_configured",
      "Google sign-in is not configured.",
    );
  }

  const state = randomToken(24);
  setCookie(context, STATE_COOKIE, state, {
    httpOnly: true,
    secure: context.env.ENVIRONMENT === "production",
    sameSite: "Lax",
    path: STATE_PATH,
    maxAge: STATE_TTL_SECONDS,
  });

  const url = new URL("https://accounts.google.com/o/oauth2/v2/auth");
  url.searchParams.set("client_id", config.clientId);
  url.searchParams.set("redirect_uri", config.redirectUri);
  url.searchParams.set("response_type", "code");
  url.searchParams.set("scope", "openid email profile");
  url.searchParams.set("state", state);
  url.searchParams.set("prompt", "select_account");

  return context.redirect(url.toString(), 302);
});

googleAuthRoutes.get("/google/callback", async (context) => {
  const env = context.env;
  const fail = () => context.redirect(failureRedirect(env), 302);

  try {
    const code = context.req.query("code");
    const state = context.req.query("state");
    const savedState = getCookie(context, STATE_COOKIE);
    deleteCookie(context, STATE_COOKIE, {
      path: STATE_PATH,
      secure: env.ENVIRONMENT === "production",
      sameSite: "Lax",
    });

    if (context.req.query("error")) {
      return fail();
    }

    if (!code || !state || !savedState || savedState !== state) {
      return fail();
    }

    const config = googleConfig(env);
    if (!config) {
      return fail();
    }

    const tokenResponse = await fetch("https://oauth2.googleapis.com/token", {
      method: "POST",
      headers: { "content-type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({
        code,
        client_id: config.clientId,
        client_secret: config.clientSecret,
        redirect_uri: config.redirectUri,
        grant_type: "authorization_code",
      }),
    });

    if (!tokenResponse.ok) {
      console.error(
        "Google token exchange failed",
        tokenResponse.status,
        await tokenResponse.text(),
      );
      return fail();
    }

    const tokens = (await tokenResponse.json()) as { access_token?: string };
    if (!tokens.access_token) {
      return fail();
    }

    const profileResponse = await fetch(
      "https://openidconnect.googleapis.com/v1/userinfo",
      { headers: { authorization: `Bearer ${tokens.access_token}` } },
    );

    if (!profileResponse.ok) {
      console.error(
        "Google userinfo failed",
        profileResponse.status,
        await profileResponse.text(),
      );
      return fail();
    }

    const profile = (await profileResponse.json()) as {
      sub?: string;
      email?: string;
      email_verified?: boolean;
      name?: string;
      locale?: string;
    };

    const email = profile.email?.trim().toLowerCase();
    if (!profile.sub || !email || profile.email_verified === false) {
      return fail();
    }

    const db = getDb(env);
    const meta = requestMeta(context);
    const now = Date.now();

    const byGoogleId = profile.sub
      ? await db
          .select()
          .from(users)
          .where(eq(users.googleId, profile.sub))
          .limit(1)
      : [];

    let user = byGoogleId[0];

    if (!user) {
      const byEmail = await db
        .select()
        .from(users)
        .where(eq(users.email, email))
        .limit(1);

      user = byEmail[0];

      if (user) {
        const link: { googleId: string; emailVerifiedAt?: number } = {
          googleId: profile.sub,
        };
        if (!user.emailVerifiedAt) {
          link.emailVerifiedAt = now;
        }

        await db
          .update(users)
          .set({ ...link, updatedAt: now })
          .where(eq(users.id, user.id));

        user = { ...user, ...link };
      }
    }

    if (!user) {
      const userId = newId("usr");
      const name =
        profile.name?.trim() || email.split("@")[0] || "Google user";
      const locale = profile.locale?.toLowerCase().startsWith("km")
        ? "km"
        : "en";

      let inserted;
      try {
        inserted = await db
          .insert(users)
          .values({
            id: userId,
            email,
            passwordHash: `!google!${randomToken(32)}`,
            googleId: profile.sub,
            name,
            locale,
            emailVerifiedAt: now,
            lastLoginAt: now,
          })
          .returning();
      } catch (error) {
        if (isUniqueViolation(error)) {
          return fail();
        }
        throw error;
      }

      user = inserted[0];

      await writeAudit(env, {
        actorUserId: user.id,
        action: "auth.google_registered",
        entityType: "user",
        entityId: user.id,
        metadata: { provider: "google" },
        ...meta,
      });
    } else {
      if (user.status !== "active") {
        return fail();
      }

      await db
        .update(users)
        .set({ lastLoginAt: now })
        .where(eq(users.id, user.id));

      await writeAudit(env, {
        actorUserId: user.id,
        action: "auth.google_login",
        entityType: "user",
        entityId: user.id,
        metadata: { provider: "google" },
        ...meta,
      });
    }

    const session = await createSession(env, user.id, meta);
    setSessionCookie(context, env, session.token, session.expiresAt);

    return context.redirect(`${env.APP_URL}/app`, 302);
  } catch (error) {
    console.error(
      "Google sign-in failed",
      error instanceof Error ? error.message : error,
    );
    return fail();
  }
});
