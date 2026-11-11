<?php

namespace Database\Seeders;

use App\Models\VipPlan;
use Illuminate\Database\Seeder;

class VipPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'VIP Monthly',
                'slug' => 'vip-monthly',
                'description' => '1 month of unlimited access to all eligible books and premium materials.',
                'price' => 7.99,
                'duration_days' => 30,
            ],
            [
                'name' => 'VIP Yearly',
                'slug' => 'vip-yearly',
                'description' => '12 months of unlimited access at a discounted rate.',
                'price' => 59.99,
                'duration_days' => 365,
            ],
        ];

        foreach ($plans as $plan) {
            VipPlan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }

        $this->command?->info(count($plans).' VIP plans created.');
    }
}
