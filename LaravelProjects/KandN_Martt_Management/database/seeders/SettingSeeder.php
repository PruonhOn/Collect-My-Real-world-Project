<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General
            ['key' => 'store_name', 'value' => 'K&N Mart', 'group' => 'general', 'type' => 'text'],
            ['key' => 'store_address', 'value' => '123 Main Street, City', 'group' => 'general', 'type' => 'text'],
            ['key' => 'store_phone', 'value' => '+1234567890', 'group' => 'general', 'type' => 'text'],
            ['key' => 'store_email', 'value' => 'info@knmart.com', 'group' => 'general', 'type' => 'text'],
            ['key' => 'currency', 'value' => 'USD', 'group' => 'general', 'type' => 'text'],
            ['key' => 'tax_rate', 'value' => '10', 'group' => 'general', 'type' => 'number'],

            // POS
            ['key' => 'pos_default_payment', 'value' => 'cash', 'group' => 'pos', 'type' => 'text'],
            ['key' => 'pos_auto_print_receipt', 'value' => 'true', 'group' => 'pos', 'type' => 'boolean'],

            // Receipt
            ['key' => 'receipt_header', 'value' => 'K&N Mart', 'group' => 'receipt', 'type' => 'text'],
            ['key' => 'receipt_footer', 'value' => 'Thank you for shopping!', 'group' => 'receipt', 'type' => 'text'],
            ['key' => 'receipt_show_logo', 'value' => 'true', 'group' => 'receipt', 'type' => 'boolean'],

            // Inventory
            ['key' => 'low_stock_threshold', 'value' => '10', 'group' => 'inventory', 'type' => 'number'],
        ];

        foreach ($settings as $setting) {
            Setting::create($setting);
        }
    }
}
