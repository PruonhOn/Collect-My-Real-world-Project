<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'stock_id',
        'reference_type',
        'reference_id',
        'type',
        'quantity',
        'unit_price',
        'total_price',
        'before_quantity',
        'after_quantity',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'before_quantity' => 'decimal:2',
            'after_quantity' => 'decimal:2',
            'type' => 'string',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
