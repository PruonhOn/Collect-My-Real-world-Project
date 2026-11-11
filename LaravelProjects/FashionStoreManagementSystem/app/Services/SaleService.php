<?php

namespace App\Services;

use App\Enums\DiscountType;
use App\Enums\InventoryTransactionType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\InsufficientStockException;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\Refund;
use App\Models\RefundItem;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaleService
{
    public function __construct(private readonly InventoryService $inventory)
    {
    }

    public function checkout(
        array $cartItems,
        ?int $customerId,
        PaymentMethod $paymentMethod,
        DiscountType $discountType,
        float $discountValue,
        ?float $amountTendered,
        ?string $notes,
        User $cashier
    ): Order {
        return DB::transaction(function () use ($cartItems, $customerId, $paymentMethod, $discountType, $discountValue, $amountTendered, $notes, $cashier) {
            $customer = $customerId ? Customer::find($customerId) : null;

            $lineItems = [];
            $subtotal = 0.0;

            foreach ($cartItems as $item) {
                $variant = ProductVariant::query()->lockForUpdate()->findOrFail($item['variant_id']);
                $qty = (int) $item['quantity'];

                if ($variant->stock_quantity < $qty) {
                    throw new InsufficientStockException($variant, $qty);
                }

                $price = $variant->price;
                $lineTotal = round($price * $qty, 2);
                $subtotal += $lineTotal;

                $lineItems[] = [
                    'variant' => $variant,
                    'qty' => $qty,
                    'unit_price' => $price,
                    'line_total' => $lineTotal,
                ];
            }

            $subtotal = round($subtotal, 2);
            [$discountAmount, $taxRate, $taxAmount, $total] = $this->calculateTotals($discountType, $discountValue, $subtotal);

            $orderNumber = $this->generateOrderNumber();
            $taxRateDecimal = $taxRate / 100;

            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $customer?->id,
                'user_id' => $cashier->id,
                'subtotal' => $subtotal,
                'discount_type' => $discountType->value,
                'discount_value' => $discountValue,
                'discount_amount' => $discountAmount,
                'tax_rate' => $taxRateDecimal,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'amount_tendered' => $amountTendered,
                'change_due' => $amountTendered !== null ? round(max(0, $amountTendered - $total), 2) : null,
                'payment_method' => $paymentMethod->value,
                'payment_status' => 'paid',
                'status' => OrderStatus::Completed->value,
                'notes' => $notes,
            ]);

            foreach ($lineItems as $line) {
                /** @var ProductVariant $variant */
                $variant = $line['variant'];

                $variant->decrement('stock_quantity', $line['qty']);

                $order->items()->create([
                    'variant_id' => $variant->id,
                    'product_name' => $variant->product->name,
                    'variant_label' => $variant->label,
                    'sku' => $variant->sku_variant,
                    'unit_price' => $line['unit_price'],
                    'cost_price' => $variant->product->cost_price,
                    'quantity' => $line['qty'],
                    'line_total' => $line['line_total'],
                ]);

                $this->inventory->recordSale($variant, $line['qty'], $order->id, $cashier);
            }

            if ($customer) {
                $customer->increment('loyalty_points', (int) floor($total));
            }

            return $order->load('items', 'customer', 'user');
        });
    }

    public function refund(Order $order, array $items, string $reason, User $staff): Refund
    {
        return DB::transaction(function () use ($order, $items, $reason, $staff) {
            if (! in_array($order->status->value, [OrderStatus::Completed->value, OrderStatus::PartiallyRefunded->value])) {
                throw new \DomainException('This order cannot be refunded.');
            }

            $refund = Refund::create([
                'refund_number' => 'RF-'.date('Ymd').'-'.Str::upper(Str::random(4)),
                'order_id' => $order->id,
                'user_id' => $staff->id,
                'reason' => $reason,
                'amount' => 0,
            ]);

            $refundAmount = 0.0;

            foreach ($items as $requested) {
                $orderItem = OrderItem::query()->lockForUpdate()->findOrFail($requested['order_item_id']);

                if ($orderItem->order_id !== $order->id) {
                    throw new \InvalidArgumentException('Invalid item for this order.');
                }

                $qty = (int) $requested['quantity'];
                $remaining = $orderItem->quantity - (int) $orderItem->refundItems()->sum('quantity');

                if ($qty > $remaining) {
                    throw new \InvalidArgumentException("Only {$remaining} unit(s) of \"{$orderItem->product_name}\" can be refunded.");
                }

                $share = $order->subtotal > 0 ? $orderItem->line_total / $order->subtotal : 0;
                $lineRefund = round($order->total * $share * ($qty / $orderItem->quantity), 2);

                RefundItem::create([
                    'refund_id' => $refund->id,
                    'order_item_id' => $orderItem->id,
                    'variant_id' => $orderItem->variant_id,
                    'quantity' => $qty,
                    'unit_price' => $orderItem->unit_price,
                    'line_total' => $lineRefund,
                ]);

                $refundAmount += $lineRefund;

                if ($orderItem->variant_id) {
                    $variant = ProductVariant::query()->lockForUpdate()->find($orderItem->variant_id);
                    if ($variant) {
                        $variant->increment('stock_quantity', $qty);
                        $this->inventory->recordReturn($variant, $qty, $order->id, $staff);
                    }
                }
            }

            $refundAmount = round($refundAmount, 2);
            $refund->update(['amount' => $refundAmount]);

            $newRefunded = round((float) $order->refunded_amount + $refundAmount, 2);
            $order->update(['refunded_amount' => $newRefunded]);

            $remainingRefundable = round((float) $order->total - $newRefunded, 2);
            $order->update([
                'status' => $remainingRefundable <= 0.001
                    ? OrderStatus::Refunded->value
                    : OrderStatus::PartiallyRefunded->value,
            ]);

            return $refund->load('items', 'order', 'user');
        });
    }

    private function calculateTotals(DiscountType $discountType, float $discountValue, float $subtotal): array
    {
        $discountAmount = 0.0;

        if ($discountType === DiscountType::Percent && $discountValue > 0) {
            $discountAmount = round($subtotal * ($discountValue / 100), 2);
        } elseif ($discountType === DiscountType::Fixed && $discountValue > 0) {
            $discountAmount = round(min($discountValue, $subtotal), 2);
        }

        $taxRate = (float) Setting::get('tax_rate', '0');
        $taxable = round($subtotal - $discountAmount, 2);
        $taxAmount = round($taxable * ($taxRate / 100), 2);
        $total = round($taxable + $taxAmount, 2);

        return [$discountAmount, $taxRate, $taxAmount, $total];
    }

    private function generateOrderNumber(): string
    {
        do {
            $number = 'POS-'.date('Ymd').'-'.strtoupper(Str::random(5));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
