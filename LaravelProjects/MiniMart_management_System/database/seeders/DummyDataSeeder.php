<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\SaleService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $saleService = app(SaleService::class);

        // Create sample sales for the last 7 days
        for ($day = 6; $day >= 0; $day--) {
            $saleCount = rand(2, 5);
            for ($i = 0; $i < $saleCount; $i++) {
                $cart = [];
                $productCount = rand(1, 4);

                $products = \App\Models\Product::inRandomOrder()->take($productCount)->get();
                foreach ($products as $product) {
                    $qty = rand(1, 3);
                    if ($product->quantity >= $qty) {
                        $cart[] = [
                            'product_id' => $product->id,
                            'quantity' => $qty,
                        ];
                    }
                }

                if (!empty($cart)) {
                    try {
                        $totalAmount = 0;
                        foreach ($cart as $item) {
                            $p = \App\Models\Product::find($item['product_id']);
                            $totalAmount += $item['quantity'] * $p->selling_price;
                        }
                        $paidAmount = $totalAmount + rand(0, 5);

                        $sale = Sale::create([
                            'invoice_number' => $saleService->generateInvoiceNumber(),
                            'user_id' => rand(1, 2),
                            'total_amount' => $totalAmount,
                            'paid_amount' => $paidAmount,
                            'change_amount' => $paidAmount - $totalAmount,
                            'payment_method' => 'cash',
                            'created_at' => Carbon::now()->subDays($day)->addHours(rand(8, 20))->addMinutes(rand(0, 59)),
                            'updated_at' => Carbon::now()->subDays($day)->addHours(rand(8, 20))->addMinutes(rand(0, 59)),
                        ]);

                        foreach ($cart as $item) {
                            $p = \App\Models\Product::find($item['product_id']);
                            SaleItem::create([
                                'sale_id' => $sale->id,
                                'product_id' => $p->id,
                                'quantity' => $item['quantity'],
                                'unit_price' => $p->selling_price,
                                'total_price' => $item['quantity'] * $p->selling_price,
                                'created_at' => $sale->created_at,
                                'updated_at' => $sale->created_at,
                            ]);
                            $p->decrement('quantity', $item['quantity']);
                        }
                    } catch (\Exception $e) {
                        // Skip if insufficient stock
                    }
                }
            }
        }

        // Create sample expenses for the last 7 days
        $expenseDescriptions = [
            'Electricity bill', 'Water bill', 'Store cleaning supplies',
            'Employee salary advance', 'Transportation', 'Maintenance',
            'Internet bill', 'Office supplies', 'Marketing materials',
            'Store rent',
        ];

        for ($day = 6; $day >= 0; $day--) {
            $expenseCount = rand(1, 2);
            for ($i = 0; $i < $expenseCount; $i++) {
                Expense::create([
                    'description' => $expenseDescriptions[array_rand($expenseDescriptions)],
                    'amount' => rand(10, 200) + rand(0, 99) / 100,
                    'date' => Carbon::now()->subDays($day),
                    'user_id' => 1,
                    'created_at' => Carbon::now()->subDays($day),
                    'updated_at' => Carbon::now()->subDays($day),
                ]);
            }
        }
    }
}
