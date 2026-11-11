<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Feature extends Model
{
    use HasFactory;

    protected $fillable = [
        'key', 'name', 'description', 'type', 'default_enabled', 'default_limit', 'metadata',
    ];

    protected $casts = [
        'default_enabled' => 'boolean',
        'default_limit' => 'integer',
        'metadata' => 'array',
    ];

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'plan_feature')
            ->withPivot('enabled', 'limit_value', 'metadata')
            ->withTimestamps();
    }
}
