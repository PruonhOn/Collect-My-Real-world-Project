<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SystemSettingSeeder::class,
            RolePermissionSeeder::class,
            BranchSeeder::class,
            PaymentMethodSeeder::class,
        ]);
    }
}
