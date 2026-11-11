<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultAdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@mathematic.edu.kh'],
            [
                'name' => 'អ្នកគ្រប់គ្រងប្រព័ន្ធ',
                'phone' => '012345678',
                'role' => 'admin',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        $admin->assignRole('admin');

        $settings = [
            'school_name' => 'Mathematic and Scienties',
            'school_name_km' => 'សាលាគណិតវិទ្យា និងវិទ្យាសាស្ត្រ',
            'school_address' => 'ភ្នំពេញ, កម្ពុជា',
            'school_phone' => '012345678',
            'school_email' => 'info@mathematic.edu.kh',
            'currency' => '$',
        ];

        foreach ($settings as $key => $value) {
            SystemSetting::set($key, $value);
        }
    }
}
