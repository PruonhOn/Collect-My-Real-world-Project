<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    public const CACHE_KEY = 'app_settings';

    public static function get(string $key, ?string $default = null): ?string
    {
        return self::allSettings()[$key] ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value]);
        self::flushCache();
    }

    public static function setMany(array $settings): void
    {
        foreach ($settings as $key => $value) {
            self::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        self::flushCache();
    }

    public static function allSettings(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return self::query()->pluck('value', 'key')->all();
        });
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
