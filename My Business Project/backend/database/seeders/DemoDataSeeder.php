<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\BusinessMembership;
use App\Models\BusinessSubscription;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    /**
     * Development-only demo data. Safe to re-run: every record is looked up
     * by a stable natural key before being created or updated.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@mybusiness.test'],
            [
                'name' => 'Platform Administrator',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_super_admin' => true,
                'is_active' => true,
            ]
        );

        $freePlan = Plan::where('slug', 'free')->first();
        $proPlan = Plan::where('slug', 'pro-monthly')->first();

        $demoBusinesses = [
            [
                'key' => 'demo-free',
                'name' => 'Demo Free Business',
                'email' => 'owner@demofree.test',
                'plan' => 'free',
                'products' => 12,
                'customers' => 8,
                'orders' => 6,
            ],
            [
                'key' => 'demo-pro',
                'name' => 'Demo Pro Business',
                'email' => 'owner@demopro.test',
                'plan' => 'pro',
                'products' => 30,
                'customers' => 20,
                'orders' => 15,
            ],
        ];

        foreach ($demoBusinesses as $spec) {
            $owner = User::updateOrCreate(
                ['email' => $spec['email']],
                [
                    'name' => ucwords(str_replace(['.', 'test'], ' ', $spec['email'])),
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]
            );

            $business = Business::updateOrCreate(
                ['slug' => $spec['key']],
                [
                    'owner_id' => $owner->id,
                    'name' => $spec['name'],
                    'business_type' => 'retail',
                    'country' => 'US',
                    'currency' => 'USD',
                    'timezone' => 'UTC',
                    'is_active' => true,
                ]
            );

            BusinessMembership::updateOrCreate(
                ['business_id' => $business->id, 'user_id' => $owner->id],
                [
                    'role' => 'owner',
                    'status' => 'active',
                    'accepted_at' => now(),
                ]
            );

            $plan = $spec['plan'] === 'pro' ? $proPlan : $freePlan;

            BusinessSubscription::updateOrCreate(
                ['business_id' => $business->id],
                [
                    'plan_id' => $plan->id,
                    'status' => 'active',
                    'billing_cycle' => $spec['plan'] === 'pro' ? 'monthly' : null,
                    'starts_at' => now()->subMonth(),
                    'ends_at' => $spec['plan'] === 'pro' ? now()->addMonth() : null,
                ]
            );

            $this->seedOperationalData($business, $spec);
        }
    }

    protected function seedOperationalData(Business $business, array $spec): void
    {
        $productNames = [
            'Consulting Hour', 'Monthly Retainer', 'Website Audit', 'Premium Support',
            'Training Workshop', 'License Fee', 'Data Migration', 'Hosting Plan',
            'Starter Package', 'Team License', 'Onboarding', 'Annual Review',
        ];

        $products = collect();
        for ($i = 0; $i < $spec['products']; $i++) {
            $name = $productNames[$i % count($productNames)].($i >= count($productNames) ? ' '.(($i + 1)) : '');
            $products->push(\App\Models\Product::updateOrCreate(
                ['business_id' => $business->id, 'sku' => 'SKU-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT)],
                [
                    'name' => $name,
                    'description' => 'Seeded demo product for local development.',
                    'price' => 1500 + ($i * 750),
                    'cost_price' => 700 + ($i * 300),
                    'status' => 'active',
                    'track_inventory' => false,
                ]
            ));
        }

        $customers = collect();
        for ($i = 0; $i < $spec['customers']; $i++) {
            $customers->push(\App\Models\Customer::updateOrCreate(
                ['business_id' => $business->id, 'email' => "customer{$i}@demo.test"],
                [
                    'first_name' => 'Customer',
                    'last_name' => (string) ($i + 1),
                    'phone' => '+1 555 0100'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                    'address' => '100 Demo Street, Sample City',
                ]
            ));
        }

        for ($i = 0; $i < $spec['orders']; $i++) {
            $customer = $customers[$i % $customers->count()];
            $product = $products[$i % $products->count()];
            $quantity = ($i % 3) + 1;
            $unitPrice = (int) $product->price;
            $total = $unitPrice * $quantity;

            $order = \App\Models\Order::updateOrCreate(
                ['business_id' => $business->id, 'order_number' => 'DEMO-'.str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT)],
                [
                    'customer_id' => $customer->id,
                    'subtotal' => $total,
                    'tax' => 0,
                    'discount' => 0,
                    'total' => $total,
                    'currency' => $business->currency,
                    'status' => 'completed',
                    'payment_status' => 'paid',
                    'created_at' => now()->subDays($i * 2),
                    'updated_at' => now()->subDays($i * 2),
                ]
            );

            \App\Models\OrderItem::updateOrCreate(
                ['order_id' => $order->id, 'product_id' => $product->id],
                [
                    'name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $total,
                ]
            );
        }
    }
}
