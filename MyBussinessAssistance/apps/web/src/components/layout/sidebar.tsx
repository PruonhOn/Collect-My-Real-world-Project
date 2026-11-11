import { NavLink, Link } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { ArrowUpRight } from "lucide-react";
import { Logo } from "@/components/brand/logo";
import { navGroups } from "@/config/navigation";
import { useAuth } from "@/providers/auth-provider";
import { cn } from "@/lib/utils";
import { WorkspaceSwitcher } from "./workspace-switcher";

type SidebarProps = {
  onNavigate?: () => void;
};

export function Sidebar({ onNavigate }: SidebarProps) {
  const { t } = useTranslation();
  const { hasModule } = useAuth();

  return (
    <div className="flex h-full flex-col gap-6 overflow-y-auto px-3 py-5 scrollbar-thin">
      <div className="px-2">
        <Link to="/" onClick={onNavigate} aria-label={t("common.appName")}>
          <Logo />
        </Link>
      </div>

      <WorkspaceSwitcher />

      <nav className="flex flex-1 flex-col gap-5">
        {navGroups.map((group) => {
          const items = group.items.filter(
            (item) => item.alwaysAllowed || hasModule(item.module),
          );
          if (items.length === 0) return null;

          return (
            <div key={group.key}>
              <p className="px-3 pb-1.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-muted-foreground">
                {t(`nav.${group.key}`)}
              </p>
              <div className="space-y-0.5">
                {items.map((item) => {
                const Icon = item.icon;
                return (
                  <NavLink
                    key={item.key}
                    to={item.to}
                    end={item.to === "/app"}
                    onClick={onNavigate}
                    className={({ isActive }) =>
                      cn(
                        "group flex items-center gap-2.5 rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition-colors",
                        isActive
                          ? "bg-primary/10 text-primary"
                          : "hover:bg-secondary hover:text-foreground",
                      )
                    }
                  >
                    {({ isActive }) => (
                      <>
                        <Icon
                          className={cn(
                            "h-4 w-4 shrink-0",
                            isActive ? "text-primary" : "text-muted-foreground",
                          )}
                        />
                        <span className="truncate">{t(`nav.${item.key}`)}</span>
                        {!item.ready ? (
                          <span className="ml-auto text-[10px] font-normal text-muted-foreground/70">
                            {item.phase.replace("Phase ", "P")}
                          </span>
                        ) : null}
                      </>
                    )}
                  </NavLink>
                );
                })}
              </div>
            </div>
          );
        })}
      </nav>

      <Link
        to="/"
        onClick={onNavigate}
        className="flex items-center justify-between rounded-lg border bg-card px-3 py-2.5 text-xs font-medium text-muted-foreground transition-colors hover:text-foreground"
      >
        <span>{t("landing.footerCompany")}</span>
        <ArrowUpRight className="h-3.5 w-3.5" />
      </Link>
    </div>
  );
}
