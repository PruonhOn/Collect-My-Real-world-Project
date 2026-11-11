<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\SalePayment;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PosService
{
    public function searchProduct(string $query)
    {
        return Product::with(['category:id,name', 'unit:id,short_name'])
            ->where('status', 'active')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('barcode', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%");
            })
            ->get(['id', 'name', 'sku', 'barcode', 'selling_price', 'current_stock', 'image', 'unit_id', 'category_id']);
    }

    public function scanBarcode(string $barcode)
    {
        return Product::with(['category:id,name', 'unit:id,short_name'])
            ->where('barcode', $barcode)
            ->where('status', 'active')
            ->first(['id', 'name', 'sku', 'barcode', 'selling_price', 'current_stock', 'image', 'unit_id', 'category_id']);
    }

    public function createSale(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            $customerId = $data['customer_id'] ?? null;

            if (!isset($data['customer_id']) && isset($data['customer_phone'])) {
                $customer = Customer::firstOrCreate(
                    ['phone' => $data['customer_phone']],
                    [
                        'name' => $data['customer_name'] ?? 'Walk-in Customer',
                        'created_by' => auth()->id(),
                    ]
                );
                $customerId = $customer->id;
            }

            $subtotal = 0;
            $totalDiscount = 0;
            $totalTax = 0;

            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $itemSubtotal = $product->selling_price * $item['quantity'];
                $itemDiscount = $item['discount'] ?? 0;
                $itemTax = ($item['tax'] ?? 0);

                $subtotal += $itemSubtotal;
                $totalDiscount += $itemDiscount;
                $totalTax += $itemTax;
            }

            $discountType = $data['discount_type'] ?? null;
            $discountValue = $data['discount_value'] ?? 0;
            $discountAmount = $data['discount_amount'] ?? 0;

            if ($discountType === 'percentage' && $discountValue > 0) {
                $discountAmount = ($subtotal * $discountValue) / 100;
            }

            $grandTotal = $subtotal - $discountAmount + $totalTax + ($data['shipping_cost'] ?? 0);
            $paidAmount = $data['paid_amount'] ?? $grandTotal;
            $changeAmount = max(0, $paidAmount - $grandTotal);
            $dueAmount = max(0, $grandTotal - $paidAmount);

            $sale = Sale::create([
                'invoice_no' => 'INV-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                'customer_id' => $customerId,
                'user_id' => auth()->id(),
                'sale_date' => now(),
                'subtotal' => $subtotal,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'discount_amount' => $discountAmount,
                'tax_amount' => $totalTax,
                'shipping_cost' => $data['shipping_cost'] ?? 0,
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'change_amount' => $changeAmount,
                'payment_status' => $dueAmount > 0 ? ($paidAmount > 0 ? 'partial' : 'unpaid') : 'paid',
                'payment_method' => $data['payment_method'] ?? 'cash',
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);

                $itemSubtotal = $product->selling_price * $item['quantity'];
                $itemDiscount = $item['discount'] ?? 0;
                $itemTax = $item['tax'] ?? 0;

                SaleDetail::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->selling_price,
                    'discount' => $itemDiscount,
                    'tax' => $itemTax,
                    'subtotal' => $itemSubtotal - $itemDiscount + $itemTax,
                ]);

                app(InventoryService::class)->removeStock(
                    $product,
                    $item['quantity'],
                    'sale',
                    $sale->id,
                    $product->selling_price,
                    'Sale #' . $sale->invoice_no
                );
            }

            if ($paidAmount > 0) {
                SalePayment::create([
                    'sale_id' => $sale->id,
                    'payment_date' => now(),
                    'amount' => $paidAmount,
                    'payment_method' => $data['payment_method'] ?? 'cash',
                    'notes' => 'Point of Sale payment',
                    'created_by' => auth()->id(),
                ]);

                Transaction::create([
                    'transaction_no' => 'TXN-' . strtoupper(Str::random(10)),
                    'type' => 'income',
                    'amount' => $paidAmount,
                    'description' => 'Sale payment - ' . $sale->invoice_no,
                    'transaction_date' => now(),
                    'payment_method' => $data['payment_method'] ?? 'cash',
                    'reference_type' => 'sale',
                    'reference_id' => $sale->id,
                    'status' => 'completed',
                    'created_by' => auth()->id(),
                ]);
            }

            if ($customerId) {
                $customer = Customer::find($customerId);
                $customer->increment('total_purchases', $grandTotal);
                $customer->increment('points', intval($grandTotal / 100));
                $customer->update(['last_purchase_at' => now()]);
            }

            return $sale->load(['details.product', 'customer', 'payments']);
        });
    }

    public function getLowStockAlerts()
    {
        return Product::where('status', 'active')
            ->whereColumn('current_stock', '<=', 'min_stock')
            ->get(['id', 'name', 'sku', 'current_stock', 'min_stock']);
    }
}
