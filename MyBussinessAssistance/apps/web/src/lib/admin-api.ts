import { apiRequest } from "./api";

export type AdminOverview = {
  workspaces: number;
  users: number;
  products: number;
  payingWorkspaces: number;
  estimatedMonthlyRevenueCents: number;
  subscriptionsByPlan: {
    planId: string;
    planName: string;
    planSlug: string;
    count: number;
  }[];
};

export type AdminWorkspace = {
  id: string;
  name: string;
  slug: string;
  status: string;
  defaultCurrency: string;
  createdAt: number;
  ownerEmail: string | null;
  ownerName: string | null;
  planId: string | null;
  planName: string | null;
  planSlug: string | null;
  subscriptionStatus: string | null;
  trialEndsAt: number | null;
  currentPeriodEnd: number | null;
  cancelAtPeriodEnd: boolean | null;
  memberCount: number;
  productCount: number;
};

export type AdminPlanEntitlement = { key: string; value: string };

export type AdminPlan = {
  id: string;
  name: string;
  slug: string;
  description: string | null;
  monthlyPriceCents: number;
  yearlyPriceCents: number;
  currency: string;
  trialDays: number;
  isActive: boolean;
  sortOrder: number;
  subscriberCount: number;
  entitlements: AdminPlanEntitlement[];
};

export function fetchAdminOverview() {
  return apiRequest<AdminOverview>("/admin/overview");
}

export function fetchAdminWorkspaces(search?: string) {
  const query = search ? `?search=${encodeURIComponent(search)}` : "";
  return apiRequest<{ workspaces: AdminWorkspace[] }>(
    `/admin/workspaces${query}`,
  );
}

export function updateAdminWorkspaceStatus(
  workspaceId: string,
  status: "active" | "suspended",
) {
  return apiRequest<{ workspaceId: string; status: string }>(
    `/admin/workspaces/${workspaceId}`,
    { method: "PATCH", body: JSON.stringify({ status }) },
  );
}

export function fetchAdminPlans() {
  return apiRequest<{ plans: AdminPlan[] }>("/admin/plans");
}

export function updateAdminPlan(
  planId: string,
  patch: {
    name?: string;
    description?: string | null;
    monthlyPriceCents?: number;
    yearlyPriceCents?: number;
    isActive?: boolean;
  },
) {
  return apiRequest<{ plan: AdminPlan }>(`/admin/plans/${planId}`, {
    method: "PATCH",
    body: JSON.stringify(patch),
  });
}

export function updateAdminEntitlement(
  planId: string,
  key: string,
  value: string | null,
) {
  return apiRequest<{ planId: string; key: string; value: string | null }>(
    `/admin/plans/${planId}/entitlements`,
    { method: "PUT", body: JSON.stringify({ key, value }) },
  );
}
