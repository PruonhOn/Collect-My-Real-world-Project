import type { Context } from "hono";
import type { ZodType } from "zod";
import { ApiError } from "./errors";

export async function parseJson<T>(context: Context, schema: ZodType<T>) {
  let raw: unknown;

  try {
    raw = await context.req.json();
  } catch {
    throw new ApiError(
      400,
      "invalid_json",
      "Request body must be valid JSON.",
    );
  }

  const result = schema.safeParse(raw);
  if (!result.success) {
    throw new ApiError(
      400,
      "validation_error",
      "Please check the submitted fields.",
      result.error.flatten(),
    );
  }

  return result.data;
}

export function parseQuery<T>(context: Context, schema: ZodType<T>) {
  const raw = Object.fromEntries(
    new URL(context.req.url).searchParams.entries(),
  );
  const result = schema.safeParse(raw);
  if (!result.success) {
    throw new ApiError(
      400,
      "validation_error",
      "Invalid query parameters.",
      result.error.flatten(),
    );
  }
  return result.data;
}
