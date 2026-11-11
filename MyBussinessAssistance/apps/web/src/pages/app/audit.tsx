import { useDeferredValue, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { useTranslation } from "react-i18next";
import { History, Loader2, Search } from "lucide-react";
import { PageHeader } from "@/components/app/page-header";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { ApiClientError } from "@/lib/api";
import { fetchAuditLogs } from "@/lib/business-api";
import { formatDateTime } from "@/lib/format";
import { useBusinessContext } from "@/lib/use-business";

function errorMessage(error: unknown, fallback: string) {
  return error instanceof ApiClientError ? error.message : fallback;
}

export function AuditPage() {
  const { t, i18n } = useTranslation();
  const { workspaceId, can } = useBusinessContext();
  const locale = i18n.language.startsWith("km") ? "km" : "en";
  const [search, setSearch] = useState("");
  const [entityType, setEntityType] = useState("");
  const deferredSearch = useDeferredValue(search);
  const deferredEntityType = useDeferredValue(entityType);
  const canRead = can("settings:read");

  const auditQuery = useQuery({
    queryKey: [
      "audit-logs",
      workspaceId,
      deferredSearch,
      deferredEntityType,
    ],
    queryFn: () =>
      fetchAuditLogs(workspaceId, {
        limit: 100,
        search: deferredSearch || undefined,
        entityType: deferredEntityType || undefined,
      }),
    enabled: canRead,
  });

  if (!canRead) {
    return (
      <Card>
        <CardContent className="flex flex-col items-center gap-2 py-16 text-center">
          <History className="h-8 w-8 text-muted-foreground" />
          <p className="text-sm text-muted-foreground">{t("audit.denied")}</p>
        </CardContent>
      </Card>
    );
  }

  const logs = auditQuery.data?.logs ?? [];

  return (
    <div className="space-y-6">
      <PageHeader
        title={t("audit.title")}
        description={t("audit.subtitle")}
        actions={
          <Button
            variant="outline"
            onClick={() => void auditQuery.refetch()}
            disabled={auditQuery.isFetching}
          >
            {auditQuery.isFetching ? (
              <Loader2 className="h-4 w-4 animate-spin" />
            ) : (
              <History className="h-4 w-4" />
            )}
            {t("audit.refresh")}
          </Button>
        }
      />

      <Card>
        <CardContent className="grid gap-3 p-4 sm:grid-cols-2">
          <div className="relative">
            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              className="pl-9"
              value={search}
              onChange={(event) => setSearch(event.target.value)}
              placeholder={t("audit.searchPlaceholder")}
            />
          </div>
          <Input
            value={entityType}
            onChange={(event) => setEntityType(event.target.value)}
            placeholder={t("audit.entityPlaceholder")}
          />
        </CardContent>
      </Card>

      <Card>
        <CardContent className="p-0">
          {auditQuery.isLoading ? (
            <div className="space-y-3 p-5">
              {[0, 1, 2, 3, 4].map((index) => (
                <Skeleton key={index} className="h-12 w-full" />
              ))}
            </div>
          ) : auditQuery.isError ? (
            <p className="p-6 text-sm text-destructive">
              {errorMessage(auditQuery.error, t("audit.loadError"))}
            </p>
          ) : logs.length === 0 ? (
            <p className="p-10 text-center text-sm text-muted-foreground">
              {t("audit.empty")}
            </p>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>{t("audit.when")}</TableHead>
                  <TableHead>{t("audit.action")}</TableHead>
                  <TableHead>{t("audit.entity")}</TableHead>
                  <TableHead>{t("audit.actor")}</TableHead>
                  <TableHead>{t("audit.details")}</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {logs.map((log) => (
                  <TableRow key={log.id}>
                    <TableCell className="whitespace-nowrap text-xs text-muted-foreground">
                      {formatDateTime(log.createdAt, locale)}
                    </TableCell>
                    <TableCell>
                      <Badge variant="secondary">{log.action}</Badge>
                    </TableCell>
                    <TableCell className="text-xs">
                      {log.entityType ? (
                        <div className="space-y-1">
                          <Badge variant="outline">{log.entityType}</Badge>
                          {log.entityId ? (
                            <p className="max-w-40 truncate text-muted-foreground">
                              {log.entityId}
                            </p>
                          ) : null}
                        </div>
                      ) : (
                        "—"
                      )}
                    </TableCell>
                    <TableCell className="text-xs">
                      {log.actorName || log.actorEmail ? (
                        <div>
                          <p className="font-medium">
                            {log.actorName ?? log.actorEmail}
                          </p>
                          {log.actorName && log.actorEmail ? (
                            <p className="text-muted-foreground">
                              {log.actorEmail}
                            </p>
                          ) : null}
                        </div>
                      ) : (
                        <span className="text-muted-foreground">
                          {t("audit.system")}
                        </span>
                      )}
                    </TableCell>
                    <TableCell className="min-w-56">
                      {log.metadata ? (
                        <pre className="max-h-24 max-w-xs overflow-auto whitespace-pre-wrap break-words rounded bg-secondary/50 p-2 text-[11px] text-muted-foreground">
                          {JSON.stringify(log.metadata, null, 2)}
                        </pre>
                      ) : (
                        <span className="text-xs text-muted-foreground">—</span>
                      )}
                      {log.ip ? (
                        <p className="mt-1 text-[10px] text-muted-foreground">
                          {t("audit.ip")}: {log.ip}
                        </p>
                      ) : null}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
