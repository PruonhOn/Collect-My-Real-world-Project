<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group',
        'type',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'string',
        ];
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        return $setting ? $setting->value : $default;
    }

    public static function setValue(string $key, mixed $value, ?string $group = null, ?string $type = null, ?string $description = null): self
    {
        $data = ['value' => $value];

        if ($group !== null) {
            $data['group'] = $group;
        }
        if ($type !== null) {
            $data['type'] = $type;
        }
        if ($description !== null) {
            $data['description'] = $description;
        }

        return static::updateOrCreate(['key' => $key], $data);
    }

    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }
}
