import { useAuth } from "@/providers/auth-provider";

export function useBusinessContext() {
  const { activeWorkspace, activeWorkspaceId, can } = useAuth();

  if (!activeWorkspace || !activeWorkspaceId) {
    throw new Error("A workspace must be selected before loading this page.");
  }

  return {
    workspace: activeWorkspace,
    workspaceId: activeWorkspaceId,
    can,
  };
}
