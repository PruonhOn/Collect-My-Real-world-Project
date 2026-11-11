<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\Settings\UpdateSettingsRequest;
use App\Services\AuditService;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends BaseApiController
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->settings->all(),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        foreach ($request->input('settings', []) as $setting) {
            $this->settings->set(
                $setting['key'],
                $setting['value'] ?? null,
                $setting['group'] ?? 'general',
                (bool) ($setting['is_public'] ?? false),
            );
        }

        $this->audit->record('settings.updated', null, after: $request->input('settings'));

        return response()->json([
            'data' => $this->settings->all(),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function delete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:120'],
            'group' => ['nullable', 'string', 'max:60'],
        ]);

        $this->settings->forget($validated['key'], $validated['group'] ?? 'general');
        $this->audit->record('settings.deleted', null, after: $validated);

        return response()->json([
            'data' => ['message' => 'Setting removed.'],
            'meta' => null,
            'errors' => null,
        ]);
    }
}
