<?php

namespace App\Services\Ai;

/**
 * Offline driver. Returns a clearly-labelled, deterministic answer so the
 * reader assistant can be built, demonstrated and tested end to end without
 * provider credentials or network access.
 *
 * It reads the same payload as a real provider, which keeps the UI honest:
 * every action, both context paths (text layer and OCR image) and every
 * language are exercised exactly as they would be in production.
 */
class MockAiProvider implements AiProviderInterface
{
    public function isConfigured(): bool
    {
        return true;
    }

    public function complete(array $payload): array
    {
        $language = (string) $payload['language'];
        $action = (string) $payload['action'];
        $usedImage = $payload['image_base64'] !== null;

        return [
            'text' => $this->answer($action, $language, $usedImage, (string) $payload['prompt']),
            'model' => 'mock',
        ];
    }

    private function answer(string $action, string $language, bool $usedImage, string $prompt): string
    {
        $source = $usedImage
            ? 'the page image you sent (OCR mode)'
            : 'the page text';

        return implode("\n", [
            '[Demo mode] No AI provider is configured, so this is a placeholder answer.',
            '',
            "Action: {$action}",
            "Answer language: {$language}",
            "Context read from: {$source}",
            '',
            $this->preview($prompt),
        ]);
    }

    /**
     * Echoes a little of the built prompt so a developer can confirm the
     * correct page text, language and instruction actually reached the
     * provider layer.
     */
    private function preview(string $prompt): string
    {
        $lines = array_values(array_filter(
            array_map('trim', explode("\n", $prompt)),
            fn (string $line): bool => $line !== ''
        ));

        return 'Context preview: '.implode(' | ', array_slice($lines, 0, 3));
    }
}
