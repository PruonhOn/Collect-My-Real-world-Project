<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Seeds the keyed settings the Settings screen reads.
 *
 * The screen has no create endpoint, by design: a value is only editable if
 * something declared it should exist. Without a seed the table is empty and
 * the screen renders a card with nothing in it, which is a correct response to
 * an unconfigured installation but reads as a broken feature.
 *
 * Every value here is a safe default that works without further configuration,
 * so `db:seed` alone leaves a workable installation.
 */
class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = $this->settings();

        foreach ($settings as $setting) {
            Setting::query()->updateOrCreate(
                ['key' => $setting['key']],
                $setting,
            );
        }

        $this->command?->info('Settings seeded: '.count($settings).' keys.');
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function settings(): array
    {
        return [
            // General
            [
                'key' => 'company.name',
                'value' => 'Logistics ERP',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Trading name shown in the header and on printed documents.',
            ],
            [
                'key' => 'company.timezone',
                'value' => 'Asia/Singapore',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Timezone used to interpret dispatch dates and SLA deadlines.',
            ],
            [
                'key' => 'company.default_currency',
                'value' => 'SGD',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Currency quotations and invoices are issued in.',
            ],

            // Appearance
            [
                'key' => 'app.theme',
                'value' => 'maritime',
                'type' => 'string',
                'group' => 'appearance',
                'description' => 'Theme template: maritime, emerald or sunset. Users may override this in their browser.',
            ],
            [
                'key' => 'app.density',
                'value' => 'comfortable',
                'type' => 'string',
                'group' => 'appearance',
                'description' => 'Table row height. comfortable or compact.',
            ],

            // Notifications
            [
                'key' => 'notifications.digest_enabled',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'notifications',
                'description' => 'Send a daily digest instead of one alert per event.',
            ],
            [
                'key' => 'notifications.sla_warning_hours',
                'value' => '24',
                'type' => 'integer',
                'group' => 'notifications',
                'description' => 'Hours before an SLA breach that a warning is sent.',
            ],

            // Mail
            [
                'key' => 'mail.from_address',
                'value' => 'noreply@logistics-erp.test',
                'type' => 'string',
                'group' => 'mail',
                'description' => 'From address on system email.',
            ],
            [
                'key' => 'mail.reply_to_address',
                'value' => 'support@logistics-erp.test',
                'type' => 'string',
                'group' => 'mail',
                'description' => 'Where replies to system email are directed.',
            ],
        ];
    }
}
