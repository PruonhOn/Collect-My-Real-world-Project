<?php

namespace App\Services\Ai;

/**
 * Contract for an AI Assistant provider.
 *
 * Providers are deliberately dumb: they receive a fully built system
 * instruction plus the user's context and return plain text. All prompt
 * wording, truncation, OCR handling and authorization live above this
 * interface (see AiAssistantService and AiContextService) so that adding a
 * provider never means re-implementing the assistant's behaviour.
 */
interface AiProviderInterface
{
    /**
     * Run a single completion and return the model's text.
     *
     * @param  array{
     *     system: string,
     *     prompt: string,
     *     image_mime: string|null,
     *     image_base64: string|null,
     *     action: string,
     *     language: string
     * }  $payload
     * @return array{text: string, model: string|null}
     *
     * @throws AiProviderException
     */
    public function complete(array $payload): array;

    /**
     * Whether the provider is usable right now, i.e. it is configured with
     * the credentials it needs. Used to fail fast with a clear 503 rather
     * than surfacing a provider-specific error to the reader.
     */
    public function isConfigured(): bool;
}
