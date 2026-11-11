import { useTranslation } from "react-i18next";
import { Link } from "react-router-dom";
import { ArrowLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";

export function LegalPage({ kind }: { kind: "privacy" | "terms" }) {
  const { t } = useTranslation();

  return (
    <div className="mx-auto w-full max-w-3xl px-4 py-12 md:px-6">
      <Button asChild variant="ghost" size="sm" className="mb-6">
        <Link to="/">
          <ArrowLeft className="h-4 w-4" />
          {t("legal.back")}
        </Link>
      </Button>
      <h1 className="text-2xl font-bold tracking-tight">
        {kind === "privacy" ? t("legal.privacyTitle") : t("legal.termsTitle")}
      </h1>
      <p className="mt-2 text-sm text-muted-foreground">
        {t("legal.updated", { date: "2026-11-05" })}
      </p>
      <Card className="mt-6">
        <CardContent className="space-y-4 p-6 text-sm leading-relaxed text-muted-foreground">
          {(kind === "privacy"
            ? (["privacyCollection", "privacyUse", "privacyStorage", "privacyContact"] as const)
            : (["termsUse", "termsBilling", "termsData", "termsContact"] as const)
          ).map((key) => (
            <div key={key}>
              <h2 className="font-semibold text-foreground">{t(`legal.${key}Title`)}</h2>
              <p className="mt-1">{t(`legal.${key}`)}</p>
            </div>
          ))}
        </CardContent>
      </Card>
    </div>
  );
}
