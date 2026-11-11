<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Setting;

class NotificationService
{
    public function checkLowStock(): array
    {
        $lowStockProducts = Product::where('status', 'active')
            ->whereColumn('current_stock', '<=', 'min_stock')
            ->where('min_stock', '>', 0)
            ->get(['id', 'name', 'sku', 'current_stock', 'min_stock']);

        $alerts = [];
        foreach ($lowStockProducts as $product) {
            $alerts[] = [
                'type' => 'low_stock',
                'title' => 'Low Stock Alert',
                'message' => "Product '{$product->name}' (SKU: {$product->sku}) has only {$product->current_stock} units left. Minimum stock is {$product->min_stock}.",
                'product_id' => $product->id,
            ];
        }

        return $alerts;
    }

    public function checkExpiredProducts(): array
    {
        return [];
    }

    public function getAllAlerts(): array
    {
        return array_merge(
            $this->checkLowStock(),
            $this->checkExpiredProducts()
        );
    }
}
