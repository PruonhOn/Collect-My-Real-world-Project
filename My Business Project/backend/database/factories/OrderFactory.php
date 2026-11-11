<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = \App\Models\Order::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'customer_id' => Customer::factory(),
            'order_number' => 'ORD-'.Str::upper(Str::random(10)),
            'subtotal' => random_int(1000, 50000),
            'tax' => 0,
            'discount' => 0,
            'total' => random_int(1000, 50000),
            'currency' => 'USD',
            'status' => 'completed',
            'payment_status' => 'paid',
        ];
    }
}
