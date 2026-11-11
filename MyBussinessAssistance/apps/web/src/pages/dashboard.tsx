import { Link } from "react-router-dom";
import { useQuery } from "@tanstack/react-query";
import { useTranslation } from "react-i18next";
import {
  AlertTriangle,
  ArrowRight,
  CircleDollarSign,
  Package,
  Receipt,
  ShoppingCart,
  Sparkles,
  Users,
} from "lucide-react";
import {
  Area,
  AreaChart,
  CartesianGrid,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from "recharts";
import { Badge } from "@/components/ui/badge";
import { Button, buttonVariants } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { fetchHealth } from "@/lib/api";
import { fetchDashboard } from "@/lib/business-api";
import { formatDate } from "@/lib/format";
import { formatCents, formatNumber } from "@/lib/money";
import { cn } from "@/lib/utils";
import { useAuth } from "@/providers/auth-provider";
import { useBusinessContext } from "@/lib/use-business";

export function DashboardPage() {
  const { t, i18n } = useTranslation();
  const { user } = useAuth();
  const { workspaceId, workspace } = useBusinessContext();
  const locale = i18n.language.startsWith("km") ? "km" : "en";

  const health = useQuery({
    queryKey: ["health"],
    queryFn: fetchHealth,
    retry: 0,
    refetchInterval: 60_000,
  });
  const dashboard = useQuery({
    queryKey: ["dashboard", workspaceId],
    queryFn: () => fetchDashboard(workspaceId),
  });

  const data = dashboard.data;
  const series = (data?.series ?? []).map((point) => ({
    ...point,
    sales: point.salesCents / 100,
    profit: point.profitCents / 100,
  }));

  const stats = [
    {
      key: "statRevenue",
      hint: "statRevenueHint",
      value: formatCents(
        data?.stats?.revenueCents ?? 0,
        workspace.defaultCurrency,
        locale,
      ),
      icon: CircleDollarSign,
      badge:
        data && data.stats.revenueChangePercent !== 0
          ? `${data.stats.revenueChangePercent > 0 ? "+" : ""}${data.stats.revenueChangePercent}%`
          : null,
      badgeVariant:
        (data?.stats?.revenueChangePercent ?? 0) >= 0
          ? ("success" as const)
          : ("destructive" as const),
    },
    {
      key: "statSales",
      hint: "statSalesHint",
      value: formatNumber(data?.stats?.salesCount ?? 0, locale),
      icon: ShoppingCart,
      badge: null,
      badgeVariant: "default" as const,
    },
    {
      key: "statProducts",
      hint: "statProductsHint",
      value: formatNumber(data?.stats?.activeProducts ?? 0, locale),
      icon: Package,
      badge: null,
      badgeVariant: "default" as const,
    },
    {
      key: "statLowStock",
      hint: "statLowStockHint",
      value: formatNumber(data?.stats?.lowStockCount ?? 0, locale),
      icon: AlertTriangle,
      badge:
        (data?.stats?.lowStockCount ?? 0) > 0
          ? String(data?.stats?.lowStockCount)
          : null,
      badgeVariant: "accent" as const,
    },
  ];

  const quickActions = [
    { key: "quickNewSale", to: "/app/sales", icon: ShoppingCart },
    { key: "quickAddProduct", to: "/app/products", icon: Package },
    { key: "quickAddCustomer", to: "/app/customers", icon: Users },
    { key: "quickRecordExpense", to: "/app/expenses", icon: Receipt },
  ];

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h2 className="text-2xl font-bold tracking-tight">
            {user
              ? t("auth.welcome", { name: user.name.split(" ")[0] })
              : t("dashboard.greeting")}
          </h2>
          <p className="mt-1 text-sm text-muted-foreground">
            {workspace.name} — {t("dashboard.subtitle")}
          </p>
        </div>
        <div className="flex items-center gap-2 text-xs text-muted-foreground">
          <span
            className={cn(
              "h-2 w-2 rounded-full",
              health.data ? "bg-success" : "bg-destructive",
            )}
          />
          {health.data ? t("dashboard.systemOnline") : t("dashboard.systemOffline")}
        </div>
      </div>

      {dashboard.isError ? (
        <Card>
          <CardContent className="py-10 text-center text-sm text-destructive">
            {t("common.noResults")}
          </CardContent>
        </Card>
      ) : (
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          {stats.map((stat) => {
            const Icon = stat.icon;
            return (
              <Card key={stat.key}>
                <CardContent className="p-5">
                  <div className="flex items-start justify-between">
                    <span className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                      <Icon className="h-5 w-5" />
                    </span>
                    {dashboard.isLoading ? (
                      <Skeleton className="h-5 w-12" />
                    ) : stat.badge ? (
                      <Badge variant={stat.badgeVariant}>{stat.badge}</Badge>
                    ) : null}
                  </div>
                  <p className="mt-4 text-sm text-muted-foreground">
                    {t(`dashboard.${stat.key}`)}
                  </p>
                  {dashboard.isLoading ? (
                    <Skeleton className="mt-2 h-7 w-24" />
                  ) : (
                    <p className="mt-1 text-2xl font-bold tracking-tight">
                      {stat.value}
                    </p>
                  )}
                  <p className="mt-1 text-xs text-muted-foreground">
                    {t(`dashboard.${stat.hint}`)}
                  </p>
                </CardContent>
              </Card>
            );
          })}
        </div>
      )}

      <div className="grid gap-4 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardHeader className="flex-row items-start justify-between space-y-0">
            <div>
              <CardTitle>{t("dashboard.trendTitle")}</CardTitle>
              <CardDescription>{t("dashboard.trendSubtitle")}</CardDescription>
            </div>
            <div className="flex items-center gap-3 text-xs">
              <span className="flex items-center gap-1.5">
                <span className="h-2 w-2 rounded-full bg-primary" />
                {t("dashboard.trendSales")}
              </span>
              <span className="flex items-center gap-1.5">
                <span className="h-2 w-2 rounded-full bg-accent" />
                {t("dashboard.trendProfit")}
              </span>
            </div>
          </CardHeader>
          <CardContent>
            {dashboard.isLoading ? (
              <Skeleton className="h-64 w-full" />
            ) : (
              <div className="h-64 w-full">
                <ResponsiveContainer width="100%" height="100%">
                  <AreaChart data={series} margin={{ left: -16, right: 8 }}>
                    <defs>
                      <linearGradient id="salesFill" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stopColor="hsl(var(--primary))" stopOpacity={0.3} />
                        <stop offset="100%" stopColor="hsl(var(--primary))" stopOpacity={0} />
                      </linearGradient>
                      <linearGradient id="profitFill" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stopColor="hsl(var(--accent))" stopOpacity={0.25} />
                        <stop offset="100%" stopColor="hsl(var(--accent))" stopOpacity={0} />
                      </linearGradient>
                    </defs>
                    <CartesianGrid
                      strokeDasharray="3 3"
                      stroke="hsl(var(--border))"
                      vertical={false}
                    />
                    <XAxis
                      dataKey="date"
                      tickLine={false}
                      axisLine={false}
                      tick={{ fontSize: 11, fill: "hsl(var(--muted-foreground))" }}
                      tickFormatter={(value) => String(value).slice(5)}
                    />
                    <YAxis
                      tickLine={false}
                      axisLine={false}
                      tick={{ fontSize: 11, fill: "hsl(var(--muted-foreground))" }}
                    />
                    <Tooltip
                      contentStyle={{
                        background: "hsl(var(--card))",
                        border: "1px solid hsl(var(--border))",
                        borderRadius: 12,
                        fontSize: 12,
                      }}
                      formatter={(value: number) =>
                        formatCents(
                          Math.round(value * 100),
                          workspace.defaultCurrency,
                          locale,
                        )
                      }
                    />
                    <Area
                      type="monotone"
                      dataKey="sales"
                      name={t("dashboard.trendSales")}
                      stroke="hsl(var(--primary))"
                      strokeWidth={2.5}
                      fill="url(#salesFill)"
                    />
                    <Area
                      type="monotone"
                      dataKey="profit"
                      name={t("dashboard.trendProfit")}
                      stroke="hsl(var(--accent))"
                      strokeWidth={2}
                      fill="url(#profitFill)"
                    />
                  </AreaChart>
                </ResponsiveContainer>
              </div>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <AlertTriangle className="h-4 w-4 text-destructive" />
              {t("business.dashboard.lowStockTitle")}
            </CardTitle>
            <CardDescription>
              {t("business.dashboard.lowStockSubtitle")}
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-2">
            {dashboard.isLoading ? (
              <div className="space-y-2">
                {[0, 1, 2].map((index) => (
                  <Skeleton key={index} className="h-10 w-full" />
                ))}
              </div>
            ) : (data?.lowStock?.length ?? 0) === 0 ? (
              <p className="py-8 text-center text-sm text-muted-foreground">
                {t("business.dashboard.allStocked")}
              </p>
            ) : (
              <>
                {data?.lowStock?.slice(0, 5).map((item) => (
                  <div
                    key={`${item.productId}-${item.branchName}`}
                    className="flex items-center justify-between gap-2 rounded-lg bg-secondary/60 px-3 py-2"
                  >
                    <div className="min-w-0">
                      <p className="truncate text-sm font-medium">{item.productName}</p>
                      <p className="text-xs text-muted-foreground">{item.branchName}</p>
                    </div>
                    <Badge variant="destructive">
                      {item.quantity}/{item.threshold} {item.unit}
                    </Badge>
                  </div>
                ))}
                <Button asChild variant="outline" size="sm" className="w-full">
                  <Link to="/app/inventory">
                    {t("business.dashboard.viewAll")}
                    <ArrowRight className="h-4 w-4" />
                  </Link>
                </Button>
              </>
            )}
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-4 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardHeader className="flex-row items-start justify-between space-y-0">
            <div>
              <CardTitle>{t("business.dashboard.recentSales")}</CardTitle>
              <CardDescription>{t("business.dashboard.recentSalesSubtitle")}</CardDescription>
            </div>
            <Button asChild variant="ghost" size="sm">
              <Link to="/app/sales">
                {t("common.viewAll")}
                <ArrowRight className="h-4 w-4" />
              </Link>
            </Button>
          </CardHeader>
          <CardContent>
            {dashboard.isLoading ? (
              <div className="space-y-2">
                {[0, 1, 2].map((index) => (
                  <Skeleton key={index} className="h-12 w-full" />
                ))}
              </div>
            ) : (data?.recentSales?.length ?? 0) === 0 ? (
              <p className="py-10 text-center text-sm text-muted-foreground">
                {t("business.dashboard.noSales")}
              </p>
            ) : (
              <div className="space-y-1">
                {data?.recentSales?.map((sale) => (
                  <Link
                    key={sale.id}
                    to={`/app/invoices/${sale.id}`}
                    className="flex items-center gap-3 rounded-lg px-2 py-2.5 transition-colors hover:bg-secondary/60"
                  >
                    <span className="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 text-primary">
                      <Receipt className="h-4 w-4" />
                    </span>
                    <div className="min-w-0 flex-1">
                      <p className="font-mono text-xs font-semibold">{sale.number}</p>
                      <p className="truncate text-xs text-muted-foreground">
                        {sale.customerName ?? t("business.sales.walkIn")} ·{" "}
                        {formatDate(sale.saleDate, locale)}
                      </p>
                    </div>
                    <div className="text-right">
                      <p className="text-sm font-semibold">
                        {formatCents(
                          sale.totalCents,
                          workspace.defaultCurrency,
                          locale,
                        )}
                      </p>
                      <p className="text-[10px] capitalize text-muted-foreground">
                        {sale.status}
                      </p>
                    </div>
                  </Link>
                ))}
              </div>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-3">
            <CardTitle>{t("dashboard.quickTitle")}</CardTitle>
          </CardHeader>
          <CardContent className="grid grid-cols-2 gap-2">
            {quickActions.map((action) => {
              const Icon = action.icon;
              return (
                <Link
                  key={action.key}
                  to={action.to}
                  className={cn(
                    buttonVariants({ variant: "outline", size: "sm" }),
                    "h-auto flex-col items-start gap-1.5 px-3 py-3 text-left",
                  )}
                >
                  <Icon className="h-4 w-4 text-primary" />
                  <span className="text-xs font-medium">
                    {t(`dashboard.${action.key}`)}
                  </span>
                </Link>
              );
            })}
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>{t("business.dashboard.topProducts")}</CardTitle>
            <CardDescription>{t("business.dashboard.topProductsSubtitle")}</CardDescription>
          </CardHeader>
          <CardContent>
            {dashboard.isLoading ? (
              <div className="space-y-2">
                {[0, 1, 2].map((index) => (
                  <Skeleton key={index} className="h-10 w-full" />
                ))}
              </div>
            ) : (data?.topProducts?.length ?? 0) === 0 ? (
              <p className="py-8 text-center text-sm text-muted-foreground">
                {t("business.dashboard.noProducts")}
              </p>
            ) : (
              <div className="space-y-3">
                {data?.topProducts?.map((product, index) => {
                  const max = Math.max(
1,
...(data?.topProducts?.map((item) => item.revenueCents) ?? [1]),
                  );
                  return (
                    <div key={`${product.productName}-${index}`}>
                      <div className="flex items-center justify-between text-sm">
                        <span className="font-medium">{product.productName}</span>
                        <span className="text-muted-foreground">
                          {formatCents(
                            product.revenueCents,
                            workspace.defaultCurrency,
                            locale,
                          )}
                        </span>
                      </div>
                      <div className="mt-1 flex items-center gap-2">
                        <div className="h-1.5 flex-1 overflow-hidden rounded-full bg-secondary">
                          <div
                            className="h-full rounded-full bg-primary"
                            style={{ width: `${(product.revenueCents / max) * 100}%` }}
                          />
                        </div>
                        <span className="text-xs text-muted-foreground">
                          ×{formatNumber(product.quantity, locale)}
                        </span>
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </CardContent>
        </Card>

        <Card className="border-dashed bg-secondary/30">
          <CardContent className="flex h-full flex-col items-start justify-center gap-4 p-6 sm:flex-row sm:items-center">
            <span className="flex h-10 w-10 items-center justify-center rounded-lg bg-accent/15 text-accent-foreground">
              <Sparkles className="h-5 w-5" />
            </span>
            <div className="flex-1">
              <p className="font-semibold">{t("nav.ai")}</p>
              <p className="text-sm text-muted-foreground">
                {t("landing.feature6Body")}
              </p>
            </div>
            <Button asChild variant="outline" size="sm">
              <Link to="/app/ai">
                {t("dashboard.aiCardCta")}
                <Sparkles className="h-4 w-4" />
              </Link>
            </Button>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
