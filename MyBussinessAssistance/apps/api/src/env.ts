import type { Workspace } from "@mba/db";

export interface Env {
  DB: D1Database;
  ASSETS?: Fetcher;
  ENVIRONMENT: string;
  APP_URL: string;
  RESEND_API_KEY?: string;
  RESEND_FROM_EMAIL?: string;
  RESEND_FROM_NAME?: string;
  GOOGLE_CLIENT_ID?: string;
  GOOGLE_CLIENT_SECRET?: string;
  OPENAI_API_KEY?: string;
  OPENAI_MODEL?: string;
  OPENAI_BASE_URL?: string;
}

export type AuthUser = {
  id: string;
  email: string;
  name: string;
  locale: string;
  status: string;
  emailVerifiedAt: number | null;
};

export type WorkspaceAccess = {
  workspace: Workspace;
  roleId: string;
  roleKey: string;
  permissions: Set<string>;
};

export type AppEnv = {
  Bindings: Env;
  Variables: {
    user: AuthUser;
    sessionId: string;
    workspace: WorkspaceAccess;
    platformRole: string;
  };
};
