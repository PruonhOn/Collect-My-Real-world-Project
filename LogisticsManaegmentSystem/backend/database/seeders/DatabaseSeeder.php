<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Only what an installation needs to be usable, plus the demo data set when
     * APP_SEED_DEMO_DATA is on. That switch is exposed through config/app.php and
     * is forced off in production, so a misconfigured deployment cannot fill a
     * live database with fictional bookings.
     *
     * Model events are deliberately left switched on. They used to be suppressed
     * with WithoutModelEvents, which is a reasonable thing to do for a seeder and
     * was quietly wrong here: Shipment::booted() provisions a consignment's
     * tracking row from a `created` listener, because that hook is the module's
     * only substitute for a create endpoint, and a seeder running without events
     * inserted consignments that no tracking row existed for. Nothing reported an
     * error - the rows were simply absent, so the tracking screen came up empty on
     * a fresh install and every consignment was missing from the stale worklist.
     *
     * The saving this used to buy is a few hundred inserts on a demo set that is
     * run once, against a table that is now the reason the hook has to fire.
     */
    public function run(): void
    {
        // Roles first: the initial administrator needs one to hold.
        $this->call(RolePermissionSeeder::class);

        // Keyed settings: the Settings screen has no create endpoint, so the
        // keys it offers have to be declared somewhere.
        $this->call(SettingSeeder::class);

        $this->call(InitialAdminSeeder::class);

        if ($this->shouldSeedDemoData()) {
            $this->call(DemoDataSeeder::class);
        }
    }

    /**
     * Whether the fictional data set belongs in this database.
     *
     * The environment flag alone is not trusted: an APP_SEED_DEMO_DATA left
     * behind in a .env that gets deployed would otherwise fill a live database
     * with invented bookings. Production is therefore refused outright rather
     * than left to the operator having removed the flag.
     */
    private function shouldSeedDemoData(): bool
    {
        return ! app()->isProduction() && (bool) config('app.seed_demo_data');
    }
}
