<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PurchaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        PurchaseItem::truncate();
        Purchase::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $suppliers = Supplier::pluck('id');
        $users = User::pluck('id');
        $products = Product::with('variants')->get();

        if ($products->isEmpty() || $suppliers->isEmpty() || $users->isEmpty()) {
            return;
        }

        for ($i = 1; $i <= 10; $i++) {
            $purchaseDate = Carbon::now()->subDays(random_int(1, 45))->setTime(random_int(8, 17), random_int(0, 59));
            $supplierId = $suppliers->random();
            $userId = $users->random();

            $itemsCount = random_int(1, 4);
            $totalAmount = 0;
            $itemRecords = [];

            for ($j = 0; $j < $itemsCount; $j++) {
                $product = $products->random();
                $variant = $product->variants->random();
                $qty = random_int(10, 50);
                $unitCost = $product->cost_price + random_int(-50, 50);
                $itemSubtotal = $unitCost * $qty;

                $itemRecords[] = [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'subtotal' => $itemSubtotal,
                ];

                $totalAmount += $itemSubtotal;
            }

            $paidAmount = random_int(0, 1) === 0 ? $totalAmount : round($totalAmount * random_int(50, 90) / 100, 2);
            $dueAmount = round($totalAmount - $paidAmount, 2);

            $status = $dueAmount > 0 ? 'partial' : 'received';

            $refNo = 'PO-' . $purchaseDate->format('Ymd') . '-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT);

            $purchase = Purchase::create([
                'supplier_id' => $supplierId,
                'user_id' => $userId,
                'reference_no' => $refNo,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'status' => $status,
                'purchase_date' => $purchaseDate,
            ]);

            foreach ($itemRecords as $item) {
                PurchaseItem::create(array_merge($item, ['purchase_id' => $purchase->id]));
            }
        }
    }
}
