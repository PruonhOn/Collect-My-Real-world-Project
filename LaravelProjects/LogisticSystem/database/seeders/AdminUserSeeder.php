<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@logistics.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
                'is_active' => true,
            ]
        );

        $superAdminRole = Role::where('slug', 'super_admin')->first();
        if ($superAdminRole) {
            $superAdmin->assignRole($superAdminRole);
        }

        $branchManager = User::firstOrCreate(
            ['email' => 'manager@logistics.com'],
            [
                'name' => 'Branch Manager',
                'password' => bcrypt('password'),
                'is_active' => true,
            ]
        );

        $branchManagerRole = Role::where('slug', 'branch_manager')->first();
        if ($branchManagerRole) {
            $branchManager->assignRole($branchManagerRole);
        }
    }
}
