<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'price', 'category_id',
        'image', 'images', 'status', 'featured', 'stock',
        'size', 'color', 'notes',
    ];

    protected $casts = [
        'images' => 'array',
        'status' => 'boolean',
        'featured' => 'boolean',
        'price' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name) . '-' . uniqid();
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
