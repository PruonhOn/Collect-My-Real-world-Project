import { useEffect, useRef, useState } from "react";
import { Link } from "react-router-dom";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useTranslation } from "react-i18next";
import { Bell, CheckCheck, Inbox, Loader2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";
import {
  fetchNotifications,
  markAllNotificationsRead,
  markNotificationRead,
} from "@/lib/business-api";
import { formatDateTime } from "@/lib/format";
import { useAuth } from "@/providers/auth-provider";

function notificationTarget(type: string) {
  if (type.startsWith("order.")) return "/app/orders";
  if (type.startsWith("transfer.")) return "/app/transfers";
  return "/app";
}

export function NotificationBell() {
  const { t, i18n } = useTranslation();
  const { activeWorkspaceId, can } = useAuth();
  const queryClient = useQueryClient();
  const [open, setOpen] = useState(false);
  const rootRef = useRef<HTMLDivElement>(null);
  const locale = i18n.language.startsWith("km") ? "km" : "en";
  const enabled = Boolean(activeWorkspaceId) && can("notification:read");

  const notificationsQuery = useQuery({
    queryKey: ["notifications", activeWorkspaceId],
    queryFn: () => fetchNotifications(activeWorkspaceId as string, { limit: 20 }),
    enabled,
    refetchInterval: 60_000,
  });

  const markRead = useMutation({
    mutationFn: (notificationId: string) =>
      markNotificationRead(activeWorkspaceId as string, notificationId),
    onSuccess: () =>
      void queryClient.invalidateQueries({
        queryKey: ["notifications", activeWorkspaceId],
      }),
  });

  const markAllRead = useMutation({
    mutationFn: () => markAllNotificationsRead(activeWorkspaceId as string),
    onSuccess: () =>
      void queryClient.invalidateQueries({
        queryKey: ["notifications", activeWorkspaceId],
      }),
  });

  useEffect(() => {
    if (!open) return;
    const handlePointerDown = (event: MouseEvent) => {
      if (!rootRef.current?.contains(event.target as Node)) {
        setOpen(false);
      }
    };
    document.addEventListener("mousedown", handlePointerDown);
    return () => document.removeEventListener("mousedown", handlePointerDown);
  }, [open]);

  if (!enabled) return null;

  const unreadCount = notificationsQuery.data?.unreadCount ?? 0;
  const notifications = notificationsQuery.data?.notifications ?? [];

  return (
    <div className="relative" ref={rootRef}>
      <Button
        variant="ghost"
        size="icon"
        aria-label={t("notifications.title")}
        aria-expanded={open}
        onClick={() => setOpen((current) => !current)}
        className="relative"
      >
        <Bell className="h-5 w-5" />
        {unreadCount > 0 ? (
          <span className="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-semibold text-primary-foreground">
            {unreadCount > 99 ? "99+" : unreadCount}
          </span>
        ) : null}
      </Button>

      {open ? (
        <div className="absolute right-0 top-full z-40 mt-2 w-80 overflow-hidden rounded-xl border bg-card shadow-xl">
          <div className="flex items-center justify-between border-b px-4 py-3">
            <div>
              <p className="text-sm font-semibold">{t("notifications.title")}</p>
              <p className="text-xs text-muted-foreground">
                {t("notifications.unread", { count: unreadCount })}
              </p>
            </div>
            <Button
              variant="ghost"
              size="sm"
              className="h-8 gap-1 text-xs"
              disabled={unreadCount === 0 || markAllRead.isPending}
              onClick={() => markAllRead.mutate()}
            >
              {markAllRead.isPending ? (
                <Loader2 className="h-3.5 w-3.5 animate-spin" />
              ) : (
                <CheckCheck className="h-3.5 w-3.5" />
              )}
              {t("notifications.markAllRead")}
            </Button>
          </div>

          <div className="max-h-96 overflow-y-auto">
            {notificationsQuery.isLoading ? (
              <div className="flex items-center justify-center gap-2 px-4 py-10 text-sm text-muted-foreground">
                <Loader2 className="h-4 w-4 animate-spin" />
                {t("common.loading")}
              </div>
            ) : notificationsQuery.isError ? (
              <p className="px-4 py-8 text-center text-sm text-destructive">
                {t("notifications.loadError")}
              </p>
            ) : notifications.length === 0 ? (
              <div className="flex flex-col items-center gap-2 px-4 py-10 text-center">
                <Inbox className="h-7 w-7 text-muted-foreground" />
                <p className="text-sm text-muted-foreground">
                  {t("notifications.empty")}
                </p>
              </div>
            ) : (
              notifications.map((notification) => {
                const unread = !notification.readAt;
                return (
                  <Link
                    key={notification.id}
                    to={notificationTarget(notification.type)}
                    onClick={() => {
                      setOpen(false);
                      if (unread) markRead.mutate(notification.id);
                    }}
                    className={cn(
                      "block border-b px-4 py-3 transition-colors last:border-b-0 hover:bg-secondary/60",
                      unread && "bg-primary/5",
                    )}
                  >
                    <div className="flex items-start gap-2.5">
                      <span
                        className={cn(
                          "mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full",
                          unread ? "bg-primary" : "bg-transparent",
                        )}
                      />
                      <div className="min-w-0 flex-1">
                        <p
                          className={cn(
                            "truncate text-sm",
                            unread ? "font-semibold" : "font-medium",
                          )}
                        >
                          {notification.title}
                        </p>
                        {notification.body ? (
                          <p className="mt-0.5 line-clamp-2 text-xs text-muted-foreground">
                            {notification.body}
                          </p>
                        ) : null}
                        <p className="mt-1 text-[10px] text-muted-foreground">
                          {formatDateTime(notification.createdAt, locale)}
                        </p>
                      </div>
                    </div>
                  </Link>
                );
              })
            )}
          </div>
        </div>
      ) : null}
    </div>
  );
}
