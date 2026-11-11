<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentItem extends Model
{
    protected $fillable = [
        'name',
        'description',
        'quantity',
        'weight',
        'volume',
        'value',
        'hs_code',
        'shipment_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'weight' => 'decimal:2',
            'volume' => 'decimal:2',
            'value' => 'decimal:2',
        ];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}
