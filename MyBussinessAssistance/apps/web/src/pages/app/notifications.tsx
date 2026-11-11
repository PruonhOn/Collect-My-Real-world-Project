import { useMemo, useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useTranslation } from "react-i18next";
import { Bell, Check, Loader2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { EmptyState } from "@/components/app/empty-state";
import { PageHeader } from "@/components/app/page-header";
import {
  fetchNotifications,
  markAllNotificationsRead,
  markNotificationRead,
} from "@/lib/business-api";
import { formatDateTime } from "@/lib/format";
import { useBusinessContext } from "@/lib/use-business";

function errorMessage(error: unknown) {
  return error instanceof Error ? error.message : "Unable to load notifications.";
}

export function NotificationsPage() {
  const { t, i18n } = useTranslation();
  const { workspaceId } = useBusinessContext();
  const locale = i18n.language.startsWith("km") ? "km" : "en";
  const queryClient = useQueryClient();

  const notificationsQuery = useQuery({
    queryKey: ["notifications", workspaceId],
    queryFn: () => fetchNotifications(workspaceId, { limit: 100 }),
  });

  const [actionError, setActionError] = useState<string | null>(null);
  const markRead = useMutation({
    mutationFn: (notificationId: string) =>
      markNotificationRead(workspaceId, notificationId),
    onSuccess: () => {
      setActionError(null);
      void queryClient.invalidateQueries({
        queryKey: ["notifications", workspaceId],
      });
    },
    onError: (cause: unknown) =>
      setActionError(
        cause instanceof Error ? cause.message : "Unable to mark as read.",
      ),
  });

  const markAllRead = useMutation({
    mutationFn: () => markAllNotificationsRead(workspaceId),
    onSuccess: () => {
      setActionError(null);
      void queryClient.invalidateQueries({
        queryKey: ["notifications", workspaceId],
      });
    },
    onError: (cause: unknown) =>
      setActionError(
        cause instanceof Error ? cause.message : "Unable to mark all as read.",
      ),
  });

  const data = notificationsQuery.data;
  const notifications = data?.notifications ?? [];
  const unreadCount = data?.unreadCount ?? 0;
  const unreadIds = useMemo(
    () => new Set(notifications.filter((n) => !n.readAt).map((n) => n.id)),
    [notifications],
  );

  const handleMarkRead = (id: string) => {
    if (unreadIds.has(id)) void markRead.mutate(id);
  };

  const handleMarkAllRead = () => {
    if (unreadCount === 0) return;
    void markAllRead.mutate();
  };

  return (
    <div className="space-y-6">
      <PageHeader
        title={t("nav.notifications")}
        description={t("business.notifications.subtitle")}
        actions={
          unreadCount > 0 ? (
            <Button
              onClick={handleMarkAllRead}
              disabled={markAllRead.isPending}
              variant="outline"
              size="sm"
            >
              {markAllRead.isPending ? (
                <Loader2 className="h-3.5 w-3.5 animate-spin" />
              ) : (
                <Check className="h-3.5 w-3.5" />
              )}
              {t("business.notifications.markAllRead")}
            </Button>
          ) : null
        }
      />

      {actionError ? (
        <div className="rounded-md border border-destructive/30 bg-destructive/10 px-4 py-2 text-sm text-destructive">
          {actionError}
        </div>
      ) : null}

      <div className="grid gap-4 sm:grid-cols-12">
        <div className="lg:col-span-12">
          <Card>
            <CardContent className="p-0">
              {notificationsQuery.isLoading ? (
                <div className="flex items-center justify-center gap-2 px-4 py-16 text-sm text-muted-foreground">
                  <Loader2 className="h-4 w-4 animate-spin" />
                  {t("common.loading")}
                </div>
              ) : notificationsQuery.isError ? (
                <Card>
                  <CardContent className="p-8 text-center text-sm text-destructive">
                    {errorMessage(notificationsQuery.error)}
                  </CardContent>
                </Card>
              ) : notifications.length === 0 ? (
                <EmptyState
                  icon={Bell}
                  title={t("business.notifications.emptyTitle")}
                  description={t("business.notifications.emptyBody")}
                />
              ) : (
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>{t("business.notifications.title")}</TableHead>
                      <TableHead>{t("business.notifications.when")}</TableHead>
                      <TableHead>{t("common.status")}</TableHead>
                      <TableHead className="text-right">{t("common.actions")}</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {notifications.map((notification) => {
                      const unread = !notification.readAt;
                      return (
                        <TableRow
                          key={notification.id}
                          className={unread ? "bg-primary/5" : ""}
                          onClick={() => handleMarkRead(notification.id)}
                          style={{ cursor: "pointer" }}
                        >
                          <TableCell className="font-medium truncate max-w-xs">
                            {notification.title}
                          </TableCell>
                          <TableCell className="text-xs text-muted-foreground">
                            {formatDateTime(notification.createdAt, locale)}
                          </TableCell>
                          <TableCell>
                            <Badge variant={unread ? "default" : "secondary"}>
                              {unread
                                ? t("business.notifications.unread")
                                : t("business.notifications.read")}
                            </Badge>
                          </TableCell>
                          <TableCell className="text-right">
                            <Button
                              size="icon"
                              variant="outline"
                              onClick={(event) => {
                                event.stopPropagation();
                                handleMarkRead(notification.id);
                              }}
                              disabled={!unread || markRead.isPending}
                            >
                              {unread ? (
                                <Bell className="h-3 w-3" />
                              ) : (
                                <Check className="h-3 w-3" />
                              )}
                            </Button>
                          </TableCell>
                        </TableRow>
                      );
                    })}
                  </TableBody>
                </Table>
              )}
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
