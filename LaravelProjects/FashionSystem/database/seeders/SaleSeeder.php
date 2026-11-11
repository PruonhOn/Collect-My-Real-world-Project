<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SaleSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        SaleItem::truncate();
        Sale::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $customers = Customer::pluck('id');
        $users = User::pluck('id');
        $products = Product::with('variants')->get();

        if ($products->isEmpty() || $customers->isEmpty() || $users->isEmpty()) {
            return;
        }

        $paymentMethods = ['cash', 'cash', 'cash', 'gcash', 'maya', 'credit_card', 'debit_card'];
        $paymentStatuses = ['paid', 'paid', 'paid', 'paid', 'pending', 'partial'];

        for ($i = 1; $i <= 25; $i++) {
            $saleDate = Carbon::now()->subDays(random_int(0, 29))->setTime(random_int(8, 20), random_int(0, 59));
            $customerId = random_int(0, 2) === 0 ? null : $customers->random();
            $userId = $users->random();
            $method = $paymentMethods[array_rand($paymentMethods)];
            $status = $paymentStatuses[array_rand($paymentStatuses)];

            $invoiceNo = 'INV-' . $saleDate->format('Ymd') . '-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT);

            $itemsCount = random_int(1, 5);
            $subtotal = 0;
            $itemRecords = [];

            $usedProductIds = [];

            for ($j = 0; $j < $itemsCount; $j++) {
                $product = $products->random();

                if (in_array($product->id, $usedProductIds)) {
                    continue;
                }
                $usedProductIds[] = $product->id;

                $variant = $product->variants->random();
                $price = $product->selling_price + $variant->additional_price;
                $qty = random_int(1, 3);
                $itemSubtotal = $price * $qty;

                $itemRecords[] = [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'subtotal' => $itemSubtotal,
                ];

                $subtotal += $itemSubtotal;
            }

            if (empty($itemRecords)) {
                $i--;
                continue;
            }

            $discount = random_int(0, 3) === 0 ? round($subtotal * random_int(5, 20) / 100, 2) : 0;
            $tax = round(($subtotal - $discount) * 0.12, 2);
            $total = round($subtotal - $discount + $tax, 2);

            if ($status === 'paid') {
                $paidAmount = $total;
                $changeAmount = 0;
            } elseif ($status === 'partial') {
                $paidAmount = round($total * random_int(50, 80) / 100, 2);
                $changeAmount = 0;
            } else {
                $paidAmount = 0;
                $changeAmount = 0;
            }

            $sale = Sale::create([
                'invoice_no' => $invoiceNo,
                'customer_id' => $customerId,
                'user_id' => $userId,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'payment_method' => $method,
                'payment_status' => $status,
                'sale_date' => $saleDate,
                'notes' => random_int(0, 2) === 0 ? 'Walk-in customer' : null,
            ]);

            foreach ($itemRecords as $item) {
                SaleItem::create(array_merge($item, ['sale_id' => $sale->id]));
            }
        }
    }
}
