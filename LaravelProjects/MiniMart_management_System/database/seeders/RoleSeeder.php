<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::create([
            'name' => 'Admin',
            'slug' => 'admin',
            'description' => 'Full system access',
        ]);

        Role::create([
            'name' => 'Cashier',
            'slug' => 'cashier',
            'description' => 'POS and sales access only',
        ]);
    }
}
