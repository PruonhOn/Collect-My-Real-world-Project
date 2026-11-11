<?php

namespace Database\Seeders;

use App\Models\VipPlan;
use App\Services\VipService;
use Illuminate\Database\Seeder;

class VipPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plan = [
            'name' => 'VIP Yearly',
            'slug' => 'vip-yearly',
            'description' => '12 months of unlimited access to all eligible books and premium materials.',
            'price' => 59.99,
            'duration_days' => VipService::SUBSCRIPTION_DAYS,
            'is_active' => true,
        ];

        VipPlan::updateOrCreate(['slug' => $plan['slug']], $plan);

        VipPlan::where('slug', '!=', $plan['slug'])->update(['is_active' => false]);

        $this->command?->info('VIP set to a single 1-year plan.');
    }
}
