import type { Context } from "hono";
import type { ContentfulStatusCode } from "hono/utils/http-status";

type Meta = Record<string, unknown>;

export function ok<T>(
  context: Context,
  data: T,
  meta?: Meta,
  status: ContentfulStatusCode = 200,
) {
  return context.json({ ok: true, data, ...(meta ? { meta } : {}) }, status);
}

export function created<T>(context: Context, data: T, meta?: Meta) {
  return ok(context, data, meta, 201);
}

export function fail(
  context: Context,
  status: ContentfulStatusCode,
  code: string,
  message: string,
  details?: unknown,
) {
  return context.json(
    { ok: false, error: { code, message, ...(details ? { details } : {}) } },
    status,
  );
}
