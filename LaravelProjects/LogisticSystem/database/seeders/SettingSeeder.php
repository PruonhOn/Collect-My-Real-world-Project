<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key' => 'company_name',
                'value' => 'Cambodia Logistics Co., Ltd.',
                'group' => 'general',
                'type' => 'text',
                'description' => 'Company display name',
            ],
            [
                'key' => 'company_address',
                'value' => '#123, Street 456, Phnom Penh, Cambodia',
                'group' => 'general',
                'type' => 'text',
                'description' => 'Company physical address',
            ],
            [
                'key' => 'company_phone',
                'value' => '+855 23 888 999',
                'group' => 'general',
                'type' => 'text',
                'description' => 'Company contact phone number',
            ],
            [
                'key' => 'company_email',
                'value' => 'info@cambodialogistics.com',
                'group' => 'general',
                'type' => 'text',
                'description' => 'Company contact email',
            ],
            [
                'key' => 'company_logo',
                'value' => null,
                'group' => 'general',
                'type' => 'image',
                'description' => 'Company logo image path',
            ],
            [
                'key' => 'default_currency',
                'value' => 'USD',
                'group' => 'financial',
                'type' => 'text',
                'description' => 'Default currency for transactions',
            ],
            [
                'key' => 'default_exchange_rate',
                'value' => '4100',
                'group' => 'financial',
                'type' => 'number',
                'description' => 'USD to KHR exchange rate',
            ],
            [
                'key' => 'tax_rate',
                'value' => '10',
                'group' => 'financial',
                'type' => 'number',
                'description' => 'Default tax rate percentage',
            ],
            [
                'key' => 'default_weight_unit',
                'value' => 'kg',
                'group' => 'shipment',
                'type' => 'text',
                'description' => 'Default weight unit for shipments',
            ],
            [
                'key' => 'default_volume_unit',
                'value' => 'm3',
                'group' => 'shipment',
                'type' => 'text',
                'description' => 'Default volume unit for shipments',
            ],
            [
                'key' => 'tracking_url',
                'value' => '/track',
                'group' => 'shipment',
                'type' => 'text',
                'description' => 'Public tracking URL path',
            ],
            [
                'key' => 'date_format',
                'value' => 'd/m/Y',
                'group' => 'general',
                'type' => 'text',
                'description' => 'Default date display format',
            ],
            [
                'key' => 'timezone',
                'value' => 'Asia/Phnom_Penh',
                'group' => 'general',
                'type' => 'text',
                'description' => 'Default timezone',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
