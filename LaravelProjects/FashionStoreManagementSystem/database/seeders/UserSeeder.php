<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Admin User', 'email' => 'admin@fashionstore.test', 'role' => Role::Admin],
            ['name' => 'Manager User', 'email' => 'manager@fashionstore.test', 'role' => Role::Manager],
            ['name' => 'Cashier User', 'email' => 'cashier@fashionstore.test', 'role' => Role::Cashier],
            ['name' => 'Emma Wilson', 'email' => 'emma@fashionstore.test', 'role' => Role::Cashier],
            ['name' => 'Liam Turner', 'email' => 'liam@fashionstore.test', 'role' => Role::Cashier],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => 'password',
                    'role' => $user['role'],
                    'is_active' => true,
                ]
            );
        }
    }
}
