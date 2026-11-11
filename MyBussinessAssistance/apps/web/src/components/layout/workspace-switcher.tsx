import { Link } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { Building2, Plus } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { useAuth } from "@/providers/auth-provider";

export function WorkspaceSwitcher() {
  const { t } = useTranslation();
  const { workspaces, activeWorkspace, activeWorkspaceId, switchWorkspace } =
    useAuth();

  if (workspaces.length === 0) return null;

  return (
    <div className="space-y-2 px-2">
      <div className="flex items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.14em] text-muted-foreground">
        <Building2 className="h-3 w-3" />
        <span className="truncate">{t("common.workspace")}</span>
      </div>

      <div className="rounded-lg border bg-card p-2.5">
        <select
          aria-label={t("common.workspace")}
          className="w-full cursor-pointer bg-transparent text-sm font-semibold focus:outline-none"
          value={activeWorkspaceId ?? ""}
          onChange={(event) => switchWorkspace(event.target.value)}
        >
          {workspaces.map((workspace) => (
            <option key={workspace.workspaceId} value={workspace.workspaceId}>
              {workspace.name}
            </option>
          ))}
        </select>

        <div className="mt-2 flex items-center justify-between gap-2">
          <Badge variant="outline" className="text-[10px]">
            {activeWorkspace?.roleName ?? "Member"}
          </Badge>
          {activeWorkspace?.planName ? (
            <Badge variant="secondary" className="text-[10px]">
              {activeWorkspace.planName}
            </Badge>
          ) : null}
        </div>
      </div>

      <Link
        to="/onboarding"
        className="flex items-center gap-1.5 rounded-md px-1 py-1 text-xs font-medium text-muted-foreground transition-colors hover:text-foreground"
      >
        <Plus className="h-3.5 w-3.5" />
        {t("auth.createWorkspace")}
      </Link>
    </div>
  );
}
