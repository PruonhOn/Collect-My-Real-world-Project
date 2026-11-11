<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'code',
        'name',
        'description',
        'capacity',
        'used',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'decimal:2',
            'used' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function warehouseShelves(): HasMany
    {
        return $this->hasMany(WarehouseShelf::class);
    }
}
