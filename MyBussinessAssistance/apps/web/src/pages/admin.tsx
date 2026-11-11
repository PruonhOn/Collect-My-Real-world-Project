import { useState } from "react";
import { Link } from "react-router-dom";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useTranslation } from "react-i18next";
import { ArrowLeft, Building2, Search, Users } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { StatusBadge } from "@/components/app/status-badge";
import { ConfirmDialog } from "@/components/app/confirm-dialog";
import { Skeleton } from "@/components/ui/skeleton";
import {
  fetchAdminOverview,
  fetchAdminPlans,
  fetchAdminWorkspaces,
  updateAdminEntitlement,
  updateAdminPlan,
  updateAdminWorkspaceStatus,
  type AdminWorkspace,
} from "@/lib/admin-api";
import { ApiClientError } from "@/lib/api";
import { formatDate } from "@/lib/format";
import { formatCents, formatNumber } from "@/lib/money";
import { useAuth } from "@/providers/auth-provider";

function errorMessage(error: unknown) {
  return error instanceof ApiClientError ? error.message : "Request failed.";
}

function AdminGuard({ children }: { children: React.ReactNode }) {
  const { user, loading, platformRole } = useAuth();

  if (loading) {
    return <div className="p-10 text-center text-sm text-muted-foreground">...</div>;
  }

  if (!user || !platformRole) {
    return (
      <div className="flex min-h-screen items-center justify-center p-6">
        <Card className="w-full max-w-md">
          <CardContent className="p-6 text-center text-sm text-muted-foreground">
            <p>Sign in as a platform administrator to view this page.</p>
            <Button asChild className="mt-4">
              <Link to="/login">Sign in</Link>
            </Button>
          </CardContent>
        </Card>
      </div>
    );
  }

  return <>{children}</>;
}

function OverviewCards() {
  const { t, i18n } = useTranslation();
  const locale = i18n.language.startsWith("km") ? "km" : "en";
  const { data, isLoading, isError } = useQuery({
    queryKey: ["admin-overview"],
    queryFn: fetchAdminOverview,
  });

  if (isError) {
    return (
      <Card>
        <CardContent className="p-6 text-center text-sm text-destructive">
          Unable to load platform overview.
        </CardContent>
      </Card>
    );
  }

  if (isLoading || !data) {
    return (
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {[0, 1, 2, 3].map((key) => (
          <Skeleton key={key} className="h-24" />
        ))}
      </div>
    );
  }

  const cards = [
    { label: t("admin.workspaces"), value: formatNumber(data.workspaces, locale) },
    { label: t("admin.users"), value: formatNumber(data.users, locale) },
    { label: t("admin.paying"), value: formatNumber(data.payingWorkspaces, locale) },
    {
      label: t("admin.mrr"),
      value: formatCents(data.estimatedMonthlyRevenueCents, "USD", locale),
    },
  ];

  return (
    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      {cards.map((card) => (
        <Card key={card.label}>
          <CardContent className="p-4">
            <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
              {card.label}
            </p>
            <p className="mt-1.5 text-xl font-bold">{card.value}</p>
          </CardContent>
        </Card>
      ))}
    </div>
  );
}

function WorkspaceRow({ workspace }: { workspace: AdminWorkspace }) {
  const { t } = useTranslation();
  const { platformRole } = useAuth();
  const canMutate = platformRole === "super_admin";
  const queryClient = useQueryClient();
  const [confirm, setConfirm] = useState(false);

  const mutation = useMutation({
    mutationFn: () =>
      updateAdminWorkspaceStatus(
        workspace.id,
        workspace.status === "active" ? "suspended" : "active",
      ),
    onSuccess: () =>
      queryClient.invalidateQueries({ queryKey: ["admin-workspaces"] }),
  });

  return (
    <>
      <tr>
        <td className="px-3 py-2.5">
          <p className="font-medium">{workspace.name}</p>
          <p className="text-xs text-muted-foreground">
            {workspace.ownerEmail ?? "—"} · {workspace.memberCount}{" "}
            {t("admin.members")} · {workspace.productCount} {t("admin.products")}
          </p>
        </td>
        <td className="px-3 py-2.5 text-sm">
          {workspace.planName ?? t("admin.noPlan")}
          {workspace.subscriptionStatus ? (
            <span className="ml-2">
              <StatusBadge status={workspace.subscriptionStatus} />
            </span>
          ) : null}
        </td>
        <td className="px-3 py-2.5 text-sm text-muted-foreground">
          {formatDate(workspace.createdAt)}
        </td>
        <td className="px-3 py-2.5 text-right">
          <Button
            variant="outline"
            size="sm"
            disabled={mutation.isPending || !canMutate}
            onClick={() => setConfirm(true)}
          >
            {workspace.status === "active"
              ? t("admin.suspend")
              : t("admin.activate")}
          </Button>
        </td>
      </tr>
      {mutation.isError ? (
        <tr>
          <td colSpan={4} className="px-3 pb-2 text-xs text-destructive">
            {errorMessage(mutation.error)}
          </td>
        </tr>
      ) : null}
      <ConfirmDialog
        open={confirm}
        onClose={() => setConfirm(false)}
        title={
          workspace.status === "active"
            ? t("admin.suspendTitle", { name: workspace.name })
            : t("admin.activateTitle", { name: workspace.name })
        }
        description={
          workspace.status === "active"
            ? t("admin.suspendBody")
            : t("admin.activateBody")
        }
        confirmLabel={
          workspace.status === "active"
            ? t("admin.suspend")
            : t("admin.activate")
        }
        cancelLabel={t("common.cancel")}
        destructive={workspace.status === "active"}
        pending={mutation.isPending}
        onConfirm={() =>
          mutation.mutate(undefined, { onSuccess: () => setConfirm(false) })
        }
      />
    </>
  );
}

function WorkspacesPanel() {
  const { t } = useTranslation();
  const [search, setSearch] = useState("");

  const { data, isLoading, isError } = useQuery({
    queryKey: ["admin-workspaces", search],
    queryFn: () => fetchAdminWorkspaces(search || undefined),
  });

  return (
    <div className="space-y-4">
      <div className="relative max-w-sm">
        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
        <Input
          className="pl-9"
          placeholder={t("admin.searchPlaceholder")}
          value={search}
          onChange={(event) => setSearch(event.target.value)}
        />
      </div>
      <Card>
        <CardContent className="p-0">
          {isLoading ? (
            <div className="space-y-2 p-4">
              {[0, 1, 2].map((key) => (
                <Skeleton key={key} className="h-12" />
              ))}
            </div>
          ) : isError ? (
            <p className="p-6 text-center text-sm text-destructive">
              Unable to load workspaces.
            </p>
          ) : (data?.workspaces.length ?? 0) === 0 ? (
            <p className="p-6 text-center text-sm text-muted-foreground">
              {t("admin.noWorkspaces")}
            </p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-left text-sm">
                <thead className="border-b bg-secondary/40 text-xs uppercase tracking-wide text-muted-foreground">
                  <tr>
                    <th className="px-3 py-2.5 font-medium">{t("admin.workspace")}</th>
                    <th className="px-3 py-2.5 font-medium">{t("admin.plan")}</th>
                    <th className="px-3 py-2.5 font-medium">{t("admin.created")}</th>
                    <th className="px-3 py-2.5" />
                  </tr>
                </thead>
                <tbody className="divide-y">
                  {data?.workspaces.map((workspace) => (
                    <WorkspaceRow key={workspace.id} workspace={workspace} />
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}

function PlansPanel() {
  const { t } = useTranslation();
  const { platformRole } = useAuth();
  const canMutate = platformRole === "super_admin";
  const queryClient = useQueryClient();
  const { data, isLoading } = useQuery({
    queryKey: ["admin-plans"],
    queryFn: fetchAdminPlans,
  });

  const planMutation = useMutation({
    mutationFn: ({ planId, patch }: { planId: string; patch: Parameters<typeof updateAdminPlan>[1] }) =>
      updateAdminPlan(planId, patch),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ["admin-plans"] }),
  });

  const entitlementMutation = useMutation({
    mutationFn: ({ planId, key, value }: { planId: string; key: string; value: string | null }) =>
      updateAdminEntitlement(planId, key, value),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ["admin-plans"] }),
  });

  if (isLoading) {
    return (
      <div className="space-y-4">
        {[0, 1, 2].map((key) => (
          <Skeleton key={key} className="h-40" />
        ))}
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {data?.plans.map((plan) => (
        <Card key={plan.id}>
          <CardContent className="space-y-4 p-5">
            <div className="flex flex-wrap items-center justify-between gap-3">
              <div>
                <div className="flex items-center gap-2">
                  <h3 className="font-semibold">{plan.name}</h3>
                  {!plan.isActive ? (
                    <Badge variant="secondary">{t("admin.inactive")}</Badge>
                  ) : null}
                </div>
                <p className="text-xs text-muted-foreground">
                  {plan.subscriberCount} {t("admin.subscribers")} ·{" "}
                  {formatCents(plan.monthlyPriceCents, plan.currency)}/
                  {t("business.subscription.month")} ·{" "}
                  {formatCents(plan.yearlyPriceCents, plan.currency)}/
                  {t("business.subscription.year")}
                </p>
              </div>
              <div className="flex flex-wrap gap-2">
                <Button
                  variant="outline"
                  size="sm"
                  disabled={planMutation.isPending || !canMutate}
                  onClick={() =>
                    planMutation.mutate({
                      planId: plan.id,
                      patch: { isActive: !plan.isActive },
                    })
                  }
                >
                  {plan.isActive ? t("admin.deactivate") : t("admin.activate")}
                </Button>
                <Button
                  variant="outline"
                  size="sm"
                  disabled={planMutation.isPending || !canMutate}
                  onClick={() =>
                    planMutation.mutate({
                      planId: plan.id,
                      patch: { monthlyPriceCents: plan.monthlyPriceCents + 500 },
                    })
                  }
                >
                  +$5 {t("admin.monthly")}
                </Button>
                <Button
                  variant="outline"
                  size="sm"
                  disabled={planMutation.isPending || !canMutate}
                  onClick={() =>
                    planMutation.mutate({
                      planId: plan.id,
                      patch: { yearlyPriceCents: plan.yearlyPriceCents + 5000 },
                    })
                  }
                >
                  +$50 {t("admin.yearly")}
                </Button>
              </div>
            </div>

            <div className="rounded-lg border bg-secondary/30 p-3">
              <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                {t("admin.entitlements")}
              </p>
              <div className="grid gap-1.5 sm:grid-cols-2">
                {plan.entitlements.map((entitlement) => (
                  <div
                    key={entitlement.key}
                    className="flex items-center justify-between gap-2 rounded-md border bg-card px-2.5 py-1.5 text-xs"
                  >
                    <code className="truncate text-muted-foreground">
                      {entitlement.key}
                    </code>
                    <span className="max-w-[45%] truncate font-mono font-medium">
                      {entitlement.value}
                    </span>
                  </div>
                ))}
              </div>
              <p className="mt-2 text-[11px] text-muted-foreground/70">
                {t("admin.entitlementNote")}
              </p>
            </div>

            {planMutation.error || entitlementMutation.error ? (
              <p className="text-sm text-destructive">
                {planMutation.error
                  ? errorMessage(planMutation.error)
                  : errorMessage(entitlementMutation.error)}
              </p>
            ) : null}
          </CardContent>
        </Card>
      ))}
    </div>
  );
}

export function AdminPage() {
  const { t } = useTranslation();
  const { platformRole } = useAuth();
  const [tab, setTab] = useState<"workspaces" | "plans">("workspaces");

  return (
    <AdminGuard>
      <div className="mx-auto w-full max-w-6xl px-4 py-8 md:px-6">
        <Button asChild variant="ghost" size="sm" className="mb-4">
          <Link to="/app">
            <ArrowLeft className="h-4 w-4" />
            {t("admin.back")}
          </Link>
        </Button>

        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="flex items-center gap-2 text-2xl font-bold tracking-tight">
              <Building2 className="h-6 w-6 text-primary" />
              {t("admin.title")}
            </h1>
             <p className="mt-1 flex items-center gap-2 text-sm text-muted-foreground">
               {t("admin.subtitle")}
               {platformRole === "support_admin" ? (
                 <Badge variant="secondary">{t("admin.viewOnly")}</Badge>
               ) : null}
             </p>
          </div>
          <div className="flex rounded-lg border bg-card p-1 text-sm">
            <button
              type="button"
              onClick={() => setTab("workspaces")}
              className={`flex items-center gap-1.5 rounded-md px-3 py-1.5 font-medium transition-colors ${
                tab === "workspaces"
                  ? "bg-primary/10 text-primary"
                  : "text-muted-foreground hover:text-foreground"
              }`}
            >
              <Building2 className="h-4 w-4" />
              {t("admin.workspacesTab")}
            </button>
            <button
              type="button"
              onClick={() => setTab("plans")}
              className={`flex items-center gap-1.5 rounded-md px-3 py-1.5 font-medium transition-colors ${
                tab === "plans"
                  ? "bg-primary/10 text-primary"
                  : "text-muted-foreground hover:text-foreground"
              }`}
            >
              <Users className="h-4 w-4" />
              {t("admin.plansTab")}
            </button>
          </div>
        </div>

        <div className="mt-6 space-y-6">
          <OverviewCards />
          {tab === "workspaces" ? <WorkspacesPanel /> : <PlansPanel />}
        </div>
      </div>
    </AdminGuard>
  );
}
