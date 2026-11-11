import { Hono } from "hono";
import { aiChatSchema } from "@mba/shared";
import type { AppEnv } from "../env";
import { ok } from "../lib/response";
import { parseJson } from "../lib/validate";
import { requirePermission } from "../middleware/workspace";
import { writeAudit } from "../services/audit";
import { chatCompletion, type ChatMessage } from "../services/ai";
import { buildAiBusinessContext } from "../services/ai-context";
import {
  assertAiCredits,
  getAiUsageStatus,
  recordAiUsage,
} from "../services/ai-usage";

export const aiRoutes = new Hono<AppEnv>();

aiRoutes.get("/", requirePermission("ai:use"), async (context) => {
  const access = context.get("workspace");
  const status = await getAiUsageStatus(
    context.env,
    access.workspace.id,
  );

  return ok(context, status);
});

aiRoutes.post("/chat", requirePermission("ai:use"), async (context) => {
  const access = context.get("workspace");
  const user = context.get("user");
  const data = await parseJson(context, aiChatSchema);
  const credits = await assertAiCredits(
    context.env,
    access.workspace.id,
  );
  const workspaceContext = await buildAiBusinessContext(
    context.env,
    access.workspace.id,
    {
      name: access.workspace.name,
      currency: access.workspace.defaultCurrency,
      locale: access.workspace.locale,
    },
  );
  const language = data.locale === "km" ? "Khmer" : "English";
  const systemPrompt = [
    "You are MyBusiness Assistant, a practical business advisor for small businesses in Cambodia.",
    "Use only the workspace snapshot below for business facts and numbers. Never invent figures or actions.",
    `Amounts are in ${access.workspace.defaultCurrency}; the snapshot stores amounts as decimal numbers, not cents.`,
    "If data is missing, say what is missing and suggest what the user can record.",
    "Give concise, actionable answers. Prefer short paragraphs and bullet points.",
    `Reply in ${language}.`,
    "",
    "Workspace snapshot:",
    workspaceContext,
  ].join("\n");
  const messages: ChatMessage[] = [
    { role: "system", content: systemPrompt },
    ...(data.history ?? []),
    { role: "user", content: data.message },
  ];

  const result = await chatCompletion(context.env, messages, {
    temperature: 0.3,
    maxTokens: 700,
  });
  const used = await recordAiUsage(context.env, access.workspace.id);
  const remaining =
    credits.creditsLimit === null
      ? null
      : Math.max(0, credits.creditsLimit - used);

  await writeAudit(context.env, {
    workspaceId: access.workspace.id,
    actorUserId: user.id,
    action: "ai.chat",
    entityType: "ai",
    metadata: {
      model: result.model,
      promptTokens: result.usage.promptTokens,
      completionTokens: result.usage.completionTokens,
      totalTokens: result.usage.totalTokens,
      messageLength: data.message.length,
    },
  });

  return ok(context, {
    reply: result.content,
    model: result.model,
    usage: result.usage,
    credits: {
      used,
      limit: credits.creditsLimit,
      remaining,
    },
  });
});
