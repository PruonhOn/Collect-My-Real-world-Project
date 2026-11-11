import { useRef } from "react";
import { Link, useNavigate } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { LogOut, Settings, Shield } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { useAuth } from "@/providers/auth-provider";

function initials(name: string) {
  return name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase())
    .join("");
}

export function UserMenu() {
  const { t } = useTranslation();
  const { user, signOut, platformRole } = useAuth();
  const navigate = useNavigate();
  const detailsRef = useRef<HTMLDetailsElement>(null);

  if (!user) return null;

  function close() {
    detailsRef.current?.removeAttribute("open");
  }

  async function handleSignOut() {
    close();
    await signOut();
    navigate("/login", { replace: true });
  }

  return (
    <details ref={detailsRef} className="group relative">
      <summary className="flex cursor-pointer list-none items-center gap-2 rounded-full border bg-card py-1 pl-1 pr-3 transition-colors hover:bg-secondary [&::-webkit-details-marker]:hidden">
        <span className="flex h-7 w-7 items-center justify-center rounded-full bg-primary/10 text-[11px] font-bold text-primary">
          {initials(user.name) || "U"}
        </span>
        <span className="hidden max-w-[120px] truncate text-xs font-medium sm:block">
          {user.name}
        </span>
      </summary>

      <div className="absolute right-0 top-[calc(100%+8px)] z-50 w-64 rounded-lg border bg-card p-2 shadow-lg">
        <div className="border-b px-2 pb-2.5">
          <p className="truncate text-sm font-semibold">{user.name}</p>
          <p className="truncate text-xs text-muted-foreground">
            {user.email}
          </p>
          {!user.emailVerifiedAt ? (
            <Link
              to="/verify-email"
              onClick={close}
              className="mt-2 inline-block"
            >
              <Badge variant="accent" className="text-[10px]">
                {t("auth.verifyNow")}
              </Badge>
            </Link>
          ) : null}
        </div>

        <div className="pt-1.5">
          {platformRole ? (
            <Link
              to="/admin"
              onClick={close}
              className="flex items-center gap-2 rounded-md px-2 py-2 text-sm transition-colors hover:bg-secondary"
            >
              <Shield className="h-4 w-4 text-muted-foreground" />
              {t("admin.title")}
            </Link>
          ) : null}
          <Link
            to="/app/settings"
            onClick={close}
            className="flex items-center gap-2 rounded-md px-2 py-2 text-sm transition-colors hover:bg-secondary"
          >
            <Settings className="h-4 w-4 text-muted-foreground" />
            {t("nav.settings")}
          </Link>
          <Button
            variant="ghost"
            size="sm"
            className="mt-0.5 w-full justify-start gap-2 text-destructive hover:text-destructive"
            onClick={handleSignOut}
          >
            <LogOut className="h-4 w-4" />
            {t("auth.signOut")}
          </Button>
        </div>
      </div>
    </details>
  );
}
