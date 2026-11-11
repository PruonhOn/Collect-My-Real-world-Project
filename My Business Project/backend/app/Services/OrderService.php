<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\Notifications\LowStockAlert;
use App\Services\Features\FeatureGate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Throwable;

class OrderService
{
    public function __construct(protected FeatureGate $gate) {}

    /**
     * Create an order with server-side calculation of all monetary totals.
     *
     * @param  array<int, array{product_id:int, quantity:int}>  $items
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $items, array $attributes = []): Order
    {
        $business = app('current.business');

        if (! $business) {
            throw new RuntimeException('Business context is required to create an order.');
        }

        if (! $this->gate->checkLimit($business, 'max_orders_monthly')) {
            throw new RuntimeException('Monthly order limit reached for your current plan.');
        }

        return DB::transaction(function () use ($business, $items, $attributes) {
            $subtotal = 0;
            $prepared = [];

            foreach ($items as $item) {
                $product = Product::withoutGlobalScopes()
                    ->where('business_id', $business->id)
                    ->findOrFail($item['product_id']);

                $quantity = (int) $item['quantity'];
                if ($quantity < 1) {
                    throw new RuntimeException('Quantity must be at least 1.');
                }

                // Price snapshot is always taken from the server-side product record.
                $unitPrice = (int) $product->price;
                $lineTotal = $unitPrice * $quantity;

                $subtotal += $lineTotal;

                $prepared[] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $lineTotal,
                    'track_inventory' => (bool) $product->track_inventory,
                    'stock_quantity' => (int) $product->stock_quantity,
                ];
            }

            $discount = (int) ($attributes['discount'] ?? 0);
            $tax = (int) ($attributes['tax'] ?? 0);

            if ($discount < 0 || $tax < 0) {
                throw new RuntimeException('Discount and tax cannot be negative.');
            }

            $customerId = $attributes['customer_id'] ?? null;

            if ($customerId !== null) {
                // Defence in depth: the customer must belong to this tenant.
                $belongsToBusiness = Customer::withoutGlobalScopes()
                    ->where('id', $customerId)
                    ->where('business_id', $business->id)
                    ->exists();

                if (! $belongsToBusiness) {
                    throw new RuntimeException('Customer not found in this business.');
                }
            }

            $total = max(0, $subtotal - $discount) + $tax;

            $order = Order::create([
                'business_id' => $business->id,
                'customer_id' => $customerId,
                'order_number' => $this->nextOrderNumber($business->id),
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'total' => $total,
                'currency' => $business->currency,
                'status' => $attributes['status'] ?? 'pending',
                'payment_status' => $attributes['payment_status'] ?? 'unpaid',
                'notes' => $attributes['notes'] ?? null,
            ]);

            foreach ($prepared as $line) {
                $order->items()->create([
                    'product_id' => $line['product_id'],
                    'name' => $line['name'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'total' => $line['total'],
                ]);

                if ($line['track_inventory']) {
                    $this->applyStockMovement($business->id, $line, 'out', $order->order_number);
                }
            }

            $this->gate->incrementUsage($business, 'max_orders_monthly');

            return $order->load('items');
        });
    }

    protected function applyStockMovement(int $businessId, array $line, string $type, string $reference): void
    {
        $product = Product::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->whereKey($line['product_id'])
            ->lockForUpdate()
            ->first();

        if (! $product) {
            throw new RuntimeException('Product not found.');
        }

        $delta = $type === 'out' ? -$line['quantity'] : $line['quantity'];
        $newQuantity = $product->stock_quantity + $delta;

        if ($newQuantity < 0) {
            throw new RuntimeException("Insufficient stock for product: {$product->name}");
        }

        $product->update(['stock_quantity' => $newQuantity]);

        StockMovement::create([
            'business_id' => $businessId,
            'product_id' => $product->id,
            'type' => $type,
            'quantity' => abs($delta),
            'balance_after' => $newQuantity,
            'reference' => $reference,
            'user_id' => auth()->id(),
        ]);

        $this->warnAboutLowStock($businessId, $product->fresh());
    }

    /**
     * Warn the business owner when a tracked product falls below its threshold.
     */
    protected function warnAboutLowStock(int $businessId, Product $product): void
    {
        if ((int) $product->low_stock_threshold <= 0) {
            return;
        }

        if ((int) $product->stock_quantity > (int) $product->low_stock_threshold) {
            return;
        }

        $business = Business::find($businessId);
        $owner = $business?->owner;

        if (! $business || ! $owner) {
            return;
        }

        if (! $this->gate->allows($business, 'low_stock_alerts')) {
            return;
        }

        try {
            Notification::send($owner, (new LowStockAlert($product))->afterCommit());
        } catch (Throwable $e) {
            Log::warning('Low stock notification could not be sent.', [
                'business_id' => $businessId,
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function nextOrderNumber(int $businessId): string
    {
        // Serialise concurrent allocations for this business. The caller runs
        // inside a transaction, so the lock is held until it commits.
        DB::table('businesses')->where('id', $businessId)->lockForUpdate()->first();

        $prefix = 'ORD-'.now()->format('Ymd').'-';

        $last = DB::table('orders')
            ->where('business_id', $businessId)
            ->where('order_number', 'like', $prefix.'%')
            ->orderByDesc('order_number')
            ->value('order_number');

        // Derive the next sequence from the highest existing number so that a
        // deleted order can never cause a number to be reused.
        $sequence = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }
}
