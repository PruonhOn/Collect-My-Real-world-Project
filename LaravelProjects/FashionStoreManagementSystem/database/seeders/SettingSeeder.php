<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::setMany([
            'store_name' => 'Velvet Vogue',
            'store_tagline' => 'Elegant fashion for every occasion',
            'store_address' => '12 Fashion Avenue, City Center',
            'store_phone' => '+1 555 010 2233',
            'store_email' => 'hello@fashionstore.test',
            'currency' => '$',
            'tax_rate' => '10',
            'receipt_footer' => 'Thank you for shopping with Velvet Vogue!',
            'store_logo' => null,
        ]);
    }
}
