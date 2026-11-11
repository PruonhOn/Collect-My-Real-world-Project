import type { Env } from "../env";
import { ApiError } from "../lib/errors";

export type ChatRole = "system" | "user" | "assistant";

export type ChatMessage = {
  role: ChatRole;
  content: string;
};

export type ChatOptions = {
  temperature?: number;
  maxTokens?: number;
};

export type ChatResult = {
  content: string;
  model: string;
  usage: {
    promptTokens: number;
    completionTokens: number;
    totalTokens: number;
  };
};

type OpenAiResponse = {
  model?: string;
  choices?: Array<{ message?: { content?: string } }>;
  usage?: {
    prompt_tokens?: number;
    completion_tokens?: number;
    total_tokens?: number;
  };
};

export async function chatCompletion(
  env: Env,
  messages: ChatMessage[],
  options: ChatOptions = {},
): Promise<ChatResult> {
  if (!env.OPENAI_API_KEY) {
    throw new ApiError(
      503,
      "ai_not_configured",
      "The AI assistant is not configured.",
    );
  }

  const baseUrl = (env.OPENAI_BASE_URL ?? "https://api.openai.com/v1").replace(
    /\/$/,
    "",
  );
  const model = env.OPENAI_MODEL ?? "gpt-4o-mini";

  const response = await fetch(`${baseUrl}/chat/completions`, {
    method: "POST",
    headers: {
      authorization: `Bearer ${env.OPENAI_API_KEY}`,
      "content-type": "application/json",
    },
    body: JSON.stringify({
      model,
      messages,
      temperature: options.temperature ?? 0.3,
      max_tokens: options.maxTokens ?? 800,
    }),
  });

  if (!response.ok) {
    const detail = await response.text();
    console.error("OpenAI request failed", response.status, detail);
    throw new ApiError(
      502,
      "ai_request_failed",
      "The AI assistant is temporarily unavailable.",
    );
  }

  const payload = (await response.json()) as OpenAiResponse;
  const content = payload.choices?.[0]?.message?.content?.trim();

  if (!content) {
    throw new ApiError(
      502,
      "ai_empty_response",
      "The AI assistant returned an empty response.",
    );
  }

  return {
    content,
    model: payload.model ?? model,
    usage: {
      promptTokens: payload.usage?.prompt_tokens ?? 0,
      completionTokens: payload.usage?.completion_tokens ?? 0,
      totalTokens: payload.usage?.total_tokens ?? 0,
    },
  };
}
