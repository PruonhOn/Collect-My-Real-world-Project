import { Languages } from "lucide-react";
import { useTranslation } from "react-i18next";
import { Button } from "@/components/ui/button";

export function LanguageToggle() {
  const { i18n, t } = useTranslation();
  const isKhmer = i18n.language.startsWith("km");

  return (
    <Button
      variant="ghost"
      size="sm"
      onClick={() => void i18n.changeLanguage(isKhmer ? "en" : "km")}
      title={t("common.language")}
      className="gap-1.5"
    >
      <Languages className="h-4 w-4" />
      <span className="font-semibold">{isKhmer ? "ខ្មែរ" : "EN"}</span>
    </Button>
  );
}
