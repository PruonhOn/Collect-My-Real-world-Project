<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    public function getAll(array $filters = [])
    {
        $query = Setting::query();

        if (!empty($filters['group'])) {
            $query->byGroup($filters['group']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('key', 'like', "%{$filters['search']}%")
                    ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        return $query->latest()->get();
    }

    public function getByGroup(string $group)
    {
        return Setting::byGroup($group)->get();
    }

    public function getValue(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
            return Setting::getValue($key, $default);
        });
    }

    public function update(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $setting = Setting::where('key', $key)->first();
            if ($setting) {
                $setting->update(['value' => $value]);
                Cache::forget("setting.{$key}");
            }
        }
    }

    public function setValue(string $key, mixed $value, ?string $group = null, ?string $type = null, ?string $description = null): Setting
    {
        $setting = Setting::setValue($key, $value, $group, $type, $description);
        Cache::forget("setting.{$key}");
        return $setting;
    }
}
