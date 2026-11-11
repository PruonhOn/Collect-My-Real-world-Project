<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\SettingResource;
use App\Models\Setting;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * System settings: keyed values an administrator can read and change.
 *
 * Read/update only. Settings are created by the installer or by seeders, not
 * through this screen, so there is no create or delete.
 */
class SettingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Setting::query()->orderBy('group')->orderBy('key');

        $group = $request->query('group');
        if ($group !== null && $group !== '') {
            $query->where('group', $group);
        }

        $settings = $query->get()->map(fn (Setting $setting): array => new SettingResource($setting)->resolve());

        return ApiResponse::success($settings->values()->all(), 'Settings retrieved.');
    }

    public function show(Request $request, string $key): JsonResponse
    {
        $setting = Setting::query()->where('key', $key)->firstOrFail();

        return ApiResponse::success(new SettingResource($setting)->resolve(), 'Setting retrieved.');
    }

    public function update(Request $request, string $key): JsonResponse
    {
        $setting = Setting::query()->where('key', $key)->firstOrFail();

        $validated = $request->validate([
            'value' => ['sometimes', 'nullable', 'string'],
            'type' => ['sometimes', 'string', 'in:string,boolean,integer,decimal,json'],
            'group' => ['sometimes', 'string', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string'],
        ]);

        $setting->fill($validated)->save();

        return ApiResponse::success(
            new SettingResource($setting->fresh())->resolve(),
            'Setting updated.',
        );
    }
}
