import { useEffect, useRef, useState } from "react";
import { Link } from "react-router-dom";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useTranslation } from "react-i18next";
import {
  ArrowRight,
  Bot,
  Loader2,
  Lock,
  RefreshCw,
  Send,
  ShieldAlert,
  Sparkles,
  UserRound,
} from "lucide-react";
import { PageHeader } from "@/components/app/page-header";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { Textarea } from "@/components/ui/textarea";
import { ApiClientError } from "@/lib/api";
import {
  chatWithAi,
  fetchAiStatus,
  type AiChatMessage,
} from "@/lib/business-api";
import { formatNumber } from "@/lib/money";
import { useBusinessContext } from "@/lib/use-business";

type ChatEntry = AiChatMessage & { id: number };

function errorMessage(error: unknown, fallback: string) {
  return error instanceof ApiClientError ? error.message : fallback;
}

export function AiPage() {
  const { t, i18n } = useTranslation();
  const { workspaceId, can } = useBusinessContext();
  const locale = i18n.language.startsWith("km") ? "km" : "en";
  const queryClient = useQueryClient();
  const canUse = can("ai:use");

  const [messages, setMessages] = useState<ChatEntry[]>([]);
  const [input, setInput] = useState("");
  const [error, setError] = useState<string | null>(null);
  const endRef = useRef<HTMLDivElement | null>(null);

  const statusQuery = useQuery({
    queryKey: ["ai-status", workspaceId],
    queryFn: () => fetchAiStatus(workspaceId),
    enabled: canUse,
  });

  const chatMutation = useMutation({
    mutationFn: ({
      text,
      history,
    }: {
      text: string;
      history: AiChatMessage[];
    }) =>
      chatWithAi(workspaceId, {
        message: text,
        locale,
        history,
      }),
    onSuccess: async (data) => {
      setMessages((current) => [
        ...current,
        {
          id: Date.now(),
          role: "assistant",
          content: data.reply,
        },
      ]);
      await queryClient.invalidateQueries({
        queryKey: ["ai-status", workspaceId],
      });
    },
    onError: (cause) => setError(errorMessage(cause, t("ai.error"))),
  });

  useEffect(() => {
    endRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [messages, chatMutation.isPending]);

  const status = statusQuery.data;

  if (!canUse) {
    return (
      <Card>
        <CardContent className="flex flex-col items-center gap-2 py-16 text-center">
          <Lock className="h-8 w-8 text-muted-foreground" />
          <p className="text-sm text-muted-foreground">{t("ai.denied")}</p>
        </CardContent>
      </Card>
    );
  }

  if (statusQuery.isLoading) {
    return (
      <div className="space-y-6">
        <Skeleton className="h-16 w-full" />
        <Skeleton className="h-[520px] w-full" />
      </div>
    );
  }

  if (statusQuery.isError) {
    return (
      <Card>
        <CardContent className="flex flex-col items-center gap-3 py-16 text-center">
          <ShieldAlert className="h-8 w-8 text-destructive" />
          <p className="text-sm text-muted-foreground">
            {errorMessage(statusQuery.error, t("ai.loadError"))}
          </p>
          <Button variant="outline" onClick={() => void statusQuery.refetch()}>
            <RefreshCw className="h-4 w-4" />
            {t("ai.retry")}
          </Button>
        </CardContent>
      </Card>
    );
  }

  if (status && !status.enabled) {
    return (
      <div className="space-y-6">
        <PageHeader title={t("ai.title")} description={t("ai.subtitle")} />
        <Card>
          <CardContent className="flex flex-col items-center gap-3 py-16 text-center">
            <span className="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 text-primary">
              <Sparkles className="h-7 w-7" />
            </span>
            <h3 className="text-lg font-semibold">{t("ai.upgradeTitle")}</h3>
            <p className="max-w-md text-sm text-muted-foreground">
              {t("ai.upgradeBody")}
            </p>
            <Button asChild className="mt-2">
              <Link to="/app/subscription">
                {t("ai.upgradeCta")}
                <ArrowRight className="h-4 w-4" />
              </Link>
            </Button>
          </CardContent>
        </Card>
      </div>
    );
  }

  if (status && !status.configured) {
    return (
      <div className="space-y-6">
        <PageHeader title={t("ai.title")} description={t("ai.subtitle")} />
        <Card>
          <CardContent className="flex flex-col items-center gap-3 py-16 text-center">
            <ShieldAlert className="h-8 w-8 text-accent-foreground" />
            <h3 className="text-lg font-semibold">{t("ai.notConfigured")}</h3>
            <p className="max-w-md text-sm text-muted-foreground">
              {t("ai.notConfiguredBody")}
            </p>
          </CardContent>
        </Card>
      </div>
    );
  }

  const suggestions = [
    t("ai.suggestion1"),
    t("ai.suggestion2"),
    t("ai.suggestion3"),
  ];

  function submit(text: string) {
    const trimmed = text.trim();
    if (!trimmed || chatMutation.isPending) return;

    setError(null);
    setInput("");
    setMessages((current) => [
      ...current,
      { id: Date.now(), role: "user", content: trimmed },
    ]);
    chatMutation.mutate({
      text: trimmed,
      history: messages.slice(-8),
    });
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title={t("ai.title")}
        description={t("ai.subtitle")}
        actions={
          messages.length > 0 ? (
            <Button
              variant="outline"
              onClick={() => {
                setMessages([]);
                setError(null);
                setInput("");
              }}
            >
              <RefreshCw className="h-4 w-4" />
              {t("ai.newChat")}
            </Button>
          ) : null
        }
      />

      <div className="grid gap-4 lg:grid-cols-[1fr_280px]">
        <Card className="flex min-h-[560px] flex-col">
          <CardContent className="flex flex-1 flex-col p-0">
            <div className="flex items-center justify-between gap-3 border-b px-4 py-3">
              <div className="flex items-center gap-2">
                <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10 text-primary">
                  <Sparkles className="h-4 w-4" />
                </span>
                <div>
                  <p className="text-sm font-semibold">{t("ai.assistant")}</p>
                  <p className="text-xs text-muted-foreground">
                    {t("ai.grounded")}
                  </p>
                </div>
              </div>
              <Badge variant="secondary" className="hidden sm:inline-flex">
                {status?.model}
              </Badge>
            </div>

            <div className="flex-1 space-y-4 overflow-y-auto px-4 py-5">
              {messages.length === 0 ? (
                <div className="flex gap-3">
                  <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-secondary text-secondary-foreground">
                    <Bot className="h-4 w-4" />
                  </span>
                  <div className="max-w-xl rounded-2xl rounded-tl-sm bg-secondary px-4 py-3 text-sm leading-relaxed">
                    {t("ai.greeting")}
                  </div>
                </div>
              ) : null}

              {messages.map((message) => {
                const isUser = message.role === "user";
                return (
                  <div
                    key={message.id}
                    className={`flex gap-3 ${isUser ? "justify-end" : ""}`}
                  >
                    {!isUser ? (
                      <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-secondary text-secondary-foreground">
                        <Bot className="h-4 w-4" />
                      </span>
                    ) : null}
                    <div
                      className={`max-w-xl whitespace-pre-wrap rounded-2xl px-4 py-3 text-sm leading-relaxed ${
                        isUser
                          ? "rounded-tr-sm bg-primary text-primary-foreground"
                          : "rounded-tl-sm bg-secondary text-secondary-foreground"
                      }`}
                    >
                      {message.content}
                    </div>
                    {isUser ? (
                      <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                        <UserRound className="h-4 w-4" />
                      </span>
                    ) : null}
                  </div>
                );
              })}

              {chatMutation.isPending ? (
                <div className="flex items-center gap-3 text-sm text-muted-foreground">
                  <span className="flex h-8 w-8 items-center justify-center rounded-full bg-secondary">
                    <Bot className="h-4 w-4" />
                  </span>
                  <Loader2 className="h-4 w-4 animate-spin" />
                  {t("ai.thinking")}
                </div>
              ) : null}

              {error ? (
                <div className="rounded-lg border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive">
                  {error}
                </div>
              ) : null}

              <div ref={endRef} />
            </div>

            {messages.length === 0 ? (
              <div className="flex flex-wrap gap-2 border-t px-4 py-3">
                {suggestions.map((suggestion) => (
                  <button
                    key={suggestion}
                    type="button"
                    onClick={() => submit(suggestion)}
                    className="rounded-full border bg-background px-3 py-1.5 text-xs font-medium text-muted-foreground transition-colors hover:border-primary/40 hover:text-foreground"
                  >
                    {suggestion}
                  </button>
                ))}
              </div>
            ) : null}

            <div className="border-t p-3">
              <div className="flex items-end gap-2">
                <Textarea
                  rows={2}
                  value={input}
                  onChange={(event) => setInput(event.target.value)}
                  onKeyDown={(event) => {
                    if (event.key === "Enter" && !event.shiftKey) {
                      event.preventDefault();
                      submit(input);
                    }
                  }}
                  placeholder={t("ai.placeholder")}
                  disabled={chatMutation.isPending}
                  className="min-h-[60px] resize-none"
                />
                <Button
                  size="icon"
                  className="h-[60px] w-12 shrink-0"
                  onClick={() => submit(input)}
                  disabled={!input.trim() || chatMutation.isPending}
                  aria-label={t("ai.send")}
                >
                  {chatMutation.isPending ? (
                    <Loader2 className="h-4 w-4 animate-spin" />
                  ) : (
                    <Send className="h-4 w-4" />
                  )}
                </Button>
              </div>
              <p className="mt-2 text-xs text-muted-foreground">
                {t("ai.disclaimer")}
              </p>
            </div>
          </CardContent>
        </Card>

        <div className="space-y-4">
          <Card>
            <CardContent className="space-y-3 p-4">
              <div className="flex items-center justify-between gap-2">
                <h3 className="text-sm font-semibold">{t("ai.usageTitle")}</h3>
                <Sparkles className="h-4 w-4 text-primary" />
              </div>
              {status?.creditsLimit === null ? (
                <p className="text-sm text-muted-foreground">
                  {t("ai.unlimitedCredits")}
                </p>
              ) : (
                <>
                  <p className="text-sm text-muted-foreground">
                    {t("ai.creditsUsed", {
                      used: formatNumber(status?.creditsUsed ?? 0, locale),
                      limit: formatNumber(status?.creditsLimit ?? 0, locale),
                    })}
                  </p>
                  <div className="h-2 overflow-hidden rounded-full bg-secondary">
                    <div
                      className="h-full rounded-full bg-primary transition-all"
                      style={{
                        width: `${Math.min(
                          100,
                          ((status?.creditsUsed ?? 0) /
                            Math.max(1, status?.creditsLimit ?? 1)) *
                            100,
                        )}%`,
                      }}
                    />
                  </div>
                  <p className="text-xs text-muted-foreground">
                    {t("ai.remaining", {
                      remaining: formatNumber(
                        status?.creditsRemaining ?? 0,
                        locale,
                      ),
                    })}
                  </p>
                </>
              )}
            </CardContent>
          </Card>

          <Card>
            <CardContent className="space-y-2 p-4">
              <h3 className="text-sm font-semibold">{t("ai.tipsTitle")}</h3>
              <p className="text-sm leading-relaxed text-muted-foreground">
                {t("ai.tipsBody")}
              </p>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
