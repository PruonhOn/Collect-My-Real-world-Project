import { apiRequest } from "./api";

export type AuthUser = {
  id: string;
  email: string;
  name: string;
  locale: string;
  status: string;
  emailVerifiedAt: number | null;
};

export type WorkspaceSummary = {
  memberId: string;
  workspaceId: string;
  name: string;
  slug: string;
  defaultCurrency: string;
  locale: string;
  status: string;
  roleId: string;
  roleKey: string;
  roleName: string;
  planName: string | null;
  planSlug: string | null;
  planModules: string[] | null;
  subscriptionStatus: string | null;
  trialEndsAt: number | null;
  permissions: string[];
};

export type MeResponse = {
  user: AuthUser;
  workspaces: WorkspaceSummary[];
  platformRole: string | null;
};

export type Branch = {
  id: string;
  workspaceId: string;
  name: string;
  code: string;
  phone: string | null;
  address: string | null;
  isDefault: boolean;
  status: string;
  createdAt: number;
  updatedAt: number;
};

export type WorkspaceSettings = {
  workspaceId: string;
  taxEnabled: boolean;
  taxRateBasisPoints: number;
  invoicePrefix: string;
  exchangeRateKhrPerUsd: number;
  lowStockThresholdDefault: number;
  updatedAt: number;
};

export type WorkspaceDetail = {
  workspace: {
    id: string;
    name: string;
    slug: string;
    ownerUserId: string;
    defaultCurrency: string;
    locale: string;
    timezone: string;
    status: string;
    logoKey: string | null;
    createdAt: number;
    updatedAt: number;
  };
  roleKey: string;
  permissions: string[];
  branches: Branch[];
  settings: WorkspaceSettings | null;
  subscription: {
    id: string;
    status: string;
    billingCycle: string;
    currentPeriodEnd: number | null;
    trialEndsAt: number | null;
    graceEndsAt: number | null;
    cancelAtPeriodEnd: boolean;
    planSlug: string;
    planName: string;
  } | null;
};

export type MemberRow = {
  memberId: string;
  userId: string;
  name: string;
  email: string;
  status: string;
  roleId: string;
  roleKey: string;
  roleName: string;
  joinedAt: number | null;
};

export type PendingInvitation = {
  id: string;
  email: string;
  status: string;
  roleId: string;
  roleName: string;
  expiresAt: number;
  createdAt: number;
};

export type RoleOption = {
  id: string;
  key: string;
  name: string;
  isSystem: boolean;
};

export type MembersResponse = {
  members: MemberRow[];
  invitations: PendingInvitation[];
  roles: RoleOption[];
};

export type InvitationPreview = {
  email: string;
  workspaceName: string;
  workspaceSlug: string;
  roleName: string;
  roleKey: string;
  expiresAt: number;
};

export type RegisterInput = {
  name: string;
  email: string;
  password: string;
  phone?: string;
  locale: "en" | "km";
};

export function register(input: RegisterInput) {
  return apiRequest<{
    user: AuthUser;
    emailSent: boolean;
    devVerificationToken?: string;
  }>("/auth/register", { method: "POST", body: JSON.stringify(input) });
}

export function login(email: string, password: string) {
  return apiRequest<{ user: AuthUser }>("/auth/login", {
    method: "POST",
    body: JSON.stringify({ email, password }),
  });
}

export function logout() {
  return apiRequest<{ loggedOut: boolean }>("/auth/logout", {
    method: "POST",
  });
}

export function fetchMe() {
  return apiRequest<MeResponse>("/auth/me");
}

export function verifyEmail(token: string) {
  return apiRequest<{ verified: boolean }>("/auth/verify-email", {
    method: "POST",
    body: JSON.stringify({ token }),
  });
}

export function resendVerification() {
  return apiRequest<{
    verificationRequired: boolean;
    emailSent: boolean;
    devVerificationToken?: string;
  }>("/auth/resend-verification", { method: "POST" });
}

export function forgotPassword(email: string) {
  return apiRequest<{
    message: string;
    emailSent: boolean;
    devResetToken?: string;
  }>("/auth/forgot-password", { method: "POST", body: JSON.stringify({ email }) });
}

export function resetPassword(token: string, password: string) {
  return apiRequest<{ reset: boolean }>("/auth/reset-password", {
    method: "POST",
    body: JSON.stringify({ token, password }),
  });
}

export function fetchWorkspaces() {
  return apiRequest<{ workspaces: WorkspaceSummary[] }>("/workspaces");
}

export function fetchWorkspace(workspaceId: string) {
  return apiRequest<WorkspaceDetail>(`/workspaces/${workspaceId}`);
}

export function createWorkspace(input: {
  name: string;
  defaultCurrency: "USD" | "KHR";
  locale: "en" | "km";
}) {
  return apiRequest<{
    workspace: {
      id: string;
      name: string;
      slug: string;
      defaultCurrency: string;
      locale: string;
    };
    branchId: string;
    subscription: {
      id: string;
      plan: string;
      status: string;
      trialEndsAt: number | null;
      currentPeriodEnd: number;
    };
  }>("/workspaces", { method: "POST", body: JSON.stringify(input) });
}

export function updateWorkspace(
  workspaceId: string,
  input: {
    name?: string;
    defaultCurrency?: "USD" | "KHR";
    locale?: "en" | "km";
    taxEnabled?: boolean;
    taxRateBasisPoints?: number;
    invoicePrefix?: string;
    exchangeRateKhrPerUsd?: number;
  },
) {
  return apiRequest<{ workspace: WorkspaceDetail["workspace"] }>(
    `/workspaces/${workspaceId}`,
    { method: "PATCH", body: JSON.stringify(input) },
  );
}

export function fetchMembers(workspaceId: string) {
  return apiRequest<MembersResponse>(`/workspaces/${workspaceId}/members`);
}

export function inviteMember(
  workspaceId: string,
  input: { email: string; roleId: string },
) {
  return apiRequest<{
    invitation: PendingInvitation;
    emailSent: boolean;
    devInvitationToken?: string;
  }>(`/workspaces/${workspaceId}/invitations`, {
    method: "POST",
    body: JSON.stringify(input),
  });
}

export function updateMemberRole(
  workspaceId: string,
  memberId: string,
  roleId: string,
) {
  return apiRequest<{ memberId: string; roleId: string; roleKey: string }>(
    `/workspaces/${workspaceId}/members/${memberId}`,
    { method: "PATCH", body: JSON.stringify({ roleId }) },
  );
}

export function removeMember(workspaceId: string, memberId: string) {
  return apiRequest<{ removed: boolean }>(
    `/workspaces/${workspaceId}/members/${memberId}`,
    { method: "DELETE" },
  );
}

export function createBranch(
  workspaceId: string,
  input: { name: string; code: string; phone?: string; address?: string },
) {
  return apiRequest<{ branch: Branch }>(`/workspaces/${workspaceId}/branches`, {
    method: "POST",
    body: JSON.stringify(input),
  });
}

export function updateBranch(
  workspaceId: string,
  branchId: string,
  input: {
    name?: string;
    phone?: string;
    address?: string;
    status?: "active" | "inactive";
  },
) {
  return apiRequest<{ branch: Branch }>(
    `/workspaces/${workspaceId}/branches/${branchId}`,
    { method: "PATCH", body: JSON.stringify(input) },
  );
}

export function deactivateBranch(workspaceId: string, branchId: string) {
  return apiRequest<{ deactivated: boolean }>(
    `/workspaces/${workspaceId}/branches/${branchId}`,
    { method: "DELETE" },
  );
}

export function fetchInvitation(token: string) {
  return apiRequest<{ invitation: InvitationPreview }>(
    `/invitations/${encodeURIComponent(token)}`,
  );
}

export function acceptInvitation(token: string) {
  return apiRequest<{
    workspaceId: string;
    workspaceName: string;
    roleKey: string;
  }>("/invitations/accept", {
    method: "POST",
    body: JSON.stringify({ token }),
  });
}
