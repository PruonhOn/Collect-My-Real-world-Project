<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentWebhookEvent extends Model
{
    protected $fillable = [
        'provider', 'event_id', 'event_type', 'payload', 'status',
        'attempts', 'last_error', 'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'attempts' => 'integer',
        'processed_at' => 'datetime',
    ];
}
