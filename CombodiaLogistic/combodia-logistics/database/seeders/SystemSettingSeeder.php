<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemSetting;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'company_name', 'value' => 'Combodia Logistics Co., Ltd.', 'group' => 'general', 'description' => 'Company name'],
            ['key' => 'company_address', 'value' => '#123, Street 456, Sangkat XYZ, Khan ABC, Phnom Penh, Cambodia', 'group' => 'general', 'description' => 'Company physical address'],
            ['key' => 'company_phone', 'value' => '+855 23 888 999', 'group' => 'general', 'description' => 'Company phone number'],
            ['key' => 'company_email', 'value' => 'info@combodialogistics.com', 'group' => 'general', 'description' => 'Company email address'],
            ['key' => 'company_tax_number', 'value' => 'KH-123456789', 'group' => 'general', 'description' => 'Company tax number'],
            ['key' => 'currency', 'value' => 'KHR', 'group' => 'general', 'description' => 'Default currency'],
            ['key' => 'timezone', 'value' => 'Asia/Phnom_Penh', 'group' => 'general', 'description' => 'Default timezone'],
            ['key' => 'date_format', 'value' => 'd/m/Y', 'group' => 'general', 'description' => 'Date display format'],
            ['key' => 'pagination_size', 'value' => '15', 'group' => 'general', 'description' => 'Default pagination size'],
            ['key' => 'shipment_prefix', 'value' => 'SHIP', 'group' => 'shipment', 'description' => 'Shipment number prefix'],
            ['key' => 'invoice_prefix', 'value' => 'INV', 'group' => 'invoice', 'description' => 'Invoice number prefix'],
            ['key' => 'payment_prefix', 'value' => 'PAY', 'group' => 'payment', 'description' => 'Payment number prefix'],
            ['key' => 'expense_prefix', 'value' => 'EXP', 'group' => 'expense', 'description' => 'Expense number prefix'],
            ['key' => 'income_prefix', 'value' => 'INC', 'group' => 'income', 'description' => 'Income number prefix'],
            ['key' => 'return_prefix', 'value' => 'RET', 'group' => 'return', 'description' => 'Return number prefix'],
            ['key' => 'pickup_prefix', 'value' => 'PICK', 'group' => 'pickup', 'description' => 'Pickup request number prefix'],
            ['key' => 'dispatch_prefix', 'value' => 'DSP', 'group' => 'dispatch', 'description' => 'Dispatch number prefix'],
            ['key' => 'tax_rate', 'value' => '10', 'group' => 'invoice', 'description' => 'Default tax rate percentage'],
            ['key' => 'enable_auto_invoice', 'value' => 'true', 'group' => 'invoice', 'description' => 'Auto-generate invoice on delivery'],
            ['key' => 'enable_notifications', 'value' => 'true', 'group' => 'notification', 'description' => 'Enable system notifications'],
        ];

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
