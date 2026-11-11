import type { ContentfulStatusCode } from "hono/utils/http-status";

export class ApiError extends Error {
  readonly status: ContentfulStatusCode;
  readonly code: string;
  readonly details?: unknown;

  constructor(
    status: ContentfulStatusCode,
    code: string,
    message: string,
    details?: unknown,
  ) {
    super(message);
    this.name = "ApiError";
    this.status = status;
    this.code = code;
    this.details = details;
  }
}

export function isUniqueViolation(error: unknown): boolean {
  const message =
    error instanceof Error ? error.message : String(error ?? "");
  return /unique constraint failed/i.test(message);
}

export const badRequest = (message: string, details?: unknown) =>
  new ApiError(400, "bad_request", message, details);

export const unauthorized = (message = "Authentication required") =>
  new ApiError(401, "unauthorized", message);

export const forbidden = (message = "You do not have permission to do this") =>
  new ApiError(403, "forbidden", message);

export const notFound = (message = "Resource not found") =>
  new ApiError(404, "not_found", message);

export const conflict = (message: string) =>
  new ApiError(409, "conflict", message);

export const tooManyRequests = (message = "Too many requests") =>
  new ApiError(429, "rate_limited", message);
