<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@pieducation.test'],
            [
                'name' => 'Pi Education Admin',
                'password' => Hash::make('admin1234'),
                'phone' => null,
                'locale' => 'km',
                'email_verified_at' => now(),
                'status' => User::STATUS_ACTIVE,
                'vip_until' => now()->addYears(1),
            ]
        );

        $admin->assignRole(User::ROLE_ADMIN);
    }
}
