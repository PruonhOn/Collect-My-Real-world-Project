<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'warehouse_id',
        'warehouse_shelf_id',
        'package_number',
        'barcode',
        'qr_code',
        'type',
        'weight',
        'volume',
        'length',
        'width',
        'height',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'volume' => 'decimal:2',
            'length' => 'decimal:2',
            'width' => 'decimal:2',
            'height' => 'decimal:2',
        ];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function warehouseShelf(): BelongsTo
    {
        return $this->belongsTo(WarehouseShelf::class);
    }
}
