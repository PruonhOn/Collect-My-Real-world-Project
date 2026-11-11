import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from "react";
import { ApiClientError } from "@/lib/api";
import {
  createWorkspace as createWorkspaceRequest,
  fetchMe,
  login as loginRequest,
  logout as logoutRequest,
  register as registerRequest,
  type AuthUser,
  type MeResponse,
  type RegisterInput,
  type WorkspaceSummary,
} from "@/lib/auth-api";

const ACTIVE_WORKSPACE_KEY = "mba.workspace";

type AuthContextValue = {
  user: AuthUser | null;
  workspaces: WorkspaceSummary[];
  activeWorkspaceId: string | null;
  activeWorkspace: WorkspaceSummary | null;
  platformRole: string | null;
  loading: boolean;
  error: string | null;
  refresh: () => Promise<void>;
  signIn: (email: string, password: string) => Promise<void>;
  signUp: (
    input: RegisterInput,
  ) => Promise<{ devVerificationToken?: string }>;
  signOut: () => Promise<void>;
  switchWorkspace: (workspaceId: string) => void;
  can: (permission: string) => boolean;
  hasModule: (moduleKey: string) => boolean;
  planModules: string[];
  createWorkspace: (input: {
    name: string;
    defaultCurrency: "USD" | "KHR";
    locale: "en" | "km";
  }) => Promise<string>;
};

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<AuthUser | null>(null);
  const [workspaces, setWorkspaces] = useState<WorkspaceSummary[]>([]);
  const [platformRole, setPlatformRole] = useState<string | null>(null);
  const [activeWorkspaceId, setActiveWorkspaceId] = useState<string | null>(
    () => {
      if (typeof window === "undefined") return null;
      return window.localStorage.getItem(ACTIVE_WORKSPACE_KEY);
    },
  );
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const applyMe = useCallback((me: MeResponse | null) => {
    setUser(me?.user ?? null);
    setWorkspaces(me?.workspaces ?? []);
    setPlatformRole(me?.platformRole ?? null);

    if (!me) {
      setActiveWorkspaceId(null);
      if (typeof window !== "undefined") {
        window.localStorage.removeItem(ACTIVE_WORKSPACE_KEY);
      }
      return;
    }

    setActiveWorkspaceId((current) => {
      const valid =
        current && me.workspaces.some((item) => item.workspaceId === current)
          ? current
          : (me.workspaces[0]?.workspaceId ?? null);

      if (typeof window !== "undefined") {
        if (valid) {
          window.localStorage.setItem(ACTIVE_WORKSPACE_KEY, valid);
        } else {
          window.localStorage.removeItem(ACTIVE_WORKSPACE_KEY);
        }
      }

      return valid;
    });
  }, []);

  const refresh = useCallback(async () => {
    try {
      const me = await fetchMe();
      applyMe(me);
      setError(null);
    } catch (cause) {
      if (cause instanceof ApiClientError && cause.status === 401) {
        applyMe(null);
        setError(null);
        return;
      }
      setError(
        cause instanceof Error
          ? cause.message
          : "Unable to load your account.",
      );
    }
  }, [applyMe]);

  useEffect(() => {
    let cancelled = false;

    refresh().finally(() => {
      if (!cancelled) setLoading(false);
    });

    return () => {
      cancelled = true;
    };
  }, [refresh]);

  const signIn = useCallback(
    async (email: string, password: string) => {
      await loginRequest(email, password);
      await refresh();
    },
    [refresh],
  );

  const signUp = useCallback(
    async (input: RegisterInput) => {
      const result = await registerRequest(input);
      await refresh();
      return { devVerificationToken: result.devVerificationToken };
    },
    [refresh],
  );

  const signOut = useCallback(async () => {
    try {
      await logoutRequest();
    } catch {
      // The session may already be gone; clear local state regardless.
    }
    applyMe(null);
  }, [applyMe]);

  const switchWorkspace = useCallback((workspaceId: string) => {
    setActiveWorkspaceId(workspaceId);
    if (typeof window !== "undefined") {
      window.localStorage.setItem(ACTIVE_WORKSPACE_KEY, workspaceId);
    }
  }, []);

  const createWorkspace = useCallback(
    async (input: {
      name: string;
      defaultCurrency: "USD" | "KHR";
      locale: "en" | "km";
    }) => {
      const result = await createWorkspaceRequest(input);
      await refresh();
      switchWorkspace(result.workspace.id);
      return result.workspace.id;
    },
    [refresh, switchWorkspace],
  );

  const value = useMemo<AuthContextValue>(() => {
    const activeWorkspace =
      workspaces.find((item) => item.workspaceId === activeWorkspaceId) ??
      null;

  const can = (permission: string) =>
    activeWorkspace?.permissions?.includes(permission) ?? false;

    const planModules = activeWorkspace?.planModules ?? [];
  const hasModule = (moduleKey: string) => planModules.includes(moduleKey);

    return {
      user,
      workspaces,
      activeWorkspaceId,
      activeWorkspace,
      platformRole,
      loading,
      error,
      refresh,
      signIn,
      signUp,
      signOut,
      switchWorkspace,
      can,
      hasModule,
      planModules,
      createWorkspace,
    };
  }, [
    user,
    workspaces,
    activeWorkspaceId,
    platformRole,
    loading,
    error,
    refresh,
    signIn,
    signUp,
    signOut,
    switchWorkspace,
    createWorkspace,
  ]);

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error("useAuth must be used inside AuthProvider");
  }
  return context;
}
