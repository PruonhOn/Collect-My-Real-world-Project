import { Link } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { ArrowLeft } from "lucide-react";
import { Button } from "@/components/ui/button";

export function NotFoundPage() {
  const { t } = useTranslation();

  return (
    <div className="flex min-h-[60vh] flex-col items-center justify-center text-center">
      <p className="text-6xl font-extrabold tracking-tight text-primary/30">
        404
      </p>
      <h2 className="mt-3 text-xl font-bold">{t("notFound.title")}</h2>
      <p className="mt-2 max-w-sm text-sm text-muted-foreground">
        {t("notFound.body")}
      </p>
      <Button asChild className="mt-6" variant="outline">
        <Link to="/app">
          <ArrowLeft className="h-4 w-4" />
          {t("common.backToDashboard")}
        </Link>
      </Button>
    </div>
  );
}
