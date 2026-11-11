<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stock extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'warehouse_location',
        'quantity',
        'reserved_quantity',
        'min_quantity',
        'max_quantity',
        'reorder_level',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'reserved_quantity' => 'decimal:2',
            'min_quantity' => 'decimal:2',
            'max_quantity' => 'decimal:2',
            'reorder_level' => 'decimal:2',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function adjustments()
    {
        return $this->hasMany(StockAdjustment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
