import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useTranslation } from "react-i18next";
import { Check, CreditCard, Sparkles } from "lucide-react";
import { ConfirmDialog } from "@/components/app/confirm-dialog";
import { PageHeader } from "@/components/app/page-header";
import { StatusBadge } from "@/components/app/status-badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import {
  cancelSubscription,
  changeSubscription,
  fetchSubscription,
  type SubscriptionPlan,
} from "@/lib/business-api";
import { ApiClientError } from "@/lib/api";
import { formatDate } from "@/lib/format";
import { formatCents, formatNumber } from "@/lib/money";
import { useBusinessContext } from "@/lib/use-business";
import { cn } from "@/lib/utils";

function errorMessage(error: unknown) {
  return error instanceof ApiClientError ? error.message : "Request failed.";
}

function formatLimit(
  value: number | null,
  t: (key: string) => string,
  locale: string,
) {
  return value === null ? t("business.subscription.unlimited") : formatNumber(value, locale);
}

function entitlementLabel(key: string) {
  return key.replace(/^max_/, "").replace(/_/g, " ");
}

export function SubscriptionPage() {
  const { t, i18n } = useTranslation();
  const { workspaceId, workspace, can } = useBusinessContext();
  const locale = i18n.language.startsWith("km") ? "km" : "en";
  const queryClient = useQueryClient();

  const [billingCycle, setBillingCycle] = useState<"monthly" | "yearly">(
    "monthly",
  );
  const [pendingPlan, setPendingPlan] = useState<SubscriptionPlan | null>(null);
  const [cancelOpen, setCancelOpen] = useState(false);
  const [notice, setNotice] = useState<{
    message: string;
    tone: "success" | "error";
  } | null>(null);
  const showNotice = (message: string, tone: "success" | "error" = "success") =>
    setNotice({ message, tone });

  const subscriptionQuery = useQuery({
    queryKey: ["subscription", workspaceId],
    queryFn: () => fetchSubscription(workspaceId),
  });

  const data = subscriptionQuery.data;
  const currentPlanId = data?.subscription?.plan.id ?? null;

  const changeMutation = useMutation({
    mutationFn: (plan: SubscriptionPlan) =>
      changeSubscription(workspaceId, {
        planId: plan.id,
        billingCycle,
      }),
    onSuccess: async () => {
      await queryClient.invalidateQueries({
        queryKey: ["subscription", workspaceId],
      });
      showNotice(t("business.subscription.changed"));
      setPendingPlan(null);
    },
    onError: (error) => showNotice(errorMessage(error), "error"),
  });

  const cancelMutation = useMutation({
    mutationFn: () => cancelSubscription(workspaceId),
    onSuccess: async () => {
      await queryClient.invalidateQueries({
        queryKey: ["subscription", workspaceId],
      });
      showNotice(t("business.subscription.cancelled"));
      setCancelOpen(false);
    },
    onError: (error) => showNotice(errorMessage(error), "error"),
  });

  const usageRows = data
    ? [
        {
          key: "products",
          label: t("business.subscription.usageProducts"),
          used: data.usage.products,
          limit: data.limits.maxProducts,
        },
        {
          key: "employees",
          label: t("business.subscription.usageEmployees"),
          used: data.usage.employees,
          limit: data.limits.maxEmployees,
        },
        {
          key: "branches",
          label: t("business.subscription.usageBranches"),
          used: data.usage.branches,
          limit: data.limits.maxBranches,
        },
        {
          key: "transactions",
          label: t("business.subscription.usageTransactions"),
          used: data.usage.transactionsThisMonth,
          limit: data.limits.maxTransactionsPerMonth,
        },
      ]
    : [];

  return (
    <div className="space-y-6">
      <PageHeader
        title={t("business.subscription.title")}
        description={t("business.subscription.subtitle")}
        actions={
          <div className="flex rounded-lg border p-0.5">
            <button
              type="button"
              onClick={() => setBillingCycle("monthly")}
              className={cn(
                "rounded-md px-3 py-1.5 text-sm font-medium transition-colors",
                billingCycle === "monthly"
                  ? "bg-primary text-primary-foreground"
                  : "text-muted-foreground hover:text-foreground",
              )}
            >
              {t("business.subscription.monthly")}
            </button>
            <button
              type="button"
              onClick={() => setBillingCycle("yearly")}
              className={cn(
                "rounded-md px-3 py-1.5 text-sm font-medium transition-colors",
                billingCycle === "yearly"
                  ? "bg-primary text-primary-foreground"
                  : "text-muted-foreground hover:text-foreground",
              )}
            >
              {t("business.subscription.yearly")}
            </button>
          </div>
        }
      />

      {notice ? (
        <div
          className={
            notice.tone === "error"
              ? "rounded-lg border border-destructive/30 bg-destructive/10 px-4 py-2.5 text-sm text-destructive"
              : "rounded-lg border border-success/30 bg-success/10 px-4 py-2.5 text-sm text-success"
          }
        >
          {notice.message}
        </div>
      ) : null}

      {subscriptionQuery.isLoading ? (
        <div className="grid gap-4 lg:grid-cols-3">
          {[0, 1, 2].map((index) => (
            <Skeleton key={index} className="h-48 w-full" />
          ))}
        </div>
      ) : subscriptionQuery.isError ? (
        <p className="py-10 text-center text-sm text-destructive">
          {errorMessage(subscriptionQuery.error)}
        </p>
      ) : data ? (
        <>
          <Card>
            <CardContent className="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
              <div className="flex items-start gap-3">
                <span className="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                  <CreditCard className="h-5 w-5" />
                </span>
                <div>
                  <div className="flex items-center gap-2">
                    <h3 className="text-lg font-semibold">
                      {data.subscription?.plan.name ??
                        t("business.subscription.noPlan")}
                    </h3>
                    {data.subscription ? (
                      <StatusBadge status={data.subscription.subscription.status} />
                    ) : null}
                  </div>
                  <p className="mt-1 text-sm text-muted-foreground">
                    {data.subscription
                      ? data.subscription.subscription.cancelAtPeriodEnd
                        ? t("business.subscription.cancelsOn", {
                            date: formatDate(
                              data.subscription.subscription
                                .currentPeriodEnd ?? Date.now(),
                              locale,
                            ),
                          })
                        : t("business.subscription.renewsOn", {
                            date: formatDate(
                              data.subscription.subscription
                                .currentPeriodEnd ?? Date.now(),
                              locale,
                            ),
                          })
                      : t("business.subscription.noPlanBody")}
                  </p>
                  {data.subscription?.subscription.trialEndsAt ? (
                    <p className="mt-1 text-xs text-accent">
                      {t("business.subscription.trialEnds", {
                        date: formatDate(
                          data.subscription.subscription.trialEndsAt,
                          locale,
                        ),
                      })}
                    </p>
                  ) : null}
                </div>
              </div>
              {data.subscription &&
              !data.subscription.subscription.cancelAtPeriodEnd &&
              can("subscription:manage") ? (
                <Button
                  variant="outline"
                  onClick={() => setCancelOpen(true)}
                >
                  {t("business.subscription.cancel")}
                </Button>
              ) : null}
            </CardContent>
          </Card>

          <div>
            <h3 className="mb-3 text-sm font-semibold">
              {t("business.subscription.usageTitle")}
            </h3>
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
              {usageRows.map((row) => {
                const percent =
                  row.limit === null
                    ? 0
                    : Math.min(100, Math.round((row.used / row.limit) * 100));
                return (
                  <Card key={row.key}>
                    <CardContent className="space-y-2 p-5">
                      <p className="text-xs text-muted-foreground">
                        {row.label}
                      </p>
                      <p className="text-lg font-semibold">
                        {formatNumber(row.used, locale)}
                        <span className="text-sm font-normal text-muted-foreground">
                          {" / "}
                          {formatLimit(row.limit, t, locale)}
                        </span>
                      </p>
                      <div className="h-1.5 overflow-hidden rounded-full bg-secondary">
                        <div
                          className={cn(
                            "h-full rounded-full",
                            percent >= 90 ? "bg-destructive" : "bg-primary",
                          )}
                          style={{
                            width:
                              row.limit === null ? "0%" : `${percent}%`,
                          }}
                        />
                      </div>
                    </CardContent>
                  </Card>
                );
              })}
            </div>
          </div>

          <div>
            <div className="mb-3 flex items-center gap-2">
              <h3 className="text-sm font-semibold">
                {t("business.subscription.plansTitle")}
              </h3>
              <Sparkles className="h-4 w-4 text-accent" />
            </div>
            <div className="grid gap-4 lg:grid-cols-3">
              {data.plans.map((plan) => {
                const price =
                  billingCycle === "yearly"
                    ? plan.yearlyPriceCents
                    : plan.monthlyPriceCents;
                const isCurrent = plan.id === currentPlanId;
                const features = Object.entries(plan.entitlements).filter(
                  ([, value]) => value !== "false",
                );
                return (
                  <Card
                    key={plan.id}
                    className={cn(
                      "relative",
                      isCurrent ? "border-primary shadow-sm" : undefined,
                    )}
                  >
                    <CardContent className="flex h-full flex-col p-5">
                      {isCurrent ? (
                        <span className="absolute right-4 top-4 rounded-full bg-primary/10 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-primary">
                          {t("business.subscription.current")}
                        </span>
                      ) : null}
                      <h4 className="text-lg font-semibold">{plan.name}</h4>
                      <p className="mt-1 min-h-10 text-sm text-muted-foreground">
                        {plan.description ?? ""}
                      </p>
                      <div className="mt-3 flex items-baseline gap-1">
                        <span className="text-2xl font-bold">
                          {formatCents(price, plan.currency, locale)}
                        </span>
                        <span className="text-sm text-muted-foreground">
                          /{" "}
                          {billingCycle === "yearly"
                            ? t("business.subscription.year")
                            : t("business.subscription.month")}
                        </span>
                      </div>
                      {plan.trialDays > 0 ? (
                        <p className="mt-1 text-xs text-accent">
                          {t("business.subscription.trialDays", {
                            count: plan.trialDays,
                          })}
                        </p>
                      ) : null}
                      <ul className="mt-4 flex-1 space-y-2">
                        {features.slice(0, 6).map(([key, value]) => (
                          <li
                            key={key}
                            className="flex items-start gap-2 text-sm"
                          >
                            <Check className="mt-0.5 h-4 w-4 shrink-0 text-success" />
                            <span className="capitalize">
                              {entitlementLabel(key)}
                              {value !== "true" ? `: ${value}` : ""}
                            </span>
                          </li>
                        ))}
                      </ul>
                      <Button
                        className="mt-5 w-full"
                        variant={isCurrent ? "outline" : "default"}
                        disabled={
                          isCurrent ||
                          !can("subscription:manage") ||
                          changeMutation.isPending
                        }
                        onClick={() => setPendingPlan(plan)}
                      >
                        {isCurrent
                          ? t("business.subscription.currentPlan")
                          : t("business.subscription.choosePlan")}
                      </Button>
                    </CardContent>
                  </Card>
                );
              })}
            </div>
          </div>

          <p className="text-xs text-muted-foreground">
            {t("business.subscription.currencyNote", {
              currency: workspace.defaultCurrency,
            })}
          </p>
        </>
      ) : null}

      <ConfirmDialog
        open={Boolean(pendingPlan)}
        onClose={() => setPendingPlan(null)}
        onConfirm={() => {
          if (pendingPlan) changeMutation.mutate(pendingPlan);
        }}
        title={t("business.subscription.changeTitle")}
        description={t("business.subscription.changeBody", {
          plan: pendingPlan?.name ?? "",
          cycle:
            billingCycle === "yearly"
              ? t("business.subscription.yearly")
              : t("business.subscription.monthly"),
        })}
        confirmLabel={t("business.subscription.changeConfirm")}
        cancelLabel={t("common.cancel")}
        pending={changeMutation.isPending}
        destructive={false}
      />

      <ConfirmDialog
        open={cancelOpen}
        onClose={() => setCancelOpen(false)}
        onConfirm={() => cancelMutation.mutate()}
        title={t("business.subscription.cancelTitle")}
        description={t("business.subscription.cancelBody")}
        confirmLabel={t("business.subscription.cancel")}
        cancelLabel={t("common.cancel")}
        pending={cancelMutation.isPending}
      />
    </div>
  );
}
