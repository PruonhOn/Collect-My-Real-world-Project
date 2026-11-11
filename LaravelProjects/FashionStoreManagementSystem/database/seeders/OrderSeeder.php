<?php

namespace Database\Seeders;

use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\SaleService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $saleService = app(SaleService::class);

        $cashiers = User::whereIn('role', ['cashier', 'manager'])->get();
        $customers = Customer::all();
        $variants = ProductVariant::with('product')->where('stock_quantity', '>', 0)->get();

        if ($cashiers->isEmpty() || $variants->isEmpty()) {
            return;
        }

        $faker = \Faker\Factory::create();

        for ($daysAgo = 29; $daysAgo >= 0; $daysAgo--) {
            $ordersToday = random_int(2, 7);
            $day = Carbon::today()->subDays($daysAgo);

            for ($i = 0; $i < $ordersToday; $i++) {
                $this->createOrder($saleService, $cashiers, $customers, $variants, $faker, $day);
            }
        }

        // A few refunds to showcase the refund workflow
        $refundCandidates = Order::query()
            ->where('status', 'completed')
            ->whereHas('items')
            ->limit(4)
            ->get();

        foreach ($refundCandidates as $order) {
            $item = $order->items()->first();
            if (! $item || $item->variant_id === null) {
                continue;
            }

            try {
                $saleService->refund($order, [
                    ['order_item_id' => $item->id, 'quantity' => 1],
                ], 'Customer returned an item (seeded sample data).', $cashiers->random());
            } catch (\Throwable) {
                // ignore - some orders may not be refundable in this iteration
            }
        }
    }

    private function createOrder(SaleService $saleService, $cashiers, $customers, $variants, $faker, Carbon $day): void
    {
        $lineCount = random_int(1, 4);
        $chosen = [];

        for ($i = 0; $i < $lineCount; $i++) {
            $variant = $variants->random();
            $qty = random_int(1, 3);

            if ($variant->stock_quantity < $qty) {
                continue;
            }

            $chosen[] = ['variant_id' => $variant->id, 'quantity' => $qty];
        }

        if (empty($chosen)) {
            return;
        }

        $useCustomer = $faker->boolean(65) && $customers->isNotEmpty();
        $discountRoll = $faker->randomElement(['none', 'none', 'none', 'percent', 'fixed']);
        $discountType = DiscountType::from($discountRoll);
        $discountValue = match ($discountType) {
            DiscountType::Percent => (float) $faker->randomElement([5, 10, 15, 20]),
            DiscountType::Fixed => (float) $faker->randomElement([5, 10, 15, 25]),
            default => 0.0,
        };

        $paymentMethod = PaymentMethod::from($faker->randomElement(['cash', 'cash', 'card', 'qr']));
        $time = $day->copy()->setTime(random_int(9, 20), random_int(0, 59), random_int(0, 59));

        try {
            $order = $saleService->checkout(
                cartItems: $chosen,
                customerId: $useCustomer ? $customers->random()->id : null,
                paymentMethod: $paymentMethod,
                discountType: $discountType,
                discountValue: $discountValue,
                amountTendered: $paymentMethod === PaymentMethod::Cash ? $faker->randomFloat(2, 100, 500) : null,
                notes: null,
                cashier: $cashiers->random(),
            );

            $order->forceFill(['created_at' => $time, 'updated_at' => $time])->save();
            $order->items()->update(['created_at' => $time, 'updated_at' => $time]);

            InventoryTransaction::query()
                ->where('reference_type', 'order')
                ->where('reference_id', $order->id)
                ->update(['created_at' => $time, 'updated_at' => $time]);
        } catch (\Throwable) {
            // skip orders that can't be created (e.g. stock ran out)
        }
    }
}
