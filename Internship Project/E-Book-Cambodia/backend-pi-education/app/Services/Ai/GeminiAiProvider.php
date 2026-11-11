<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Google Gemini provider. Uses the generateContent REST API with vision
 * support so scanned PDFs (page images) can be read when the text layer is
 * missing.
 */
class GeminiAiProvider implements AiProviderInterface
{
    public function isConfigured(): bool
    {
        $key = trim((string) config('ai.gemini.api_key'));

        return $key !== '';
    }

    public function complete(array $payload): array
    {
        if (! $this->isConfigured()) {
            throw new AiProviderException('Gemini is not configured. Set GEMINI_API_KEY in your environment.');
        }

        $model = trim((string) config('ai.model', 'gemini-2.5-flash'));
        if ($model === '') {
            $model = 'gemini-2.5-flash';
        }
        $baseUrl = rtrim((string) config('ai.gemini.base_url'), '/');
        $url = $baseUrl.'/models/'.$model.':generateContent?key='.(string) config('ai.gemini.api_key');

        $parts = [
            ['text' => (string) $payload['system']."\n\n".(string) $payload['prompt']],
        ];

        if ($payload['image_base64'] !== null && $payload['image_mime'] !== null) {
            $parts[] = [
                'inline_data' => [
                    'mime_type' => (string) $payload['image_mime'],
                    'data' => (string) $payload['image_base64'],
                ],
            ];
        }

        try {
            $response = Http::asJson()
                ->timeout((int) config('ai.timeout', 30))
                ->post($url, [
                    'contents' => [
                        ['parts' => $parts],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'maxOutputTokens' => 1024,
                        'topP' => 0.95,
                    ],
                ]);
        } catch (ConnectionException $e) {
            report($e);
            throw new AiProviderException('The Gemini API is unreachable. Please try again.');
        }

        if (! $response->successful()) {
            $body = $response->json();
            $message = $body['error']['message'] ?? $response->body() ?: 'Gemini request failed.';
            throw new AiProviderException((string) $message);
        }

        $body = $response->json();
        $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';

        return [
            'text' => trim((string) $text),
            'model' => $model,
        ];
    }
}
