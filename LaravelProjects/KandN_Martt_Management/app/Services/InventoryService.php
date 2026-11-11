<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StockAdjustment;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function addStock(Product $product, float $quantity, string $referenceType = null, int $referenceId = null, float $unitPrice = null, string $notes = null): Stock
    {
        return DB::transaction(function () use ($product, $quantity, $referenceType, $referenceId, $unitPrice, $notes) {
            $stock = Stock::firstOrCreate(
                ['product_id' => $product->id],
                [
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                    'min_quantity' => $product->min_stock,
                    'max_quantity' => $product->max_stock,
                    'reorder_level' => $product->min_stock,
                    'created_by' => auth()->id(),
                ]
            );

            $beforeQty = $stock->quantity;
            $stock->increment('quantity', $quantity);

            StockMovement::create([
                'product_id' => $product->id,
                'stock_id' => $stock->id,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'type' => 'in',
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $unitPrice ? $unitPrice * $quantity : null,
                'before_quantity' => $beforeQty,
                'after_quantity' => $stock->quantity,
                'notes' => $notes,
                'created_by' => auth()->id(),
            ]);

            $product->increment('current_stock', $quantity);

            return $stock;
        });
    }

    public function removeStock(Product $product, float $quantity, string $referenceType = null, int $referenceId = null, float $unitPrice = null, string $notes = null): Stock
    {
        return DB::transaction(function () use ($product, $quantity, $referenceType, $referenceId, $unitPrice, $notes) {
            $stock = Stock::where('product_id', $product->id)->firstOrFail();

            if ($stock->quantity < $quantity) {
                throw new \Exception('Insufficient stock');
            }

            $beforeQty = $stock->quantity;
            $stock->decrement('quantity', $quantity);

            StockMovement::create([
                'product_id' => $product->id,
                'stock_id' => $stock->id,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'type' => 'out',
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $unitPrice ? $unitPrice * $quantity : null,
                'before_quantity' => $beforeQty,
                'after_quantity' => $stock->quantity,
                'notes' => $notes,
                'created_by' => auth()->id(),
            ]);

            $product->decrement('current_stock', $quantity);

            return $stock;
        });
    }

    public function adjustStock(Product $product, float $quantity, string $type, string $reason, string $notes = null): StockAdjustment
    {
        return DB::transaction(function () use ($product, $quantity, $type, $reason, $notes) {
            $stock = Stock::firstOrCreate(
                ['product_id' => $product->id],
                [
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                    'min_quantity' => $product->min_stock,
                    'max_quantity' => $product->max_stock,
                    'reorder_level' => $product->min_stock,
                    'created_by' => auth()->id(),
                ]
            );

            $beforeQty = $stock->quantity;

            $adjustment = StockAdjustment::create([
                'product_id' => $product->id,
                'stock_id' => $stock->id,
                'type' => $type,
                'quantity' => $quantity,
                'before_quantity' => $beforeQty,
                'after_quantity' => $type === 'addition' ? $beforeQty + $quantity : $beforeQty - $quantity,
                'reason' => $reason,
                'notes' => $notes,
                'created_by' => auth()->id(),
            ]);

            if ($type === 'addition') {
                $stock->increment('quantity', $quantity);
                $product->increment('current_stock', $quantity);
            } else {
                $stock->decrement('quantity', $quantity);
                $product->decrement('current_stock', $quantity);
            }

            StockMovement::create([
                'product_id' => $product->id,
                'stock_id' => $stock->id,
                'reference_type' => 'adjustment',
                'reference_id' => $adjustment->id,
                'type' => $type === 'addition' ? 'in' : 'out',
                'quantity' => $quantity,
                'before_quantity' => $beforeQty,
                'after_quantity' => $stock->quantity,
                'notes' => $notes ?? $reason,
                'created_by' => auth()->id(),
            ]);

            return $adjustment;
        });
    }

    public function getLowStockProducts()
    {
        return Product::whereColumn('current_stock', '<=', 'min_stock')
            ->where('status', 'active')
            ->get();
    }

    public function getStockMovements(int $productId = null)
    {
        $query = StockMovement::with(['product:id,name,sku', 'creator:id,name']);

        if ($productId) {
            $query->where('product_id', $productId);
        }

        return $query->latest()->paginate(20);
    }
}
