<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;
use App\Models\Warehouse;
use App\Models\WarehouseZone;
use App\Models\WarehouseShelf;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        if (Branch::count() > 0) {
            return;
        }

        $branch = Branch::create([
            'code' => 'BR-PNH',
            'name' => 'Phnom Penh Headquarters',
            'address' => '#1, Street 102, Sangkat Wat Phnom, Khan Daun Penh',
            'city' => 'Phnom Penh',
            'state' => 'Phnom Penh',
            'postal_code' => '12201',
            'phone' => '+855 23 888 100',
            'email' => 'phnompenh@combodialogistics.com',
            'is_active' => true,
        ]);

        $warehouse = Warehouse::create([
            'branch_id' => $branch->id,
            'code' => 'WH-PNH-01',
            'name' => 'Phnom Penh Main Warehouse',
            'address' => 'Street 102, Sangkat Wat Phnom',
            'city' => 'Phnom Penh',
            'capacity' => 10000,
            'used_capacity' => 0,
            'manager_name' => 'Warehouse Manager',
            'phone' => '+855 23 888 101',
            'email' => 'wh1@combodialogistics.com',
            'is_active' => true,
        ]);

        $zones = ['A', 'B', 'C', 'D'];
        foreach ($zones as $zoneName) {
            $zone = WarehouseZone::create([
                'warehouse_id' => $warehouse->id,
                'code' => "ZONE-{$zoneName}",
                'name' => "Zone {$zoneName}",
                'description' => "Storage Zone {$zoneName}",
                'capacity' => 2500,
                'used' => 0,
                'is_active' => true,
            ]);

            for ($i = 1; $i <= 10; $i++) {
                WarehouseShelf::create([
                    'warehouse_zone_id' => $zone->id,
                    'code' => "{$zoneName}-{$i}",
                    'name' => "Shelf {$zoneName}-{$i}",
                    'max_weight' => 500,
                    'current_weight' => 0,
                    'is_active' => true,
                ]);
            }
        }

        $branch2 = Branch::create([
            'code' => 'BR-SRP',
            'name' => 'Siem Reap Branch',
            'address' => 'Street 6, Sangkat Svay Dangkum',
            'city' => 'Siem Reap',
            'state' => 'Siem Reap',
            'postal_code' => '17101',
            'phone' => '+855 63 888 200',
            'email' => 'siemreap@combodialogistics.com',
            'is_active' => true,
        ]);

        Warehouse::create([
            'branch_id' => $branch2->id,
            'code' => 'WH-SRP-01',
            'name' => 'Siem Reap Warehouse',
            'address' => 'Street 6, Svay Dangkum',
            'city' => 'Siem Reap',
            'capacity' => 5000,
            'used_capacity' => 0,
            'manager_name' => 'SR Manager',
            'phone' => '+855 63 888 201',
            'email' => 'whsr@combodialogistics.com',
            'is_active' => true,
        ]);

        Branch::create([
            'code' => 'BR-SHV',
            'name' => 'Sihanoukville Branch',
            'address' => 'Street 14, Sangkat 3',
            'city' => 'Sihanoukville',
            'state' => 'Preah Sihanouk',
            'postal_code' => '18101',
            'phone' => '+855 34 888 300',
            'email' => 'sihanoukville@combodialogistics.com',
            'is_active' => true,
        ]);
    }
}
