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
                'slug' => 'monthly',
                'price' => 4.99,
                'duration_days' => 30,
                'sort_order' => 1,
                'features' => [
                    'Unlimited access to all paid books',
                    'Read on any device',
                    'Offline download',
                ],
                'translations' => [
                    'en' => ['name' => 'Monthly', 'description' => 'Flexible monthly access to the full library.'],
                    'km' => ['name' => 'ប្រចាំខែ', 'description' => 'ចូលប្រើបណ្ណាល័យទាំងមូលដោយបត់បែនតាមខែ។'],
                    'zh' => ['name' => '月度会员', 'description' => '灵活按月访问整个图书馆。'],
                    'fr' => ['name' => 'Mensuel', 'description' => 'Accès mensuel flexible à toute la bibliothèque.'],
                ],
            ],
            [
                'slug' => 'quarterly',
                'price' => 12.99,
                'duration_days' => 90,
                'sort_order' => 2,
                'features' => [
                    'Everything in Monthly',
                    'Save 13% vs monthly',
                    'Priority support',
                ],
                'translations' => [
                    'en' => ['name' => 'Quarterly', 'description' => 'Three months of unlimited reading at a better price.'],
                    'km' => ['name' => 'ប្រចាំត្រីមាស', 'description' => 'ការអានគ្មានដែនកំណត់រយៈពេលបីខែក្នុងតម្លៃល្អជាង។'],
                    'zh' => ['name' => '季度会员', 'description' => '三个月无限阅读，价格更优惠。'],
                    'fr' => ['name' => 'Trimestriel', 'description' => 'Trois mois de lecture illimitée à un meilleur prix.'],
                ],
            ],
            [
                'slug' => 'yearly',
                'price' => 39.99,
                'duration_days' => 365,
                'sort_order' => 3,
                'features' => [
                    'Everything in Quarterly',
                    'Save 33% vs monthly',
                    'Free book downloads',
                    'Early access to new releases',
                ],
                'translations' => [
                    'en' => ['name' => 'Yearly', 'description' => 'A full year of VIP with the best value.'],
                    'km' => ['name' => 'ប្រចាំឆ្នាំ', 'description' => 'VIP មួយឆ្នាំពេញជាមួយតម្លៃដ៏ប្រសើរបំផុត។'],
                    'zh' => ['name' => '年度会员', 'description' => '全年 VIP，性价比最高。'],
                    'fr' => ['name' => 'Annuel', 'description' => 'Une année complète de VIP au meilleur prix.'],
                ],
            ],
        ];

        foreach ($plans as $data) {
            $plan = VipPlan::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'slug' => $data['slug'],
                    'price' => $data['price'],
                    'duration_days' => $data['duration_days'],
                    'features' => $data['features'],
                    'sort_order' => $data['sort_order'],
                    'is_active' => true,
                ]
            );

            foreach ($data['translations'] as $locale => $translation) {
                $plan->translations()->updateOrCreate(
                    ['locale' => $locale],
                    $translation
                );
            }
        }
    }
}
