<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * OpenAI-compatible provider. Any endpoint that speaks the chat-completions
 * API works, which covers OpenAI, OpenRouter, Groq, Ollama's compatible
 * surface, and most gateways. Vision models are supported for scanned PDFs.
 */
class OpenAiAiProvider implements AiProviderInterface
{
    public function isConfigured(): bool
    {
        $key = trim((string) config('ai.openai.api_key'));

        return $key !== '';
    }

    public function complete(array $payload): array
    {
        if (! $this->isConfigured()) {
            throw new AiProviderException('The OpenAI-compatible provider is not configured. Set OPENAI_API_KEY in your environment.');
        }

        $model = trim((string) config('ai.model'));
        $baseUrl = rtrim((string) config('ai.openai.base_url', 'https://api.openai.com/v1'), '/');

        $messages = [
            ['role' => 'system', 'content' => (string) $payload['system']],
        ];

        if ($payload['image_base64'] !== null && $payload['image_mime'] !== null) {
            $messages[] = [
                'role' => 'user',
                'content' => [
                    ['type' => 'text', 'text' => (string) $payload['prompt']],
                    [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => 'data:'.(string) $payload['image_mime'].';base64,'.(string) $payload['image_base64'],
                        ],
                    ],
                ],
            ];
        } else {
            $messages[] = ['role' => 'user', 'content' => (string) $payload['prompt']];
        }

        $body = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.2,
            'max_tokens' => 1024,
        ];

        $request = Http::asJson()
            ->withToken((string) config('ai.openai.api_key'))
            ->acceptJson()
            ->timeout((int) config('ai.timeout', 30));

        if (config('ai.openai.organization')) {
            $request = $request->withHeader('OpenAI-Organization', (string) config('ai.openai.organization'));
        }

        try {
            $response = $request->post($baseUrl.'/chat/completions', $body);
        } catch (ConnectionException $e) {
            report($e);
            throw new AiProviderException('The AI service is unreachable. Please try again.');
        }

        if (! $response->successful()) {
            $error = $response->json('error.message') ?? $response->body() ?: 'AI request failed.';
            throw new AiProviderException((string) $error);
        }

        $text = $response->json('choices.0.message.content') ?? '';

        return [
            'text' => trim((string) $text),
            'model' => $model,
        ];
    }
}
