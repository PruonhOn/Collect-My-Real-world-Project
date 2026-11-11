<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageCounter extends Model
{
    protected $fillable = [
        'business_id', 'key', 'count', 'period', 'reset_at',
    ];

    protected $casts = [
        'count' => 'integer',
        'reset_at' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
