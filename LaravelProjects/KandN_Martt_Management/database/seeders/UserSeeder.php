<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@mart.com',
            'password' => Hash::make('password'),
            'phone' => '0123456789',
            'status' => 'active',
            'is_superadmin' => true,
        ]);
        $superAdmin->assignRole('Super Admin');

        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager@mart.com',
            'password' => Hash::make('password'),
            'phone' => '0123456790',
            'status' => 'active',
        ]);
        $manager->assignRole('Manager');

        $cashier = User::create([
            'name' => 'Cashier User',
            'email' => 'cashier@mart.com',
            'password' => Hash::make('password'),
            'phone' => '0123456791',
            'status' => 'active',
        ]);
        $cashier->assignRole('Cashier');

        $inventoryStaff = User::create([
            'name' => 'Inventory Staff',
            'email' => 'inventory@mart.com',
            'password' => Hash::make('password'),
            'phone' => '0123456792',
            'status' => 'active',
        ]);
        $inventoryStaff->assignRole('Inventory Staff');

        $accountant = User::create([
            'name' => 'Accountant User',
            'email' => 'accountant@mart.com',
            'password' => Hash::make('password'),
            'phone' => '0123456793',
            'status' => 'active',
        ]);
        $accountant->assignRole('Accountant');
    }
}
