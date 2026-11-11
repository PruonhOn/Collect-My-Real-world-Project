<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryStop extends Model
{
    protected $fillable = [
        'sequence_order',
        'type',
        'address',
        'city',
        'contact_name',
        'contact_phone',
        'scheduled_time',
        'arrived_at',
        'departed_at',
        'status',
        'notes',
        'delivery_id',
    ];

    protected function casts(): array
    {
        return [
            'sequence_order' => 'integer',
            'scheduled_time' => 'datetime',
            'arrived_at' => 'datetime',
            'departed_at' => 'datetime',
        ];
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }
}
