<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_id',
        'warehouse_id',
        'type',
        'quantity_before',
        'quantity_after',
        'quantity_adjusted',
        'reason',
        'notes',
        'adjusted_by',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity_before' => 'integer',
            'quantity_after' => 'integer',
            'quantity_adjusted' => 'integer',
        ];
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function adjustedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
