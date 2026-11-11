import { Link, useParams } from "react-router-dom";
import type { ReactNode } from "react";
import { useTranslation } from "react-i18next";
import { ArrowLeft, Lock, Sparkles } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { useAuth } from "@/providers/auth-provider";
import { allNavItems } from "@/config/navigation";

export function ModuleLockedPage({ moduleKey }: { moduleKey?: string }) {
  const params = useParams();
  const { t } = useTranslation();
  const { activeWorkspace } = useAuth();
  const module = moduleKey ?? params.module;

  const item = allNavItems.find((entry) => entry.to === `/app/${module}`);

  return (
    <div className="flex min-h-[60vh] items-center justify-center">
      <Card className="w-full max-w-xl">
        <CardContent className="flex flex-col items-center p-8 text-center">
          <span className="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 text-primary">
            <Lock className="h-7 w-7" />
          </span>
          <h2 className="mt-5 text-xl font-bold tracking-tight">
            {item ? t(`nav.${item.key}`) : (module ?? "")}
          </h2>
          <p className="mt-4 text-sm leading-relaxed text-muted-foreground">
            {t("module.lockedBody", {
              module: item ? t(`nav.${item.key}`) : (module ?? ""),
              plan: activeWorkspace?.planName ?? t("business.subscription.freePlan"),
            })}
          </p>
          <div className="mt-6 flex flex-wrap items-center justify-center gap-3">
            <Button asChild>
              <Link to="/app/subscription">
                <Sparkles className="h-4 w-4" />
                {t("module.upgrade")}
              </Link>
            </Button>
            <Button asChild variant="outline">
              <Link to="/app">
                <ArrowLeft className="h-4 w-4" />
                {t("module.back")}
              </Link>
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}

export function RequireModule({
  moduleKey,
  children,
}: {
  moduleKey: string;
  children: ReactNode;
}) {
  const { hasModule } = useAuth();

  if (!hasModule(moduleKey)) {
    return <ModuleLockedPage moduleKey={moduleKey} />;
  }

  return <>{children}</>;
}
