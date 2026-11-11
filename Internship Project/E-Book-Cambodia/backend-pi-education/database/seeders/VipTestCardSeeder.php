<?php

namespace Database\Seeders;

use App\Models\VipPlan;
use App\Services\VipService;
use Illuminate\Database\Seeder;

class VipTestCardSeeder extends Seeder
{
    public function run(): void
    {
        VipPlan::updateOrCreate(
            ['slug' => VipService::TEST_PLAN_SLUG],
            [
                'name' => 'VIP Test Card',
                'description' => 'TEST ONLY - $0.01 - 5 HOURS. Runs the complete Bakong KHQR payment and VIP activation flow for development and testing.',
                'price' => VipService::TEST_PLAN_PRICE,
                'duration_days' => 0,
                'duration_hours' => VipService::TEST_SUBSCRIPTION_HOURS,
                'is_test' => true,
                'is_active' => true,
            ]
        );

        $this->command?->info('VIP Test Card seeded: $0.01 / 5 hours. Set VIP_TEST_MODE=false in production to hide it.');
    }
}
