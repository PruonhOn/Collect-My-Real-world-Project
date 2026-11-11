<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Assistant
    |--------------------------------------------------------------------------
    |
    | The reader's AI Assistant (translate, search, explain, summarize, ask)
    | talks to a provider chosen here. Every provider implements
    | App\Services\Ai\AiProviderInterface, so switching provider is a config
    | change rather than a code change.
    |
    | 'mock'   → Offline driver. Returns a clearly-labelled canned answer so
    |            the reader UI can be developed and tested without any
    |            provider credentials or network access.
    | 'gemini' → Google Gemini via the generateContent API (text + vision).
    | 'openai' → Any OpenAI-compatible chat-completions endpoint, which also
    |            covers most third-party gateways. Supports vision models.
    |
    | Vision is what makes scanned PDFs work: when a page has no text layer
    | the reader sends a rendered image of the page and the provider reads it.
    | See AiContextService.
    |
    */

    'driver' => env('AI_DRIVER', 'mock'),

    /*
    | Master switch. When disabled the assistant endpoints answer 503 instead
    | of calling a provider, which is useful for turning the feature off
    | without removing it.
    */
    'enabled' => env('AI_ENABLED', true),

    /*
    | Default model for whichever driver is active. Kept in one place because
    | the mock driver ignores it.
    */
    'model' => env('AI_MODEL', 'gemini-2.5-flash'),

    /*
    | Guard rails. These bound both cost and abuse: page context is truncated
    | before it ever leaves the server, and the image payload is capped so a
    | client cannot push an arbitrarily large base64 blob at the provider.
    */
    'max_text_chars' => (int) env('AI_MAX_TEXT_CHARS', 12000),
    'max_question_chars' => (int) env('AI_MAX_QUESTION_CHARS', 500),
    'max_image_bytes' => (int) env('AI_MAX_IMAGE_BYTES', 3 * 1024 * 1024),
    'timeout' => (int) env('AI_TIMEOUT', 30),

    /*
    | Requests per minute, per authenticated user. Each assistant call can cost
    | money at the provider, so the limit is keyed to the account rather than
    | the IP to stop one user behind a shared NAT from spending everyone's
    | budget.
    */
    'rate_limit' => (int) env('AI_RATE_LIMIT', 20),

    /*
    | Languages the assistant may be asked to answer or translate into.
    | The reader UI reads this list so the language picker never drifts out of
    | sync with what the backend will accept.
    */
    'languages' => [
        'km' => 'Khmer',
        'en' => 'English',
        'zh' => 'Chinese',
        'fr' => 'French',
    ],

    /*
    | Assistant actions, in the order the reader panel lists them.
    */
    'actions' => [
        'translate',
        'search',
        'explain',
        'summarize',
        'ask',
    ],

    'gemini' => [
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'api_key' => env('GEMINI_API_KEY'),
    ],

    'openai' => [
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'api_key' => env('OPENAI_API_KEY'),
        'organization' => env('OPENAI_ORGANIZATION'),
    ],
];
