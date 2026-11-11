<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'site_name', 'value' => 'K & N Collection', 'group' => 'general', 'label' => 'Site Name', 'type' => 'text'],
            ['key' => 'site_description', 'value' => 'Your premium fashion destination. Quality clothing and accessories for every style.', 'group' => 'general', 'label' => 'Site Description', 'type' => 'textarea'],
            ['key' => 'contact_email', 'value' => 'onpruonh123@gmail.com', 'group' => 'contact', 'label' => 'Contact Email', 'type' => 'text'],
            ['key' => 'contact_phone', 'value' => '0967594210', 'group' => 'contact', 'label' => 'Contact Phone', 'type' => 'text'],
            ['key' => 'address', 'value' => '', 'group' => 'contact', 'label' => 'Address', 'type' => 'textarea'],
            ['key' => 'facebook_url', 'value' => '', 'group' => 'social', 'label' => 'Facebook URL', 'type' => 'text'],
            ['key' => 'instagram_url', 'value' => '', 'group' => 'social', 'label' => 'Instagram URL', 'type' => 'text'],
            ['key' => 'twitter_url', 'value' => '', 'group' => 'social', 'label' => 'Twitter URL', 'type' => 'text'],
            ['key' => 'youtube_url', 'value' => '', 'group' => 'social', 'label' => 'YouTube URL', 'type' => 'text'],
            ['key' => 'products_per_page', 'value' => '12', 'group' => 'general', 'label' => 'Products Per Page', 'type' => 'text'],
            ['key' => 'currency_symbol', 'value' => '$', 'group' => 'general', 'label' => 'Currency Symbol', 'type' => 'text'],
            ['key' => 'tax_rate', 'value' => '0', 'group' => 'general', 'label' => 'Tax Rate (%)', 'type' => 'text'],
            ['key' => 'shipping_fee', 'value' => '0', 'group' => 'general', 'label' => 'Shipping Fee', 'type' => 'text'],
            ['key' => 'free_shipping_min', 'value' => '0', 'group' => 'general', 'label' => 'Free Shipping Minimum Amount', 'type' => 'text'],
        ];

        foreach ($settings as $setting) {
            SystemSetting::create($setting);
        }
    }
}
