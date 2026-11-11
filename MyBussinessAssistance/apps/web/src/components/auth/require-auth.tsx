import type { ReactNode } from "react";
import { Navigate, useLocation } from "react-router-dom";
import { FullPageLoader } from "@/components/layout/full-page-loader";
import { useAuth } from "@/providers/auth-provider";

type RequireAuthProps = {
  children: ReactNode;
  requireWorkspace?: boolean;
};

export function RequireAuth({
  children,
  requireWorkspace = true,
}: RequireAuthProps) {
  const { user, workspaces, loading } = useAuth();
  const location = useLocation();

  if (loading) return <FullPageLoader />;

  if (!user) {
    return (
      <Navigate
        to="/login"
        replace
        state={{ from: `${location.pathname}${location.search}` }}
      />
    );
  }

  if (requireWorkspace && workspaces.length === 0) {
    return <Navigate to="/onboarding" replace />;
  }

  return <>{children}</>;
}

export function PublicOnly({ children }: { children: ReactNode }) {
  const { user, loading } = useAuth();

  if (loading) return <FullPageLoader />;
  if (user) return <Navigate to="/app" replace />;

  return <>{children}</>;
}
