import { useEffect, useState } from "react";
import { Link, Outlet, useLocation } from "react-router-dom";
import { Menu, ShieldAlert } from "lucide-react";
import { useTranslation } from "react-i18next";
import { Button } from "@/components/ui/button";
import { LanguageToggle } from "@/components/language-toggle";
import { ThemeToggle } from "@/components/theme-toggle";
import { useAuth } from "@/providers/auth-provider";
import { Sidebar } from "./sidebar";
import { UserMenu } from "./user-menu";
import { NotificationBell } from "./notification-bell";
import { findNavItem } from "@/config/navigation";

export function AppShell() {
  const { t } = useTranslation();
  const { user } = useAuth();
  const location = useLocation();
  const [mobileOpen, setMobileOpen] = useState(false);

  useEffect(() => {
    setMobileOpen(false);
  }, [location.pathname]);

  const current = findNavItem(location.pathname);
  const title = current ? t(`nav.${current.key}`) : t("nav.dashboard");

  return (
    <div className="min-h-screen bg-background">
      <aside className="fixed inset-y-0 left-0 z-30 hidden w-64 border-r bg-card/60 backdrop-blur md:block print:hidden">
        <Sidebar />
      </aside>

      {mobileOpen ? (
        <div className="fixed inset-0 z-40 md:hidden print:hidden">
          <button
            type="button"
            aria-label="Close navigation"
            className="absolute inset-0 bg-slate-950/50"
            onClick={() => setMobileOpen(false)}
          />
          <div className="absolute inset-y-0 left-0 w-72 animate-slide-in border-r bg-card shadow-xl">
            <Sidebar onNavigate={() => setMobileOpen(false)} />
          </div>
        </div>
      ) : null}

      <div className="md:pl-64 print:pl-0">
        <header className="sticky top-0 z-20 flex h-16 items-center gap-3 border-b bg-background/80 px-4 backdrop-blur md:px-8 print:hidden">
          <Button
            variant="ghost"
            size="icon"
            className="md:hidden"
            onClick={() => setMobileOpen(true)}
            aria-label="Open navigation"
          >
            <Menu className="h-5 w-5" />
          </Button>

          <div className="flex min-w-0 items-center gap-2">
            <h1 className="truncate text-sm font-semibold md:text-base">
              {title}
            </h1>
          </div>

          <div className="ml-auto flex items-center gap-1">
            <NotificationBell />
            <LanguageToggle />
            <ThemeToggle />
            <div className="ml-1">
              <UserMenu />
            </div>
          </div>
        </header>

        <main className="mx-auto w-full max-w-7xl px-4 py-6 md:px-8 md:py-8 print:max-w-none print:p-0">
          {user && !user.emailVerifiedAt ? (
            <div className="mb-5 flex flex-col gap-2 rounded-lg border border-accent/30 bg-accent/10 px-4 py-3 text-sm sm:flex-row sm:items-center sm:justify-between print:hidden">
              <span className="flex items-center gap-2 text-accent-foreground">
                <ShieldAlert className="h-4 w-4 shrink-0" />
                {t("auth.verifyBannerGeneral")}
              </span>
              <Link
                to="/verify-email"
                className="text-xs font-semibold underline"
              >
                {t("auth.verifyNow")}
              </Link>
            </div>
          ) : null}
          <Outlet />
        </main>
      </div>
    </div>
  );
}
