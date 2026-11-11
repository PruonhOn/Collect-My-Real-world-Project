import type { ReactNode } from "react";
import { Link } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { Check, Sparkles } from "lucide-react";
import { Logo } from "@/components/brand/logo";
import { LanguageToggle } from "@/components/language-toggle";
import { ThemeToggle } from "@/components/theme-toggle";

type AuthLayoutProps = {
  title: string;
  subtitle: string;
  children: ReactNode;
  footer?: ReactNode;
};

export function AuthLayout({
  title,
  subtitle,
  children,
  footer,
}: AuthLayoutProps) {
  const { t } = useTranslation();

  return (
    <div className="grid min-h-screen bg-background lg:grid-cols-2">
      <div className="flex min-h-screen flex-col px-5 py-6 sm:px-8">
        <div className="flex items-center justify-between">
          <Link to="/" aria-label={t("common.appName")}>
            <Logo />
          </Link>
          <div className="flex items-center gap-1">
            <LanguageToggle />
            <ThemeToggle />
          </div>
        </div>

        <div className="mx-auto flex w-full max-w-sm flex-1 flex-col justify-center py-10">
          <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">
            {title}
          </h1>
          <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
            {subtitle}
          </p>
          <div className="mt-7">{children}</div>
          {footer ? (
            <div className="mt-6 text-sm text-muted-foreground">{footer}</div>
          ) : null}
        </div>

        <p className="text-center text-xs text-muted-foreground">
          © {new Date().getFullYear()} {t("common.appName")}
        </p>
      </div>

      <div className="relative hidden overflow-hidden border-l bg-gradient-to-br from-primary/10 via-background to-accent/10 lg:flex lg:flex-col lg:justify-center lg:px-14">
        <div className="pointer-events-none absolute -left-20 top-10 h-72 w-72 rounded-full bg-primary/20 blur-3xl" />
        <div className="pointer-events-none absolute -right-16 bottom-10 h-72 w-72 rounded-full bg-accent/20 blur-3xl" />
        <div className="relative max-w-md">
          <span className="inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
            <Sparkles className="h-3.5 w-3.5" />
            {t("landing.badge")}
          </span>
          <h2 className="mt-5 text-3xl font-extrabold leading-tight tracking-tight">
            {t("landing.title")}
          </h2>
          <p className="mt-4 leading-relaxed text-muted-foreground">
            {t("landing.subtitle")}
          </p>
          <ul className="mt-8 space-y-3 text-sm">
            {[1, 2, 3].map((point) => (
              <li key={point} className="flex items-start gap-3">
                <span className="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-success/15 text-success">
                  <Check className="h-3 w-3" />
                </span>
                <span>{t(`landing.point${point}`)}</span>
              </li>
            ))}
          </ul>
        </div>
      </div>
    </div>
  );
}
