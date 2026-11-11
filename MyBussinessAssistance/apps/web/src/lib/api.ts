import type { ApiResponse, HealthPayload } from "@mba/shared";

export class ApiClientError extends Error {
  readonly code: string;
  readonly status: number;

  constructor(message: string, code: string, status: number) {
    super(message);
    this.name = "ApiClientError";
    this.code = code;
    this.status = status;
  }
}

export async function apiRequest<T>(
  path: string,
  init?: RequestInit,
): Promise<T> {
  const response = await fetch(`/api/v1${path}`, {
    credentials: "include",
    ...init,
    headers: {
      "Content-Type": "application/json",
      ...(init?.headers ?? {}),
    },
  });

  let payload: ApiResponse<T> | null = null;

  try {
    payload = (await response.json()) as ApiResponse<T>;
  } catch {
    throw new ApiClientError(
      "The server returned an invalid response.",
      "invalid_response",
      response.status,
    );
  }

  if (!payload || payload.ok !== true) {
    throw new ApiClientError(
      payload && payload.ok === false
        ? payload.error.message
        : "Request failed.",
      payload && payload.ok === false ? payload.error.code : "request_failed",
      response.status,
    );
  }

  return payload.data;
}

export function fetchHealth() {
  return apiRequest<HealthPayload>("/health");
}
