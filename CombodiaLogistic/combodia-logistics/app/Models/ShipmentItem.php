<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'name',
        'description',
        'quantity',
        'weight',
        'volume',
        'length',
        'width',
        'height',
        'unit',
        'declared_value',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'weight' => 'decimal:2',
            'volume' => 'decimal:2',
            'length' => 'decimal:2',
            'width' => 'decimal:2',
            'height' => 'decimal:2',
            'declared_value' => 'decimal:2',
        ];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}
