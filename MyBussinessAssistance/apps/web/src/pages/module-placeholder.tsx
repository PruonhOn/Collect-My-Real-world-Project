import { Link, useParams } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { ArrowLeft, Clock } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { allNavItems } from "@/config/navigation";
import { NotFoundPage } from "./not-found";

export function ModulePlaceholderPage() {
  const { module } = useParams();
  const { t } = useTranslation();

  const item = allNavItems.find((entry) => entry.to === `/app/${module}`);

  if (!item) {
    return <NotFoundPage />;
  }

  const Icon = item.icon;

  return (
    <div className="flex min-h-[60vh] items-center justify-center">
      <Card className="w-full max-w-xl">
        <CardContent className="flex flex-col items-center p-8 text-center">
          <span className="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 text-primary">
            <Icon className="h-7 w-7" />
          </span>
          <h2 className="mt-5 text-xl font-bold tracking-tight">
            {t(`nav.${item.key}`)}
          </h2>
          <Badge variant="accent" className="mt-2 gap-1.5">
            <Clock className="h-3 w-3" />
            {item.phase}
          </Badge>
          <p className="mt-4 text-sm leading-relaxed text-muted-foreground">
            {t("module.body", { phase: item.phase })}
          </p>
          <p className="mt-2 text-xs text-muted-foreground/80">
            {t("module.note")}
          </p>
          <Button asChild variant="outline" className="mt-6">
            <Link to="/app">
              <ArrowLeft className="h-4 w-4" />
              {t("module.back")}
            </Link>
          </Button>
        </CardContent>
      </Card>
    </div>
  );
}
