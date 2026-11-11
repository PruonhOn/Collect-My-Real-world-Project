<?php

namespace App\Services\Ai;

use InvalidArgumentException;

/**
 * Resolves the AI provider named by config('ai.driver').
 *
 * Mirrors PaymentGatewayService: the rest of the app depends on the
 * interface, and switching provider is a config/env change.
 */
class AiManager
{
    public function __construct(
        private MockAiProvider $mock,
        private GeminiAiProvider $gemini,
        private OpenAiAiProvider $openai,
    ) {}

    public function driver(): string
    {
        return (string) config('ai.driver', 'mock');
    }

    public function provider(): AiProviderInterface
    {
        return match ($this->driver()) {
            'gemini' => $this->gemini,
            'openai' => $this->openai,
            'mock' => $this->mock,
            default => throw new InvalidArgumentException(
                "Unknown AI driver [{$this->driver()}]. Set AI_DRIVER to mock, gemini or openai."
            ),
        };
    }
}
