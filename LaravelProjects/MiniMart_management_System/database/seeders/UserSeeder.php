<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@minimart.com',
            'password' => 'password',
            'phone' => '1234567890',
            'role_id' => 1,
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Cashier User',
            'email' => 'cashier@minimart.com',
            'password' => 'password',
            'phone' => '0987654321',
            'role_id' => 2,
            'is_active' => true,
        ]);
    }
}
