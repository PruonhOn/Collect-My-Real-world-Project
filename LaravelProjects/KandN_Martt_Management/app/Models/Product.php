<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'brand_id',
        'unit_id',
        'name',
        'slug',
        'sku',
        'barcode',
        'description',
        'cost_price',
        'selling_price',
        'wholesale_price',
        'discount_type',
        'discount_value',
        'tax_type',
        'tax_rate',
        'min_stock',
        'max_stock',
        'current_stock',
        'image',
        'is_featured',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'wholesale_price' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'current_stock' => 'decimal:2',
            'min_stock' => 'integer',
            'max_stock' => 'integer',
            'is_featured' => 'boolean',
            'status' => 'string',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function stock()
    {
        return $this->hasOne(Stock::class);
    }

    public function saleDetails()
    {
        return $this->hasMany(SaleDetail::class);
    }

    public function purchaseDetails()
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
