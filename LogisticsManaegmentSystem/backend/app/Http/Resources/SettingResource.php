<?php

namespace App\Http\Resources;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A system setting, with its value cast back to the declared type.
 */
class SettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Setting $setting */
        $setting = $this->resource;

        return [
            'key' => $setting->key,
            'value' => $setting->typedValue,
            'type' => $setting->type,
            'group' => $setting->group,
            'description' => $setting->description,
            'updated_at' => $setting->updated_at?->toIso8601String(),
            'created_at' => $setting->created_at?->toIso8601String(),
        ];
    }
}
