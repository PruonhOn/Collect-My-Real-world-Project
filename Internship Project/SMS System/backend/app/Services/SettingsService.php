<?php

namespace App\Services;

use App\Models\SchoolSetting;
use Illuminate\Support\Facades\Cache;

final class SettingsService
{
    private const CACHE_KEY = 'sms.settings.all';

    /**
     * All settings as nested array, cached to avoid a query per request.
     *
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return SchoolSetting::query()
                ->get()
                ->groupBy('group')
                ->map(fn ($items) => $items->mapWithKeys(fn (SchoolSetting $s) => [$s->key => $this->decode($s->value)]))
                ->all();
        });
    }

    public function get(string $key, ?string $group = null, mixed $default = null): mixed
    {
        $defaultGroup = $group ?? 'general';
        $all = $this->all();

        return $all[$defaultGroup][$key] ?? $default;
    }

    public function set(string $key, mixed $value, string $group = 'general', bool $isPublic = false): void
    {
        SchoolSetting::updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => is_scalar($value) || $value === null ? (string) ($value ?? '') : json_encode($value), 'is_public' => $isPublic],
        );

        Cache::forget(self::CACHE_KEY);
    }

    public function forget(string $key, string $group = 'general'): void
    {
        SchoolSetting::query()
            ->where('group', $group)
            ->where('key', $key)
            ->delete();

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Settings flagged as safe to expose on the public website.
     *
     * @return array<string, mixed>
     */
    public function publicSettings(): array
    {
        return Cache::remember('sms.settings.public', 3600, function () {
            return SchoolSetting::query()
                ->where('is_public', true)
                ->get()
                ->mapWithKeys(fn (SchoolSetting $s) => [$s->key => $this->decode($s->value)])
                ->all();
        });
    }

    private function decode(?string $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return $decoded === null && json_last_error() !== JSON_ERROR_NONE ? $value : $decoded;
    }
}
