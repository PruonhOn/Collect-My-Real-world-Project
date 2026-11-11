<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function generateInvoiceNumber(): string
    {
        $prefix = 'INV-';
        $date = now()->format('Ymd');
        $lastSale = Sale::where('invoice_number', 'like', $prefix . $date . '-%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastSale) {
            $lastNumber = (int) substr($lastSale->invoice_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $date . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function processSale(array $cart, float $paidAmount, string $paymentMethod = 'cash'): Sale
    {
        return DB::transaction(function () use ($cart, $paidAmount, $paymentMethod) {
            $totalAmount = 0;

            foreach ($cart as $item) {
                $product = Product::findOrFail($item['product_id']);
                if ($product->quantity < $item['quantity']) {
                    throw new \Exception("Insufficient stock for product: {$product->name}");
                }
                $totalAmount += $item['quantity'] * $product->selling_price;
            }

            $changeAmount = $paidAmount - $totalAmount;

            $sale = Sale::create([
                'invoice_number' => $this->generateInvoiceNumber(),
                'user_id' => auth()->id(),
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'change_amount' => max($changeAmount, 0),
                'payment_method' => $paymentMethod,
            ]);

            foreach ($cart as $item) {
                $product = Product::findOrFail($item['product_id']);
                $unitPrice = $product->selling_price;

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'total_price' => $item['quantity'] * $unitPrice,
                ]);

                $product->decrement('quantity', $item['quantity']);
            }

            return $sale->load('items.product');
        });
    }

    public function getSalesReport($period = 'daily')
    {
        $query = Sale::query();

        switch ($period) {
            case 'daily':
                $query->whereDate('created_at', today());
                break;
            case 'weekly':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'monthly':
                $query->whereMonth('created_at', now()->month)
                      ->whereYear('created_at', now()->year);
                break;
        }

        return $query;
    }
}
