<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        Coupon::updateOrCreate(
            ['code' => 'WELCOME20'],
            [
                'type' => 'percentage',
                'value' => 20,
                'min_amount' => null,
                'max_uses' => 1000,
                'starts_at' => now()->startOfYear()->toDateString(),
                'expires_at' => now()->addYears(2)->toDateString(),
                'is_active' => true,
            ]
        );

        Coupon::updateOrCreate(
            ['code' => 'LAUNCH500'],
            [
                'type' => 'fixed',
                'value' => 500,
                'min_amount' => 2000,
                'max_uses' => 500,
                'starts_at' => now()->startOfYear()->toDateString(),
                'expires_at' => now()->addYears(2)->toDateString(),
                'is_active' => true,
            ]
        );
    }
}
