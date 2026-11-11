<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'barcode',
        'name',
        'description',
        'category_id',
        'cost_price',
        'selling_price',
        'quantity',
        'image',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function isLowStock()
    {
        return $this->quantity < 10;
    }
}
