import { Link } from "react-router-dom";
import { useTranslation } from "react-i18next";
import {
  BarChart3,
  Boxes,
  Check,
  Receipt,
  ShoppingCart,
  Sparkles,
  Users,
} from "lucide-react";
import {
  Area,
  AreaChart,
  ResponsiveContainer,
  Tooltip,
} from "recharts";
import { Logo } from "@/components/brand/logo";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { LanguageToggle } from "@/components/language-toggle";
import { ThemeToggle } from "@/components/theme-toggle";
import { useAuth } from "@/providers/auth-provider";

const heroSeries = [
  { day: "Mon", value: 620 },
  { day: "Tue", value: 780 },
  { day: "Wed", value: 710 },
  { day: "Thu", value: 940 },
  { day: "Fri", value: 1120 },
  { day: "Sat", value: 1380 },
  { day: "Sun", value: 1210 },
];

const featureIcons = [ShoppingCart, Boxes, Users, Receipt, BarChart3, Sparkles];

const pricing = [
  { key: "free", price: "$0", highlighted: false },
  { key: "pro", price: "$19", highlighted: true },
  { key: "enterprise", price: "$49", highlighted: false },
];

export function LandingPage() {
  const { t } = useTranslation();
  const { user } = useAuth();
  const primaryTarget = user ? "/app" : "/register";

  return (
    <div className="min-h-screen bg-background">
      <header className="sticky top-0 z-30 border-b bg-background/80 backdrop-blur">
        <div className="mx-auto flex h-16 w-full max-w-6xl items-center gap-4 px-4 md:px-6">
          <Link to="/" aria-label={t("common.appName")}>
            <Logo />
          </Link>
          <nav className="ml-4 hidden items-center gap-6 text-sm text-muted-foreground md:flex">
            <a href="#features" className="transition-colors hover:text-foreground">
              {t("landing.ctaSecondary")}
            </a>
            <a href="#localization" className="transition-colors hover:text-foreground">
              {t("landing.localizationTitle")}
            </a>
            <a href="#pricing" className="transition-colors hover:text-foreground">
              {t("landing.pricingTitle")}
            </a>
          </nav>
          <div className="ml-auto flex items-center gap-1.5">
            <LanguageToggle />
            <ThemeToggle />
            <Button asChild size="sm" className="ml-1">
              <Link to={user ? "/app" : "/login"}>
                {user ? t("common.openWorkspace") : t("common.signIn")}
              </Link>
            </Button>
          </div>
        </div>
      </header>

      <section className="relative overflow-hidden">
        <div className="pointer-events-none absolute -left-32 top-10 h-72 w-72 rounded-full bg-primary/20 blur-3xl" />
        <div className="pointer-events-none absolute -right-24 top-40 h-72 w-72 rounded-full bg-accent/20 blur-3xl" />
        <div className="relative mx-auto grid w-full max-w-6xl items-center gap-10 px-4 py-16 md:grid-cols-2 md:px-6 md:py-24">
          <div className="animate-fade-in">
            <Badge variant="accent" className="mb-4 gap-1.5">
              <Sparkles className="h-3.5 w-3.5" />
              {t("landing.badge")}
            </Badge>
            <h1 className="text-balance text-4xl font-extrabold leading-tight tracking-tight md:text-5xl">
              {t("landing.title")}
            </h1>
            <p className="mt-5 max-w-xl text-base leading-relaxed text-muted-foreground md:text-lg">
              {t("landing.subtitle")}
            </p>
            <div className="mt-8 flex flex-wrap items-center gap-3">
              <Button asChild size="lg">
                <Link to={primaryTarget}>
                  {user ? t("landing.ctaPrimary") : t("common.getStarted")}
                </Link>
              </Button>
              <Button asChild size="lg" variant="outline">
                <a href="#features">{t("landing.ctaSecondary")}</a>
              </Button>
            </div>
            <p className="mt-6 text-sm font-medium text-muted-foreground">
              {t("landing.trust")}
            </p>
          </div>

          <div className="animate-fade-in">
            <Card className="overflow-hidden border-primary/10">
              <div className="flex items-center justify-between border-b bg-secondary/40 px-5 py-3">
                <div className="flex items-center gap-2">
                  <span className="h-2 w-2 rounded-full bg-destructive/70" />
                  <span className="h-2 w-2 rounded-full bg-accent/80" />
                  <span className="h-2 w-2 rounded-full bg-success/70" />
                </div>
                <Badge variant="secondary" className="text-[10px]">
                  {t("dashboard.sampleData")}
                </Badge>
              </div>
              <CardContent className="space-y-4 p-5">
                <div className="flex items-end justify-between">
                  <div>
                    <p className="text-xs font-medium text-muted-foreground">
                      {t("dashboard.statRevenue")}
                    </p>
                    <p className="mt-1 text-3xl font-bold tracking-tight">
                      $12,450
                    </p>
                  </div>
                  <Badge variant="success">+12.4%</Badge>
                </div>
                <div className="h-36 w-full">
                  <ResponsiveContainer width="100%" height="100%">
                    <AreaChart data={heroSeries}>
                      <defs>
                        <linearGradient id="heroFill" x1="0" y1="0" x2="0" y2="1">
                          <stop
                            offset="0%"
                            stopColor="hsl(var(--primary))"
                            stopOpacity={0.35}
                          />
                          <stop
                            offset="100%"
                            stopColor="hsl(var(--primary))"
                            stopOpacity={0}
                          />
                        </linearGradient>
                      </defs>
                      <Tooltip
                        contentStyle={{
                          background: "hsl(var(--card))",
                          border: "1px solid hsl(var(--border))",
                          borderRadius: 12,
                          fontSize: 12,
                        }}
                      />
                      <Area
                        type="monotone"
                        dataKey="value"
                        stroke="hsl(var(--primary))"
                        strokeWidth={2.5}
                        fill="url(#heroFill)"
                      />
                    </AreaChart>
                  </ResponsiveContainer>
                </div>
                <div className="grid grid-cols-3 gap-3 border-t pt-4">
                  {[
                    { label: t("dashboard.statSales"), value: "128" },
                    { label: t("dashboard.statProducts"), value: "342" },
                    { label: t("dashboard.statLowStock"), value: "7" },
                  ].map((item) => (
                    <div key={item.label} className="rounded-lg bg-secondary/50 p-3">
                      <p className="text-[11px] text-muted-foreground">
                        {item.label}
                      </p>
                      <p className="mt-1 text-lg font-semibold">{item.value}</p>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>
          </div>
        </div>
      </section>

      <section id="features" className="border-t bg-secondary/30">
        <div className="mx-auto w-full max-w-6xl px-4 py-16 md:px-6">
          <div className="mx-auto max-w-2xl text-center">
            <h2 className="text-3xl font-bold tracking-tight">
              {t("landing.featuresTitle")}
            </h2>
            <p className="mt-3 text-muted-foreground">
              {t("landing.featuresSubtitle")}
            </p>
          </div>
          <div className="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {featureIcons.map((Icon, index) => (
              <Card key={index} className="h-full">
                <CardContent className="p-5">
                  <span className="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <Icon className="h-5 w-5" />
                  </span>
                  <h3 className="mt-4 font-semibold">
                    {t(`landing.feature${index + 1}Title`)}
                  </h3>
                  <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                    {t(`landing.feature${index + 1}Body`)}
                  </p>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>
      </section>

      <section id="localization" className="border-t">
        <div className="mx-auto grid w-full max-w-6xl items-center gap-10 px-4 py-16 md:grid-cols-2 md:px-6">
          <div>
            <Badge variant="default" className="mb-3">
              {t("landing.badge")}
            </Badge>
            <h2 className="text-3xl font-bold tracking-tight">
              {t("landing.localizationTitle")}
            </h2>
            <p className="mt-3 leading-relaxed text-muted-foreground">
              {t("landing.localizationBody")}
            </p>
            <ul className="mt-6 space-y-3">
              {[1, 2, 3, 4].map((point) => (
                <li key={point} className="flex items-start gap-3 text-sm">
                  <span className="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-success/15 text-success">
                    <Check className="h-3 w-3" />
                  </span>
                  <span>{t(`landing.point${point}`)}</span>
                </li>
              ))}
            </ul>
          </div>
          <Card className="bg-gradient-to-br from-primary/5 via-card to-accent/5">
            <CardContent className="grid gap-4 p-6 sm:grid-cols-2">
              {[
                { label: "Currency", value: "USD · KHR" },
                { label: "Language", value: "ខ្មែរ · English" },
                { label: "Timezone", value: "Asia/Phnom_Penh" },
                { label: "Payments", value: "KHQR · ABA · Cash" },
              ].map((item) => (
                <div key={item.label} className="rounded-lg border bg-card p-4">
                  <p className="text-[11px] font-medium uppercase tracking-wider text-muted-foreground">
                    {item.label}
                  </p>
                  <p className="mt-1.5 font-semibold">{item.value}</p>
                </div>
              ))}
            </CardContent>
          </Card>
        </div>
      </section>

      <section id="pricing" className="border-t bg-secondary/30">
        <div className="mx-auto w-full max-w-6xl px-4 py-16 md:px-6">
          <div className="mx-auto max-w-2xl text-center">
            <h2 className="text-3xl font-bold tracking-tight">
              {t("landing.pricingTitle")}
            </h2>
            <p className="mt-3 text-muted-foreground">
              {t("landing.pricingSubtitle")}
            </p>
          </div>
          <div className="mt-10 grid gap-4 md:grid-cols-3">
            {pricing.map((plan) => (
              <Card
                key={plan.key}
                className={
                  plan.highlighted
                    ? "relative border-primary/40 ring-1 ring-primary/20"
                    : "relative"
                }
              >
                {plan.highlighted ? (
                  <Badge
                    variant="default"
                    className="absolute right-4 top-4 text-[10px]"
                  >
                    {t("landing.mostPopular")}
                  </Badge>
                ) : null}
                <CardContent className="p-6">
                  <p className="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                    {t(`landing.${plan.key}`)}
                  </p>
                  <div className="mt-3 flex items-end gap-1">
                    <span className="text-4xl font-extrabold tracking-tight">
                      {plan.price}
                    </span>
                    <span className="pb-1 text-sm text-muted-foreground">
                      {t("landing.perMonth")}
                    </span>
                  </div>
                  <p className="mt-3 min-h-[40px] text-sm text-muted-foreground">
                    {t(`landing.${plan.key}Desc`)}
                  </p>
                  <Button
                    asChild
                    variant={plan.highlighted ? "default" : "outline"}
                    className="mt-5 w-full"
                  >
                    <Link to={user ? "/app" : "/register"}>
                      {t(
                        plan.key === "free"
                          ? "landing.ctaFree"
                          : plan.key === "pro"
                            ? "landing.ctaPro"
                            : "landing.ctaEnt",
                      )}
                    </Link>
                  </Button>
                </CardContent>
              </Card>
            ))}
          </div>
          <p className="mt-6 text-center text-xs text-muted-foreground">
            {t("landing.pricingNote")}
          </p>
        </div>
      </section>

      <footer className="border-t">
        <div className="mx-auto flex w-full max-w-6xl flex-col gap-6 px-4 py-10 md:flex-row md:items-center md:justify-between md:px-6">
          <div className="space-y-2">
            <Logo />
            <p className="text-xs text-muted-foreground">
              © {new Date().getFullYear()} MyBusiness Assistant.{" "}
              {t("landing.footerRights")}
            </p>
          </div>
          <div className="flex flex-wrap items-center gap-x-6 gap-y-2 text-xs text-muted-foreground">
            <a href="#features" className="hover:text-foreground">
              {t("landing.footerProduct")}
            </a>
            <a href="#pricing" className="hover:text-foreground">
              {t("landing.footerCompany")}
            </a>
            <Link
              to={user ? "/app" : "/login"}
              className="hover:text-foreground"
            >
              {t("landing.footerSupport")}
            </Link>
            <Link to="/privacy" className="hover:text-foreground">
              {t("landing.footerPrivacy")}
            </Link>
            <Link to="/terms" className="hover:text-foreground">
              {t("landing.footerTerms")}
            </Link>
          </div>
        </div>
      </footer>
    </div>
  );
}
