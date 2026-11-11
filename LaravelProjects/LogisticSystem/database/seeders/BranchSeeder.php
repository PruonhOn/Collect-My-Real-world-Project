<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@logistics.com')->first();

        if (!$admin) {
            $this->command->warn('Admin user not found. Run AdminUserSeeder first.');

            return;
        }

        $branches = [
            [
                'code' => 'PNH-HQ',
                'name' => 'Phnom Penh Headquarters',
                'address' => '#123, Street 456, Sangkat Tonle Bassac',
                'city' => 'Phnom Penh',
                'province' => 'Phnom Penh',
                'phone' => '+855 23 888 999',
                'email' => 'hq@cambodialogistics.com',
                'manager_name' => 'Super Admin',
                'is_active' => true,
                'created_by' => $admin->id,
            ],
            [
                'code' => 'REP',
                'name' => 'Siem Reap Branch',
                'address' => '#789, Street 123, Sangkat Svay Dangkum',
                'city' => 'Siem Reap',
                'province' => 'Siem Reap',
                'phone' => '+855 63 888 777',
                'email' => 'siemreap@cambodialogistics.com',
                'manager_name' => null,
                'is_active' => true,
                'created_by' => $admin->id,
            ],
            [
                'code' => 'SHV',
                'name' => 'Sihanoukville Branch',
                'address' => '#456, Street 789, Sangkat 4',
                'city' => 'Sihanoukville',
                'province' => 'Preah Sihanouk',
                'phone' => '+855 34 888 666',
                'email' => 'sihanoukville@cambodialogistics.com',
                'manager_name' => null,
                'is_active' => true,
                'created_by' => $admin->id,
            ],
        ];

        foreach ($branches as $branch) {
            Branch::firstOrCreate(
                ['code' => $branch['code']],
                $branch
            );
        }
    }
}
