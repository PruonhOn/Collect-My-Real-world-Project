<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'size',
        'color',
        'quantity',
        'price_adjustment',
    ];

    protected function casts(): array
    {
        return [
            'price_adjustment' => 'decimal:2',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class, 'product_variant_id');
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class, 'product_variant_id');
    }
}
