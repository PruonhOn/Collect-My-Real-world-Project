<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    protected $fillable = [
        'user_id', 'action', 'description', 'model_type',
        'model_id', 'properties', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'properties' => 'json',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function log(
        string $action,
        ?string $description = null,
        ?Model $model = null,
        ?array $properties = null,
    ): self {
        return static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->getKey(),
            'properties' => $properties,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
