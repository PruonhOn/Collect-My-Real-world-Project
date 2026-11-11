import type { ErrorHandler, NotFoundHandler } from "hono";
import { HTTPException } from "hono/http-exception";
import type { AppEnv } from "../env";
import { ApiError } from "../lib/errors";
import { fail } from "../lib/response";

export const errorHandler: ErrorHandler<AppEnv> = (err, context) => {
  if (err instanceof ApiError) {
    return fail(context, err.status, err.code, err.message, err.details);
  }

  if (err instanceof HTTPException) {
    return fail(context, err.status, "http_error", err.message);
  }

  console.error("Unhandled API error", err instanceof Error ? err.message : err);
  return fail(
    context,
    500,
    "internal_error",
    "Something went wrong. Please try again.",
  );
};

export const notFoundHandler: NotFoundHandler<AppEnv> = async (context) => {
  if (context.req.path.startsWith("/api")) {
    return fail(context, 404, "not_found", "API route not found");
  }

  if (context.env.ASSETS) {
    return context.env.ASSETS.fetch(context.req.raw);
  }

  return context.text("Not found", 404);
};
