<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            BrandSeeder::class,
            CategorySeeder::class,
            SupplierSeeder::class,
            CustomerSeeder::class,
            ProductSeeder::class,
            SaleSeeder::class,
            PurchaseSeeder::class,
        ]);

        $admin = User::firstOrCreate(
            ['email' => 'admin@knfashion.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('password'),
            ]
        );
        $admin->assignRole('Admin');

        $manager = User::firstOrCreate(
            ['email' => 'manager@knfashion.com'],
            [
                'name' => 'Manager',
                'password' => bcrypt('password'),
            ]
        );
        $manager->assignRole('Manager');

        $cashier = User::firstOrCreate(
            ['email' => 'cashier@knfashion.com'],
            [
                'name' => 'Cashier',
                'password' => bcrypt('password'),
            ]
        );
        $cashier->assignRole('Cashier');
    }
}
