import { createMiddleware } from "hono/factory";
import { eq } from "drizzle-orm";
import { rateLimits } from "@mba/db";
import type { AppEnv } from "../env";
import { getDb } from "../db";
import { ApiError } from "../lib/errors";

type RateLimitOptions = {
  prefix: string;
  limit: number;
  windowMs: number;
};

export function rateLimit({ prefix, limit, windowMs }: RateLimitOptions) {
  return createMiddleware<AppEnv>(async (context, next) => {
    const db = getDb(context.env);
    const ip =
      context.req.header("cf-connecting-ip") ??
      context.req.header("x-forwarded-for")?.split(",")[0]?.trim() ??
      "unknown";
    const key = `${prefix}:${ip}`;
    const now = Date.now();

    try {
      const rows = await db
        .select()
        .from(rateLimits)
        .where(eq(rateLimits.key, key))
        .limit(1);
      const row = rows[0];

      if (!row || row.resetAt <= now) {
        await db
          .insert(rateLimits)
          .values({ key, count: 1, resetAt: now + windowMs })
          .onConflictDoUpdate({
            target: rateLimits.key,
            set: { count: 1, resetAt: now + windowMs },
          });
        await next();
        return;
      }

      if (row.count >= limit) {
        throw new ApiError(
          429,
          "rate_limited",
          "Too many attempts. Please try again later.",
        );
      }

      await db
        .update(rateLimits)
        .set({ count: row.count + 1 })
        .where(eq(rateLimits.key, key));
      await next();
    } catch (error) {
      if (error instanceof ApiError) {
        throw error;
      }
      console.error("Rate limiter failed open", error);
      await next();
    }
  });
}
