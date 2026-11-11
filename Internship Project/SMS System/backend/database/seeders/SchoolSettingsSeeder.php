<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SchoolSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['group' => 'general', 'key' => 'school_name', 'value' => 'MultiOrion International School', 'is_public' => true],
            ['group' => 'general', 'key' => 'school_short_name', 'value' => 'MOIS', 'is_public' => true],
            ['group' => 'general', 'key' => 'school_tagline', 'value' => 'Learn. Grow. Succeed.', 'is_public' => true],
            ['group' => 'general', 'key' => 'established_year', 'value' => '2008', 'is_public' => true],
            ['group' => 'general', 'key' => 'address', 'value' => '123 Street 271, Sangkat Chraing Chamres II, Phnom Penh', 'is_public' => true],
            ['group' => 'general', 'key' => 'phone', 'value' => '+855 12 345 678', 'is_public' => true],
            ['group' => 'general', 'key' => 'email', 'value' => 'info@mois.edu.kh', 'is_public' => true],
            ['group' => 'general', 'key' => 'facebook_url', 'value' => 'https://facebook.com/mois', 'is_public' => true],
            ['group' => 'general', 'key' => 'telegram_url', 'value' => 'https://t.me/mois', 'is_public' => true],
            ['group' => 'general', 'key' => 'website_url', 'value' => 'https://mois.edu.kh', 'is_public' => true],
            ['group' => 'general', 'key' => 'about_us', 'value' => 'MultiOrion International School provides quality education combining the Cambodian national curriculum with international standards.', 'is_public' => true],
            ['group' => 'general', 'key' => 'mission', 'value' => 'To empower every student with knowledge, skills, and character to thrive in a global community.', 'is_public' => true],
            ['group' => 'general', 'key' => 'vision', 'value' => 'To be the leading international school in Cambodia known for academic excellence and lifelong learning.', 'is_public' => true],

            ['group' => 'payments', 'key' => 'currency', 'value' => 'KHR', 'is_public' => true],
            ['group' => 'payments', 'key' => 'invoice_prefix', 'value' => 'INV', 'is_public' => false],
            ['group' => 'payments', 'key' => 'receipt_prefix', 'value' => 'RCP', 'is_public' => false],
            ['group' => 'payments', 'key' => 'accepted_methods', 'value' => '["cash","bakong","acleda","aba","wing"]', 'is_public' => true],
            ['group' => 'payments', 'key' => 'cashier_instructions', 'value' => 'Issue a receipt for every payment and record the reference number.', 'is_public' => false],

            ['group' => 'partnership', 'key' => 'partner_name', 'value' => 'Pi Education Cambodia', 'is_public' => true],
            ['group' => 'partnership', 'key' => 'partner_tagline', 'value' => 'Powered by Pi Education Cambodia', 'is_public' => true],
            ['group' => 'partnership', 'key' => 'partner_description', 'value' => 'Our programs are developed in partnership with Pi Education Cambodia to bring modern learning experiences to students.', 'is_public' => true],
            ['group' => 'partnership', 'key' => 'partner_logo_url', 'value' => null, 'is_public' => true],
            ['group' => 'partnership', 'key' => 'partner_footer_note', 'value' => 'In collaboration with Pi Education Cambodia.', 'is_public' => true],

            ['group' => 'website', 'key' => 'hero_headline', 'value' => 'Shaping Future Leaders', 'is_public' => true],
            ['group' => 'website', 'key' => 'hero_subheadline', 'value' => 'International-standard education in the heart of Phnom Penh.', 'is_public' => true],
            ['group' => 'website', 'key' => 'show_courses', 'value' => '1', 'is_public' => true],
            ['group' => 'website', 'key' => 'show_contact_form', 'value' => '1', 'is_public' => true],
        ];

        foreach ($settings as $setting) {
            DB::table('school_settings')->updateOrInsert(
                ['group' => $setting['group'], 'key' => $setting['key']],
                ['value' => $setting['value'], 'is_public' => $setting['is_public']],
            );
        }
    }
}
