<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentMethod;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        if (PaymentMethod::count() > 0) {
            return;
        }

        $methods = [
            ['name' => 'Cash', 'code' => 'CASH', 'description' => 'Cash payment', 'is_active' => true],
            ['name' => 'Bank Transfer', 'code' => 'BANK', 'description' => 'Direct bank transfer', 'is_active' => true],
            ['name' => 'ABA Pay', 'code' => 'ABA', 'description' => 'ABA Bank mobile payment', 'is_active' => true],
            ['name' => 'Wing', 'code' => 'WING', 'description' => 'Wing money transfer', 'is_active' => true],
            ['name' => 'TrueMoney', 'code' => 'TRUE', 'description' => 'TrueMoney wallet', 'is_active' => true],
            ['name' => 'Pi Pay', 'code' => 'PIPAY', 'description' => 'Pi Pay mobile wallet', 'is_active' => true],
            ['name' => 'Credit Card', 'code' => 'CC', 'description' => 'Credit card payment', 'is_active' => true],
            ['name' => 'Cheque', 'code' => 'CHQ', 'description' => 'Cheque payment', 'is_active' => true],
        ];

        foreach ($methods as $method) {
            PaymentMethod::create($method);
        }
    }
}
