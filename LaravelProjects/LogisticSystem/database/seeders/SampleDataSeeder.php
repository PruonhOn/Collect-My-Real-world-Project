<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\DeliveryStop;
use App\Models\Driver;
use App\Models\Expense;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Route;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShipmentStatus;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleMaintenance;
use App\Models\VehicleType;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SampleDataSeeder extends Seeder
{
    private User $admin;
    private User $manager;

    public function run(): void
    {
        $this->admin = User::where('email', 'admin@logistics.com')->first();
        $this->manager = User::where('email', 'manager@logistics.com')->first();

        if (!$this->admin || !$this->manager) {
            $this->command->error('Admin users not found. Run AdminUserSeeder first.');
            return;
        }

        $this->seedVehicleTypes();
        $this->seedBranches();
        $this->seedCustomers();
        $this->seedVehicles();
        $this->seedDrivers();
        $this->seedRoutes();
        $this->seedWarehouses();
        $this->seedWarehouseLocations();
        $this->seedShipments();
        $this->seedDeliveries();
        $this->seedInvoices();
        $this->seedPayments();
        $this->seedTransactions();
        $this->seedExpenses();
        $this->seedInventory();
        $this->seedNotifications();

        $this->command->info('Sample data seeded successfully!');
    }

    private function seedVehicleTypes(): void
    {
        $types = [
            ['name' => 'Truck', 'slug' => 'truck', 'description' => 'Heavy-duty cargo truck'],
            ['name' => 'Van', 'slug' => 'van', 'description' => 'Light commercial van'],
            ['name' => 'Pickup', 'slug' => 'pickup', 'description' => 'Pickup truck for small loads'],
            ['name' => 'Container', 'slug' => 'container', 'description' => 'Container truck for large shipments'],
            ['name' => 'Motorcycle', 'slug' => 'motorcycle', 'description' => 'Motorcycle for express deliveries'],
        ];

        foreach ($types as $type) {
            VehicleType::firstOrCreate(['slug' => $type['slug']], $type);
        }
    }

    private function seedBranches(): void
    {
        $branches = [
            [
                'code' => 'PNH-HQ', 'name' => 'Phnom Penh Headquarters',
                'address' => '#123, Street 456, Sangkat Tonle Bassac',
                'city' => 'Phnom Penh', 'province' => 'Phnom Penh',
                'phone' => '+855 23 888 999', 'email' => 'hq@logistics.com',
                'manager_name' => 'Super Admin', 'is_active' => true,
            ],
            [
                'code' => 'REP', 'name' => 'Siem Reap Branch',
                'address' => '#789, Street 123, Sangkat Svay Dangkum',
                'city' => 'Siem Reap', 'province' => 'Siem Reap',
                'phone' => '+855 63 888 777', 'email' => 'siemreap@logistics.com',
                'manager_name' => 'Branch Manager', 'is_active' => true,
            ],
            [
                'code' => 'SHV', 'name' => 'Sihanoukville Branch',
                'address' => '#456, Street 789, Sangkat 4',
                'city' => 'Sihanoukville', 'province' => 'Preah Sihanouk',
                'phone' => '+855 34 888 666', 'email' => 'shv@logistics.com',
                'manager_name' => null, 'is_active' => true,
            ],
            [
                'code' => 'BTB', 'name' => 'Battambang Branch',
                'address' => '#12, Street 34, Sangkat Svay Por',
                'city' => 'Battambang', 'province' => 'Battambang',
                'phone' => '+855 53 888 555', 'email' => 'battambang@logistics.com',
                'manager_name' => null, 'is_active' => true,
            ],
            [
                'code' => 'KPS', 'name' => 'Kampot Branch',
                'address' => '#78, Street 90, Sangkat Kampong Kandal',
                'city' => 'Kampot', 'province' => 'Kampot',
                'phone' => '+855 33 888 444', 'email' => 'kampot@logistics.com',
                'manager_name' => null, 'is_active' => true,
            ],
        ];

        foreach ($branches as $data) {
            $data['created_by'] = $this->admin->id;
            Branch::firstOrCreate(['code' => $data['code']], $data);
        }
    }

    private function seedCustomers(): void
    {
        $customers = [
            ['CUST-001', 'Nokor Mart', '012 345 678', 'info@nokormart.com', 'Nokor Mart Co., Ltd.', 'N0001-2345', 'Street 271', 'Phnom Penh', 'Phnom Penh', 'Vann Sophea', '012 345 001', 50000, 0],
            ['CUST-002', 'Asia Garment', '012 345 679', 'info@asiagarment.com', 'Asia Garment Factory', 'A0002-6789', 'Kampong Speu Rd', 'Phnom Penh', 'Kandal', 'Chea Sothy', '012 345 002', 75000, 0],
            ['CUST-003', 'Mekong Electronics', '012 345 680', 'sales@mekongelec.com', 'Mekong Electronics Shop', 'M0003-1357', 'Monivong Blvd', 'Phnom Penh', 'Phnom Penh', 'Heng Seiha', '012 345 003', 30000, 0],
            ['CUST-004', 'Siem Reap Souvenirs', '012 345 681', 'order@souvenirshop.com', 'Souvenir Shop Co.', 'S0004-2468', 'Pub Street', 'Siem Reap', 'Siem Reap', 'Thida Rathanak', '012 345 004', 15000, 0],
            ['CUST-005', 'Kampot Pepper Export', '012 345 682', 'sales@kampotpepper.com', 'Kampot Pepper Co.', 'K0005-3690', 'River Road', 'Kampot', 'Kampot', 'Sok Serey', '012 345 005', 40000, 0],
            ['CUST-006', 'Royal Phnom Penh Hotel', '012 345 683', 'purchasing@royalpp.com', 'Royal Hotel Group', 'R0006-4812', 'Sothearos Blvd', 'Phnom Penh', 'Phnom Penh', 'Lim Srey Mom', '012 345 006', 60000, 0],
            ['CUST-007', 'Battambang Rice Mill', '012 345 684', 'info@btrice.com', 'BT Rice Export Ltd.', 'B0007-5103', 'National Road 5', 'Battambang', 'Battambang', 'Meas Chantha', '012 345 007', 55000, 0],
            ['CUST-008', 'Sihanoukville Port Supply', '012 345 685', 'supply@shvport.com', 'Port Supply Chain Inc.', 'P0008-6247', 'Port Road', 'Sihanoukville', 'Preah Sihanouk', 'Sokha Makara', '012 345 008', 80000, 0],
            ['CUST-009', 'Angkor Wat Handicrafts', '012 345 686', 'info@angkorcrafts.com', 'Handicraft Export Co.', 'H0009-7385', 'Airport Road', 'Siem Reap', 'Siem Reap', 'Rithy Phalla', '012 345 009', 20000, 0],
            ['CUST-010', 'Phnom Penh Auto Parts', '012 345 687', 'parts@ppauto.com', 'PP Auto Parts Import', 'P0010-8429', 'Russian Blvd', 'Phnom Penh', 'Phnom Penh', 'Sokun Theara', '012 345 010', 35000, 0],
            ['CUST-011', 'Cambodia Food Import', '012 345 688', 'info@camfood.com', 'Cambodia Food Distributor', 'C0012-9513', 'Street 598', 'Phnom Penh', 'Phnom Penh', 'Mey Sopheak', '012 345 011', 45000, 0],
            ['CUST-012', 'Techo Medical Supply', '012 345 689', 'order@techomed.com', 'Techo Medical Ltd.', 'T0013-0647', 'Street 271', 'Phnom Penh', 'Phnom Penh', 'Chan Vatana', '012 345 012', 65000, 0],
        ];

        foreach ($customers as $c) {
            Customer::firstOrCreate(['code' => $c[0]], [
                'code' => $c[0], 'name' => $c[1], 'phone' => $c[2], 'email' => $c[3],
                'company' => $c[4], 'tax_number' => $c[5], 'address' => $c[6],
                'city' => $c[7], 'province' => $c[8], 'country' => 'Cambodia',
                'contact_person' => $c[9], 'contact_phone' => $c[10],
                'credit_limit' => $c[11], 'outstanding_balance' => $c[12],
                'is_active' => true, 'created_by' => $this->admin->id,
            ]);
        }
    }

    private function seedVehicles(): void
    {
        $truckType = VehicleType::where('slug', 'truck')->first()->id;
        $vanType = VehicleType::where('slug', 'van')->first()->id;
        $pickupType = VehicleType::where('slug', 'pickup')->first()->id;
        $containerType = VehicleType::where('slug', 'container')->first()->id;
        $motorType = VehicleType::where('slug', 'motorcycle')->first()->id;
        $pp = Branch::where('code', 'PNH-HQ')->first()->id;
        $sr = Branch::where('code', 'REP')->first()->id;
        $shv = Branch::where('code', 'SHV')->first()->id;

        $vehicles = [
            ['VH-001', 'PP-1234', 'Toyota', 'Hilux', 2022, 'White', 1500, 8.5, 'Diesel', 8.5, 'INS-001', '2025-12-31', 'REG-001', '2025-12-31', 'active', 'owned', 45000, '2022-01-15', true, $pickupType, $pp],
            ['VH-002', 'PP-5678', 'Isuzu', 'N-Series', 2023, 'Blue', 5000, 25.0, 'Diesel', 6.2, 'INS-002', '2026-06-30', 'REG-002', '2026-06-30', 'active', 'owned', 75000, '2023-03-20', true, $truckType, $pp],
            ['VH-003', 'SR-9101', 'Nissan', 'NV350', 2021, 'Silver', 1200, 6.0, 'Diesel', 10.0, 'INS-003', '2025-09-15', 'REG-003', '2025-09-15', 'active', 'owned', 35000, '2021-05-10', true, $vanType, $sr],
            ['VH-004', 'SHV-1121', 'Hino', '700 Series', 2023, 'Red', 10000, 45.0, 'Diesel', 4.5, 'INS-004', '2026-12-31', 'REG-004', '2026-12-31', 'active', 'owned', 120000, '2023-08-01', true, $containerType, $shv],
            ['VH-005', 'PP-3141', 'Honda', 'Wave 125', 2024, 'Red', 50, 0.1, 'Gasoline', 45.0, 'INS-005', '2025-03-01', 'REG-005', '2025-03-01', 'active', 'owned', 2000, '2024-01-10', true, $motorType, $pp],
            ['VH-006', 'PP-5161', 'Toyota', 'Hiace', 2022, 'White', 1800, 9.0, 'Diesel', 9.2, 'INS-006', '2025-10-31', 'REG-006', '2025-10-31', 'active', 'owned', 42000, '2022-07-20', true, $vanType, $pp],
            ['VH-007', 'BTB-7181', 'Mitsubishi', 'Triton', 2023, 'Black', 1200, 6.5, 'Diesel', 9.0, 'INS-007', '2026-04-30', 'REG-007', '2026-04-30', 'active', 'leased', 28000, '2023-02-15', true, $pickupType, Branch::where('code', 'BTB')->first()->id],
            ['VH-008', 'KPS-9202', 'Isuzu', 'Q-Series', 2021, 'White', 4000, 20.0, 'Diesel', 7.0, 'INS-008', '2025-08-31', 'REG-008', '2025-08-31', 'active', 'owned', 65000, '2021-11-01', true, $truckType, Branch::where('code', 'KPS')->first()->id],
            ['VH-009', 'PP-1223', 'Hyundai', 'Staria', 2024, 'Gray', 1000, 4.0, 'Diesel', 10.5, 'INS-009', '2027-01-31', 'REG-009', '2027-01-31', 'active', 'owned', 38000, '2024-05-10', true, $vanType, $pp],
            ['VH-010', 'SR-3243', 'Toyota', 'Hilux', 2020, 'Silver', 1300, 7.0, 'Diesel', 9.5, 'INS-010', '2025-11-30', 'REG-010', '2025-11-30', 'under_maintenance', 'owned', 40000, '2020-09-20', false, $pickupType, $sr],
        ];

        foreach ($vehicles as $v) {
            Vehicle::firstOrCreate(['code' => $v[0]], [
                'code' => $v[0], 'plate_number' => $v[1], 'make' => $v[2], 'model' => $v[3],
                'year' => $v[4], 'color' => $v[5], 'capacity_weight' => $v[6], 'capacity_volume' => $v[7],
                'fuel_type' => $v[8], 'fuel_consumption' => $v[9], 'insurance_number' => $v[10],
                'insurance_expiry' => $v[11], 'registration_number' => $v[12], 'registration_expiry' => $v[13],
                'status' => $v[14], 'ownership' => $v[15], 'purchase_price' => $v[16], 'purchase_date' => $v[17],
                'is_active' => $v[18], 'vehicle_type_id' => $v[19], 'branch_id' => $v[20],
                'created_by' => $this->admin->id,
            ]);
        }

        VehicleMaintenance::create([
            'reference_no' => 'MAINT-001',
            'maintenance_type' => 'major',
            'description' => 'Engine overhaul and transmission check',
            'maintenance_date' => '2024-12-15',
            'next_maintenance_date' => '2025-06-15',
            'cost' => 2500,
            'vendor' => 'Toyota Cambodia Service Center',
            'status' => 'completed',
            'notes' => 'Regular scheduled maintenance',
            'vehicle_id' => Vehicle::where('code', 'VH-001')->first()->id,
            'created_by' => $this->admin->id,
        ]);

        VehicleMaintenance::create([
            'reference_no' => 'MAINT-002',
            'maintenance_type' => 'repair',
            'description' => 'Brake system replacement and tire rotation',
            'maintenance_date' => '2025-01-10',
            'next_maintenance_date' => '2025-07-10',
            'cost' => 1200,
            'vendor' => 'Isuzu Service Center',
            'status' => 'completed',
            'notes' => null,
            'vehicle_id' => Vehicle::where('code', 'VH-002')->first()->id,
            'created_by' => $this->admin->id,
        ]);

        VehicleMaintenance::create([
            'reference_no' => 'MAINT-003',
            'maintenance_type' => 'repair',
            'description' => 'AC repair and electrical system check',
            'maintenance_date' => '2025-02-20',
            'cost' => 800,
            'vendor' => 'Auto Service Cambodia',
            'status' => 'in_progress',
            'notes' => 'Submitted for repair',
            'vehicle_id' => Vehicle::where('code', 'VH-010')->first()->id,
            'created_by' => $this->admin->id,
        ]);
    }

    private function seedDrivers(): void
    {
        $pp = Branch::where('code', 'PNH-HQ')->first()->id;
        $sr = Branch::where('code', 'REP')->first()->id;
        $shv = Branch::where('code', 'SHV')->first()->id;
        $btb = Branch::where('code', 'BTB')->first()->id;
        $kps = Branch::where('code', 'KPS')->first()->id;

        $drivers = [
            ['DRV-001', 'Sokha Dara', '012 111 111', 'sokha.dara@logistics.com', 'L-00124', 'B', '2026-06-30', '1990-03-15', 'NID-001', 'Phnom Penh', 'Kim Srey', '097 111 0001', 'active', '2022-01-15', 600, true, $pp],
            ['DRV-002', 'Meas Bora', '012 111 112', 'meas.bora@logistics.com', 'L-00125', 'C', '2025-12-31', '1988-07-22', 'NID-002', 'Phnom Penh', 'Kunthea Sarin', '097 111 0002', 'active', '2022-03-01', 650, true, $pp],
            ['DRV-003', 'Chea Vannak', '012 111 113', 'chea.vannak@logistics.com', 'L-00126', 'B', '2026-09-15', '1992-11-10', 'NID-003', 'Siem Reap', 'Phalla Rith', '097 111 0003', 'active', '2022-06-01', 550, true, $sr],
            ['DRV-004', 'Heng Sreymom', '012 111 114', 'heng.sreymom@logistics.com', 'L-00127', 'C', '2025-08-20', '1991-05-05', 'NID-004', 'Sihanoukville', 'Sophea Sok', '097 111 0004', 'active', '2022-09-01', 580, true, $shv],
            ['DRV-005', 'Lim Chantha', '012 111 115', 'lim.chantha@logistics.com', 'L-00128', 'B', '2027-03-31', '1989-12-25', 'NID-005', 'Battambang', 'Sokhom Vibol', '097 111 0005', 'active', '2023-01-15', 500, true, $btb],
            ['DRV-006', 'Nop Rithy', '012 111 116', 'nop.rithy@logistics.com', 'L-00129', 'C', '2025-11-30', '1993-09-08', 'NID-006', 'Kampot', 'Bun Sopheak', '097 111 0006', 'active', '2023-04-01', 520, true, $kps],
            ['DRV-007', 'Suy Virak', '012 111 117', 'suy.virak@logistics.com', 'L-00130', 'A', '2026-05-15', '1987-04-18', 'NID-007', 'Phnom Penh', 'Mey Rathana', '097 111 0007', 'active', '2023-06-01', 700, true, $pp],
            ['DRV-008', 'Pich Makara', '012 111 118', 'pich.makara@logistics.com', 'L-00131', 'B', '2025-10-31', '1994-08-30', 'NID-008', 'Siem Reap', 'Chheang Davy', '097 111 0008', 'inactive', '2023-09-01', 450, true, $sr],
            ['DRV-009', 'So Sophea', '012 111 119', 'so.sophea@logistics.com', 'L-00132', 'C', '2027-01-31', '1990-06-12', 'NID-009', 'Phnom Penh', 'Chan Vuthy', '097 111 0009', 'active', '2024-01-01', 630, true, $pp],
            ['DRV-010', 'Tep Sothun', '012 111 120', 'tep.sothun@logistics.com', 'L-00133', 'B', '2026-08-31', '1991-02-14', 'NID-010', 'Sihanoukville', 'Hok Somaly', '097 111 0010', 'active', '2024-03-01', 550, true, $shv],
        ];

        foreach ($drivers as $d) {
            Driver::firstOrCreate(['code' => $d[0]], [
                'code' => $d[0], 'name' => $d[1], 'phone' => $d[2], 'email' => $d[3],
                'license_number' => $d[4], 'license_type' => $d[5], 'license_expiry' => $d[6],
                'date_of_birth' => $d[7], 'national_id' => $d[8], 'address' => $d[9],
                'emergency_contact' => $d[10], 'emergency_phone' => $d[11],
                'status' => $d[12], 'hire_date' => $d[13], 'salary' => $d[14],
                'is_active' => $d[15], 'branch_id' => $d[16], 'created_by' => $this->admin->id,
            ]);
        }
    }

    private function seedRoutes(): void
    {
        $routes = [
            ['RTE-001', 'PP → SR Express', 'Phnom Penh', 'Siem Reap', 314, 5.5, 'Express route via National Road 6', true],
            ['RTE-002', 'PP → SHV Freight', 'Phnom Penh', 'Sihanoukville', 230, 4.0, 'Freight route via National Road 4', true],
            ['RTE-003', 'SR → PP Return', 'Siem Reap', 'Phnom Penh', 314, 5.5, 'Return route via National Road 6', true],
            ['RTE-004', 'PP → BTB Local', 'Phnom Penh', 'Battambang', 291, 5.0, 'Local route via National Road 5', true],
            ['RTE-005', 'SHV → KPS Coastal', 'Sihanoukville', 'Kampot', 88, 1.5, 'Coastal route via National Road 3', true],
            ['RTE-006', 'PP → KPS Southern', 'Phnom Penh', 'Kampot', 148, 2.5, 'Southern route via National Road 3', true],
        ];

        foreach ($routes as $r) {
            Route::firstOrCreate(['code' => $r[0]], [
                'code' => $r[0], 'name' => $r[1], 'origin_city' => $r[2],
                'destination_city' => $r[3], 'distance_km' => $r[4],
                'estimated_hours' => $r[5], 'description' => $r[6],
                'is_active' => $r[7], 'created_by' => $this->admin->id,
            ]);
        }
    }

    private function seedWarehouses(): void
    {
        $pp = Branch::where('code', 'PNH-HQ')->first()->id;
        $sr = Branch::where('code', 'REP')->first()->id;
        $shv = Branch::where('code', 'SHV')->first()->id;

        $warehouses = [
            ['WH-001', 'Phnom Penh Main Warehouse', 'Street 271, Phnom Penh', 'Phnom Penh', 'Phnom Penh', '+855 23 888 111', 'wh.pp@logistics.com', 'Sok Chea', 50000, 15000, 2500, true, $pp],
            ['WH-002', 'Siem Reap Logistics Hub', 'Airport Road, Siem Reap', 'Siem Reap', 'Siem Reap', '+855 63 888 222', 'wh.sr@logistics.com', 'Lay Vanny', 30000, 8000, 1800, true, $sr],
            ['WH-003', 'Sihanoukville Port Warehouse', 'Port Road, Sihanoukville', 'Sihanoukville', 'Preah Sihanouk', '+855 34 888 333', 'wh.shv@logistics.com', 'Heng Navy', 40000, 20000, 3000, true, $shv],
        ];

        foreach ($warehouses as $w) {
            Warehouse::firstOrCreate(['code' => $w[0]], [
                'code' => $w[0], 'name' => $w[1], 'address' => $w[2], 'city' => $w[3],
                'province' => $w[4], 'phone' => $w[5], 'email' => $w[6],
                'manager_name' => $w[7], 'total_capacity' => $w[8], 'used_capacity' => $w[9],
                'total_area' => $w[10], 'is_active' => $w[11], 'branch_id' => $w[12],
                'created_by' => $this->admin->id,
            ]);
        }
    }

    private function seedWarehouseLocations(): void
    {
        $whPP = Warehouse::where('code', 'WH-001')->first()->id;
        $whSR = Warehouse::where('code', 'WH-002')->first()->id;
        $whSHV = Warehouse::where('code', 'WH-003')->first()->id;

        $locations = [
            ['LOC-PP-A1', 'Zone A - Rack 1', 'A', '1', 'R1', 'S1', 'B1', 1000, 5.0, false, true, $whPP],
            ['LOC-PP-A2', 'Zone A - Rack 2', 'A', '2', 'R1', 'S2', 'B1', 1000, 5.0, false, true, $whPP],
            ['LOC-PP-B1', 'Zone B - Rack 1', 'B', '1', 'R1', 'S1', 'B1', 800, 4.0, false, true, $whPP],
            ['LOC-SR-A1', 'Zone A - Rack 1', 'A', '1', 'R1', 'S1', 'B1', 600, 3.0, false, true, $whSR],
            ['LOC-SR-A2', 'Zone A - Rack 2', 'A', '2', 'R1', 'S2', 'B1', 600, 3.0, false, true, $whSR],
            ['LOC-SHV-A1', 'Zone A - Rack 1', 'A', '1', 'R1', 'S1', 'B1', 1200, 6.0, false, true, $whSHV],
            ['LOC-SHV-B1', 'Zone B - Rack 1', 'B', '1', 'R1', 'S1', 'B1', 1000, 5.0, false, true, $whSHV],
            ['LOC-SHV-B2', 'Zone B - Rack 2', 'B', '2', 'R1', 'S2', 'B1', 1000, 5.0, false, true, $whSHV],
        ];

        foreach ($locations as $l) {
            WarehouseLocation::firstOrCreate(['code' => $l[0]], [
                'code' => $l[0], 'name' => $l[1], 'zone' => $l[2], 'aisle' => $l[3],
                'rack' => $l[4], 'shelf' => $l[5], 'bin' => $l[6], 'max_weight' => $l[7],
                'max_volume' => $l[8], 'is_occupied' => $l[9], 'is_active' => $l[10],
                'warehouse_id' => $l[11],
            ]);
        }
    }

    private function seedShipments(): void
    {
        $customers = Customer::all();
        $branches = Branch::all()->keyBy('code');
        $drivers = Driver::where('status', 'active')->get();
        $vehicles = Vehicle::where('status', 'active')->get();

        $shipments = [
            ['status' => 'delivered', 'type' => 'standard', 'express' => false, 'customer_idx' => 0, 'origin' => 'PNH-HQ', 'dest' => 'REP', 'driver_idx' => 0, 'vehicle_idx' => 0],
            ['status' => 'in_transit', 'type' => 'express', 'express' => true, 'customer_idx' => 1, 'origin' => 'PNH-HQ', 'dest' => 'SHV', 'driver_idx' => 1, 'vehicle_idx' => 1],
            ['status' => 'pending', 'type' => 'standard', 'express' => false, 'customer_idx' => 2, 'origin' => 'PNH-HQ', 'dest' => 'BTB', 'driver_idx' => 4, 'vehicle_idx' => 6],
            ['status' => 'delivered', 'type' => 'express', 'express' => true, 'customer_idx' => 3, 'origin' => 'REP', 'dest' => 'PNH-HQ', 'driver_idx' => 2, 'vehicle_idx' => 2],
            ['status' => 'in_transit', 'type' => 'standard', 'express' => false, 'customer_idx' => 4, 'origin' => 'KPS', 'dest' => 'PNH-HQ', 'driver_idx' => 5, 'vehicle_idx' => 7],
            ['status' => 'delivered', 'type' => 'express', 'express' => true, 'customer_idx' => 5, 'origin' => 'PNH-HQ', 'dest' => 'PNH-HQ', 'driver_idx' => 6, 'vehicle_idx' => 5],
            ['status' => 'cancelled', 'type' => 'standard', 'express' => false, 'customer_idx' => 6, 'origin' => 'BTB', 'dest' => 'PNH-HQ', 'driver_idx' => 4, 'vehicle_idx' => 6],
            ['status' => 'delivered', 'type' => 'standard', 'express' => false, 'customer_idx' => 7, 'origin' => 'SHV', 'dest' => 'KPS', 'driver_idx' => 3, 'vehicle_idx' => 3],
            ['status' => 'pending', 'type' => 'express', 'express' => true, 'customer_idx' => 8, 'origin' => 'REP', 'dest' => 'PNH-HQ', 'driver_idx' => 2, 'vehicle_idx' => 2],
            ['status' => 'delivered', 'type' => 'standard', 'express' => false, 'customer_idx' => 9, 'origin' => 'PNH-HQ', 'dest' => 'SHV', 'driver_idx' => 7, 'vehicle_idx' => 0],
            ['status' => 'in_transit', 'type' => 'standard', 'express' => false, 'customer_idx' => 10, 'origin' => 'PNH-HQ', 'dest' => 'REP', 'driver_idx' => 0, 'vehicle_idx' => 0],
            ['status' => 'delivered', 'type' => 'express', 'express' => true, 'customer_idx' => 11, 'origin' => 'PNH-HQ', 'dest' => 'PNH-HQ', 'driver_idx' => 6, 'vehicle_idx' => 5],
            ['status' => 'pending', 'type' => 'standard', 'express' => false, 'customer_idx' => 0, 'origin' => 'PNH-HQ', 'dest' => 'KPS', 'driver_idx' => 5, 'vehicle_idx' => 7],
            ['status' => 'delivered', 'type' => 'express', 'express' => true, 'customer_idx' => 2, 'origin' => 'PNH-HQ', 'dest' => 'REP', 'driver_idx' => 0, 'vehicle_idx' => 3],
            ['status' => 'in_transit', 'type' => 'standard', 'express' => false, 'customer_idx' => 5, 'origin' => 'PNH-HQ', 'dest' => 'BTB', 'driver_idx' => 4, 'vehicle_idx' => 6],
            ['status' => 'delivered', 'type' => 'standard', 'express' => false, 'customer_idx' => 1, 'origin' => 'PNH-HQ', 'dest' => 'PNH-HQ', 'driver_idx' => 9, 'vehicle_idx' => 8],
            ['status' => 'cancelled', 'type' => 'express', 'express' => true, 'customer_idx' => 7, 'origin' => 'SHV', 'dest' => 'PNH-HQ', 'driver_idx' => 3, 'vehicle_idx' => 3],
            ['status' => 'delivered', 'type' => 'standard', 'express' => false, 'customer_idx' => 3, 'origin' => 'REP', 'dest' => 'REP', 'driver_idx' => 2, 'vehicle_idx' => 2],
            ['status' => 'pending', 'type' => 'standard', 'express' => false, 'customer_idx' => 9, 'origin' => 'SHV', 'dest' => 'PNH-HQ', 'driver_idx' => 9, 'vehicle_idx' => 8],
            ['status' => 'delivered', 'type' => 'express', 'express' => true, 'customer_idx' => 4, 'origin' => 'KPS', 'dest' => 'PNH-HQ', 'driver_idx' => 5, 'vehicle_idx' => 7],
            ['status' => 'delivered', 'type' => 'standard', 'express' => false, 'customer_idx' => 6, 'origin' => 'BTB', 'dest' => 'PNH-HQ', 'driver_idx' => 6, 'vehicle_idx' => 5],
            ['status' => 'in_transit', 'type' => 'express', 'express' => true, 'customer_idx' => 8, 'origin' => 'REP', 'dest' => 'SHV', 'driver_idx' => 2, 'vehicle_idx' => 3],
            ['status' => 'delivered', 'type' => 'standard', 'express' => false, 'customer_idx' => 10, 'origin' => 'PNH-HQ', 'dest' => 'PNH-HQ', 'driver_idx' => 0, 'vehicle_idx' => 5],
            ['status' => 'in_transit', 'type' => 'standard', 'express' => false, 'customer_idx' => 11, 'origin' => 'PNH-HQ', 'dest' => 'SHV', 'driver_idx' => 1, 'vehicle_idx' => 4],
            ['status' => 'pending', 'type' => 'express', 'express' => true, 'customer_idx' => 0, 'origin' => 'PNH-HQ', 'dest' => 'REP', 'driver_idx' => 7, 'vehicle_idx' => 8],
        ];

        $cityNames = [
            'PNH-HQ' => ['Phnom Penh', 'Phnom Penh', 'Phnom Penh', '#123 Street 456'],
            'REP' => ['Siem Reap', 'Siem Reap', 'Siem Reap', '#789 Street 123'],
            'SHV' => ['Sihanoukville', 'Preah Sihanouk', 'Sihanoukville', '#456 Street 789'],
            'BTB' => ['Battambang', 'Battambang', 'Battambang', '#12 Street 34'],
            'KPS' => ['Kampot', 'Kampot', 'Kampot', '#78 Street 90'],
        ];

        $itemNames = ['Electronics', 'Clothing', 'Food Products', 'Construction Materials', 'Pharmaceuticals', 'Automotive Parts', 'Furniture', 'Agricultural Products', 'Machinery', 'Consumer Goods'];

        foreach ($shipments as $idx => $s) {
            $daysAgo = rand(1, 45);
            $createdAt = now()->subDays($daysAgo);
            $customer = $customers[$s['customer_idx']];
            $originBranch = $branches[$s['origin']];
            $destBranch = $branches[$s['dest']];
            $driver = $s['driver_idx'] !== null ? $drivers[$s['driver_idx']] ?? null : null;
            $vehicle = $s['vehicle_idx'] !== null ? $vehicles[$s['vehicle_idx']] ?? null : null;

            $trackingNumber = 'SHP-' . strtoupper(Str::random(8));
            $weight = round(rand(10, 5000) / 10, 2);
            $volume = round(rand(1, 200) / 10, 2);
            $pieces = rand(1, 20);
            $declaredValue = round(rand(50, 50000), 2);

            $shipmentDate = $createdAt->copy()->addDays(rand(0, 2));
            $estDelivery = $s['type'] === 'express' ? $shipmentDate->copy()->addDays(rand(1, 3)) : $shipmentDate->copy()->addDays(rand(3, 7));

            $shipment = Shipment::create([
                'tracking_number' => $trackingNumber,
                'shipment_type' => $s['type'],
                'status' => $s['status'],
                'origin_address' => $cityNames[$s['origin']][3],
                'origin_city' => $cityNames[$s['origin']][0],
                'origin_province' => $cityNames[$s['origin']][1],
                'destination_address' => $cityNames[$s['dest']][3],
                'destination_city' => $cityNames[$s['dest']][0],
                'destination_province' => $cityNames[$s['dest']][1],
                'shipper_name' => $customer->name,
                'shipper_phone' => $customer->phone,
                'shipper_email' => $customer->email,
                'consignee_name' => 'Recipient at ' . $cityNames[$s['dest']][0],
                'consignee_phone' => '+855 12 ' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT),
                'consignee_email' => 'recipient_' . Str::random(5) . '@example.com',
                'weight' => $weight,
                'volume' => $volume,
                'pieces' => $pieces,
                'description' => 'Mixed cargo shipment',
                'declared_value' => $declaredValue,
                'currency' => 'USD',
                'shipment_date' => $shipmentDate,
                'estimated_delivery' => $estDelivery,
                'actual_delivery' => $s['status'] === 'delivered' ? $estDelivery->copy()->addDays(rand(-1, 2)) : null,
                'is_express' => $s['express'],
                'is_insured' => (bool) rand(0, 1),
                'insurance_amount' => rand(0, 1) ? round($declaredValue * 0.1, 2) : 0,
                'notes' => $s['express'] ? 'Express delivery - priority handling' : null,
                'customer_id' => $customer->id,
                'origin_branch_id' => $originBranch->id,
                'destination_branch_id' => $destBranch->id,
                'assigned_driver_id' => $driver?->id,
                'assigned_vehicle_id' => $vehicle?->id,
                'created_by' => $this->admin->id,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $statusSequence = ['pending', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered'];
            $statusIdx = array_search($s['status'], $statusSequence);
            if ($statusIdx === false) {
                $statusIdx = $s['status'] === 'cancelled' ? 2 : 0;
            }

            $descMap = [
                'pending' => 'Shipment created and pending pickup',
                'picked_up' => 'Package picked up from shipper',
                'in_transit' => 'Shipment is in transit to destination',
                'out_for_delivery' => 'Package out for final delivery',
                'delivered' => 'Package delivered successfully',
                'cancelled' => 'Shipment has been cancelled',
            ];

            foreach ($statusSequence as $i => $st) {
                if ($i <= $statusIdx) {
                    $statusTime = $createdAt->copy()->addHours(($i + 1) * rand(4, 12));
                    ShipmentStatus::create([
                        'status' => $st,
                        'description' => $descMap[$st] ?? 'Status update',
                        'location' => $i === 0 ? $cityNames[$s['origin']][0] : ($i >= 4 ? $cityNames[$s['dest']][0] : 'En Route'),
                        'shipment_id' => $shipment->id,
                        'updated_by' => $this->admin->id,
                        'created_at' => $statusTime,
                        'updated_at' => $statusTime,
                    ]);
                }
            }

            $numItems = rand(1, 4);
            for ($i = 0; $i < $numItems; $i++) {
                $qty = rand(1, 10);
                $unitValue = round($declaredValue / ($numItems * $qty), 2);
                ShipmentItem::create([
                    'name' => $itemNames[array_rand($itemNames)],
                    'description' => 'Item description for shipment',
                    'quantity' => $qty,
                    'weight' => round($weight / $numItems, 2),
                    'volume' => round($volume / $numItems, 4),
                    'value' => $unitValue,
                    'hs_code' => rand(1000, 9999) . '.' . rand(10, 99),
                    'shipment_id' => $shipment->id,
                ]);
            }
        }
    }

    private function seedDeliveries(): void
    {
        $deliveredShipments = Shipment::where('status', 'delivered')->get();
        $inTransitShipments = Shipment::where('status', 'in_transit')->get();
        $drivers = Driver::where('status', 'active')->get();
        $vehicles = Vehicle::where('status', 'active')->get();
        $routes = Route::all();

        foreach ($deliveredShipments as $i => $shipment) {
            $driver = $drivers[$i % $drivers->count()];
            $vehicle = $vehicles[$i % $vehicles->count()];
            $route = $routes[$i % $routes->count()];

            $scheduledDate = $shipment->shipment_date->copy()->addDay();
            $departedAt = $scheduledDate->copy()->setTime(8, rand(0, 59));
            $deliveredAt = $departedAt->copy()->addHours(rand(2, 8));

            $delivery = Delivery::create([
                'delivery_number' => 'DEL-' . strtoupper(Str::random(8)),
                'scheduled_date' => $scheduledDate,
                'departed_at' => $departedAt,
                'delivered_at' => $deliveredAt,
                'status' => 'delivered',
                'delivery_notes' => 'Successfully delivered to recipient',
                'recipient_name' => 'Sok ' . Str::random(5),
                'recipient_phone' => '+855 12 ' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT),
                'recipient_signature' => 'data:image/png;base64,signature_placeholder',
                'shipment_id' => $shipment->id,
                'route_id' => $route->id,
                'driver_id' => $driver->id,
                'vehicle_id' => $vehicle->id,
                'assigned_by' => $this->admin->id,
                'created_at' => $scheduledDate,
                'updated_at' => $deliveredAt,
            ]);

            DeliveryStop::create([
                'sequence_order' => 1,
                'type' => 'dropoff',
                'address' => $shipment->destination_address,
                'city' => $shipment->destination_city,
                'contact_name' => $shipment->consignee_name,
                'contact_phone' => $shipment->consignee_phone,
                'scheduled_time' => $departedAt->copy()->addHours(rand(1, 4)),
                'arrived_at' => $deliveredAt,
                'departed_at' => $deliveredAt,
                'status' => 'completed',
                'notes' => 'Delivered on time',
                'delivery_id' => $delivery->id,
            ]);
        }

        foreach ($inTransitShipments as $i => $shipment) {
            $driver = $drivers[($i + 3) % $drivers->count()];
            $vehicle = $vehicles[($i + 2) % $vehicles->count()];
            $route = $routes[($i + 1) % $routes->count()];

            $scheduledDate = $shipment->shipment_date->copy()->addDay();
            $departedAt = $scheduledDate->copy()->setTime(7, rand(0, 59));

            $delivery = Delivery::create([
                'delivery_number' => 'DEL-' . strtoupper(Str::random(8)),
                'scheduled_date' => $scheduledDate,
                'departed_at' => $departedAt,
                'delivered_at' => null,
                'status' => 'in_transit',
                'delivery_notes' => 'Currently in transit',
                'shipment_id' => $shipment->id,
                'route_id' => $route->id,
                'driver_id' => $driver->id,
                'vehicle_id' => $vehicle->id,
                'assigned_by' => $this->manager->id,
                'created_at' => $scheduledDate,
                'updated_at' => $departedAt,
            ]);

            DeliveryStop::create([
                'sequence_order' => 1,
                'type' => 'pickup',
                'address' => $shipment->origin_address,
                'city' => $shipment->origin_city,
                'contact_name' => $shipment->shipper_name,
                'contact_phone' => $shipment->shipper_phone,
                'scheduled_time' => $departedAt->copy()->addMinutes(30),
                'arrived_at' => $departedAt->copy()->addMinutes(45),
                'departed_at' => $departedAt->copy()->addHour(),
                'status' => 'completed',
                'notes' => 'Picked up successfully',
                'delivery_id' => $delivery->id,
            ]);

            DeliveryStop::create([
                'sequence_order' => 2,
                'type' => 'dropoff',
                'address' => $shipment->destination_address,
                'city' => $shipment->destination_city,
                'contact_name' => $shipment->consignee_name,
                'contact_phone' => $shipment->consignee_phone,
                'scheduled_time' => $departedAt->copy()->addHours(rand(3, 6)),
                'status' => 'pending',
                'notes' => 'Awaiting delivery',
                'delivery_id' => $delivery->id,
            ]);
        }
    }

    private function seedInvoices(): void
    {
        $deliveredShipments = Shipment::where('status', 'delivered')->get();
        $customers = Customer::all()->keyBy('id');
        $branches = Branch::all();

        foreach ($deliveredShipments as $i => $shipment) {
            $subtotal = $shipment->declared_value + rand(20, 200);
            $tax = round($subtotal * 0.10, 2);
            $discount = $i % 3 === 0 ? round($subtotal * 0.05, 2) : 0;
            $total = round($subtotal + $tax - $discount, 2);
            $paidAmount = $i % 4 === 0 ? 0 : $total;
            $balance = round($total - $paidAmount, 2);
            $status = $paidAmount >= $total ? 'paid' : ($i % 3 === 0 ? 'overdue' : 'pending');

            $createdAt = $shipment->created_at->copy()->addDays(rand(1, 3));
            $dueDate = $createdAt->copy()->addDays(rand(15, 30));

            $invoice = Invoice::create([
                'invoice_number' => 'INV-' . date('Ymd', $createdAt->timestamp) . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'type' => $i % 2 === 0 ? 'standard' : 'express',
                'invoice_date' => $createdAt,
                'due_date' => $dueDate,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'total' => $total,
                'paid_amount' => $paidAmount,
                'balance' => $balance,
                'currency' => 'USD',
                'exchange_rate' => 4100,
                'status' => $status,
                'notes' => null,
                'customer_id' => $shipment->customer_id,
                'shipment_id' => $shipment->id,
                'branch_id' => $shipment->origin_branch_id,
                'created_by' => $this->admin->id,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            InvoiceItem::create([
                'description' => 'Freight charges - ' . $shipment->tracking_number,
                'type' => 'freight',
                'quantity' => 1,
                'unit_price' => round($subtotal * 0.7, 2),
                'total' => round($subtotal * 0.7, 2),
                'invoice_id' => $invoice->id,
            ]);

            InvoiceItem::create([
                'description' => 'Handling & processing fee',
                'type' => 'service',
                'quantity' => 1,
                'unit_price' => round($subtotal * 0.2, 2),
                'total' => round($subtotal * 0.2, 2),
                'invoice_id' => $invoice->id,
            ]);

            if ($shipment->is_express) {
                InvoiceItem::create([
                    'description' => 'Express delivery surcharge',
                    'type' => 'surcharge',
                    'quantity' => 1,
                    'unit_price' => round($subtotal * 0.1, 2),
                    'total' => round($subtotal * 0.1, 2),
                    'invoice_id' => $invoice->id,
                ]);
            }
        }
    }

    private function seedPayments(): void
    {
        $paidInvoices = Invoice::whereIn('status', ['paid'])->get();

        foreach ($paidInvoices as $i => $invoice) {
            $paymentDate = $invoice->invoice_date->copy()->addDays(rand(1, 14));

            Payment::create([
                'payment_number' => 'PAY-' . strtoupper(Str::random(8)),
                'payment_date' => $paymentDate,
                'amount' => $invoice->total,
                'payment_method' => ['bank_transfer', 'cash', 'check', 'credit_card'][rand(0, 3)],
                'currency' => 'USD',
                'exchange_rate' => 4100,
                'reference_number' => 'REF-' . strtoupper(Str::random(6)),
                'notes' => 'Full payment for invoice ' . $invoice->invoice_number,
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'created_by' => $this->admin->id,
                'created_at' => $paymentDate,
                'updated_at' => $paymentDate,
            ]);
        }
    }

    private function seedTransactions(): void
    {
        $branches = Branch::all();

        $incomeCategories = ['Revenue', 'Service Fee', 'Consultation', 'Commission', 'Refund'];
        $expenseCategories = ['Utilities', 'Office Supplies', 'Maintenance', 'Fuel', 'Insurance'];

        for ($i = 0; $i < 15; $i++) {
            $daysAgo = rand(1, 60);
            $createdAt = now()->subDays($daysAgo);
            $isIncome = $i < 8;
            $category = $isIncome ? $incomeCategories[array_rand($incomeCategories)] : $expenseCategories[array_rand($expenseCategories)];

            Transaction::create([
                'transaction_number' => 'TRX-' . date('Ymd', $createdAt->timestamp) . '-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'transaction_date' => $createdAt,
                'type' => $isIncome ? 'income' : 'expense',
                'category' => $category,
                'amount' => round(rand(500, 15000) / 10, 2),
                'currency' => 'USD',
                'payment_method' => ['bank_transfer', 'cash', 'credit_card'][rand(0, 2)],
                'description' => ($isIncome ? 'Income: ' : 'Expense: ') . $category . ' - ' . $createdAt->format('M'),
                'reference' => 'REF-' . strtoupper(Str::random(6)),
                'status' => 'completed',
                'branch_id' => $branches->random()->id,
                'created_by' => $this->admin->id,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }

    private function seedExpenses(): void
    {
        $branches = Branch::all();
        $vehicles = Vehicle::take(5)->get();

        $categories = ['Fuel', 'Maintenance', 'Insurance', 'Utilities', 'Rent', 'Salary', 'Office Supplies', 'Marketing', 'Travel', 'Training'];

        $expenseData = [
            ['Fuel', 'Monthly fuel for fleet vehicles', 1250.00, true, null],
            ['Maintenance', 'Vehicle servicing and repairs', 850.50, false, null],
            ['Insurance', 'Monthly insurance premium', 2100.00, true, null],
            ['Rent', 'Office space rental - HQ', 3500.00, true, null],
            ['Utilities', 'Electricity and water bills', 450.00, false, null],
            ['Salary', 'Driver salaries and benefits', 5200.00, false, null],
            ['Office Supplies', 'Stationery and office equipment', 320.00, false, null],
            ['Marketing', 'Digital advertising campaign', 1500.00, false, null],
            ['Travel', 'Staff travel reimbursement', 675.00, false, null],
            ['Training', 'Employee training program', 890.00, false, null],
        ];

        foreach ($expenseData as $i => $e) {
            $daysAgo = rand(1, 45);
            $createdAt = now()->subDays($daysAgo);

            Expense::create([
                'expense_number' => 'EXP-' . date('Ymd', $createdAt->timestamp) . '-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'expense_date' => $createdAt,
                'category' => $e[0],
                'amount' => $e[2] + round(rand(-200, 200) / 10, 2),
                'currency' => 'USD',
                'description' => $e[1],
                'vendor' => ['Caltex Cambodia', 'Electricite du Cambodge', 'Smart Insurance', 'Sokha Properties', 'Office Depot'][$i % 5],
                'is_recurring' => $e[3],
                'status' => 'approved',
                'branch_id' => $branches->random()->id,
                'vehicle_id' => $e[5] ?? null,
                'approved_by' => $this->admin->id,
                'created_by' => $this->manager->id,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        // Additional fuel expenses linked to vehicles
        foreach ($vehicles as $v) {
            for ($m = 1; $m <= 3; $m++) {
                $expDate = now()->subMonths($m);
                Expense::create([
                    'expense_number' => 'EXP-FUEL-' . strtoupper(Str::random(6)),
                    'expense_date' => $expDate,
                    'category' => 'Fuel',
                    'amount' => round(rand(150, 500) / 10, 2),
                    'currency' => 'USD',
                    'description' => 'Fuel refill for ' . $v->plate_number,
                    'vendor' => 'Caltex Cambodia',
                    'is_recurring' => false,
                    'status' => 'approved',
                    'branch_id' => $v->branch_id,
                    'vehicle_id' => $v->id,
                    'approved_by' => $this->admin->id,
                    'created_by' => $this->manager->id,
                    'created_at' => $expDate,
                    'updated_at' => $expDate,
                ]);
            }
        }
    }

    private function seedInventory(): void
    {
        $warehouses = Warehouse::all();
        $locations = WarehouseLocation::all();

        $items = [
            ['SKU-PP-001', 'Laptop Computer', 'Dell Latitude 3540', 'Electronics', 50, 10, 200, 'pcs', 2.50, 0.05, 850.00, $warehouses[0]->id, $locations[0]->id],
            ['SKU-PP-002', 'Office Chair', 'Ergonomic mesh chair', 'Furniture', 120, 20, 300, 'pcs', 8.00, 0.50, 120.00, $warehouses[0]->id, $locations[1]->id],
            ['SKU-PP-003', 'Printer Paper', 'A4 80gsm 5000 sheets', 'Office Supplies', 500, 50, 1000, 'box', 5.00, 0.02, 25.00, $warehouses[0]->id, $locations[2]->id],
            ['SKU-SR-001', 'Rice 50kg Bag', 'Premium Cambodian Jasmine Rice', 'Food', 200, 30, 500, 'bag', 50.50, 0.12, 45.00, $warehouses[1]->id, $locations[3]->id],
            ['SKU-SR-002', 'Dried Fish', 'Smoked Mekong fish', 'Food', 80, 15, 200, 'kg', 1.00, 0.01, 8.50, $warehouses[1]->id, $locations[4]->id],
            ['SKU-SHV-001', 'Steel Rods', 'Construction grade 12mm', 'Construction', 300, 50, 600, 'pcs', 15.00, 0.10, 18.00, $warehouses[2]->id, $locations[5]->id],
            ['SKU-SHV-002', 'Cement Bags', 'Portland cement 50kg', 'Construction', 400, 80, 800, 'bag', 50.50, 0.05, 6.50, $warehouses[2]->id, $locations[6]->id],
            ['SKU-SHV-003', 'Paint Buckets', 'White latex paint 20L', 'Construction', 150, 25, 300, 'bucket', 25.00, 0.08, 35.00, $warehouses[2]->id, $locations[7]->id],
            ['SKU-PP-004', 'Smartphone', 'Samsung Galaxy S24', 'Electronics', 75, 15, 150, 'pcs', 0.50, 0.01, 650.00, $warehouses[0]->id, $locations[0]->id],
            ['SKU-PP-005', 'LED Monitor', '24-inch Full HD Display', 'Electronics', 40, 8, 100, 'pcs', 4.50, 0.08, 250.00, $warehouses[0]->id, $locations[1]->id],
        ];

        foreach ($items as $item) {
            $inv = InventoryItem::create([
                'sku' => $item[0],
                'name' => $item[1],
                'description' => $item[2],
                'category' => $item[3],
                'quantity' => $item[4],
                'min_quantity' => $item[5],
                'max_quantity' => $item[6],
                'unit' => $item[7],
                'weight_per_unit' => $item[8],
                'volume_per_unit' => $item[9],
                'unit_price' => $item[10],
                'barcode' => 'BAR-' . strtoupper(Str::random(8)),
                'is_active' => true,
                'warehouse_id' => $item[11],
                'location_id' => $item[12],
                'created_by' => $this->admin->id,
            ]);

            StockMovement::create([
                'reference' => 'INITIAL-STOCK-' . $inv->id,
                'type' => 'in',
                'quantity' => $inv->quantity,
                'quantity_before' => 0,
                'quantity_after' => $inv->quantity,
                'reason' => 'Initial stock entry',
                'reference_type' => 'inventory',
                'reference_id' => $inv->id,
                'inventory_item_id' => $inv->id,
                'created_by' => $this->admin->id,
            ]);
        }
    }

    private function seedNotifications(): void
    {
        $notifications = [
            ['Shipment Delivered', 'Shipment SHP-ABCD1234 has been delivered successfully.', 'bi-check-circle', 'success', null],
            ['New Shipment Created', 'A new express shipment has been created.', 'bi-box-seam', 'info', null],
            ['Payment Received', 'Payment of $1,250.00 has been received for invoice INV-20250601-0001.', 'bi-cash', 'success', null],
            ['Low Stock Alert', 'SKU-PP-001 (Laptop Computer) is below minimum quantity.', 'bi-exclamation-triangle', 'warning', 'inventory.index'],
            ['Vehicle Maintenance Due', 'Vehicle VH-002 (PP-5678) maintenance is due soon.', 'bi-truck', 'warning', 'vehicle-maintenances.index'],
            ['Invoice Overdue', 'Invoice INV-20250601-0003 is overdue by 5 days.', 'bi-receipt', 'danger', 'invoices.index'],
            ['Delivery Completed', 'Delivery DEL-XXXXXXXX to Siem Reap has been completed.', 'bi-check2-all', 'success', null],
            ['New Customer Registered', 'Customer "Techo Medical Supply" has been added.', 'bi-person-plus', 'info', 'customers.index'],
            ['Expense Approved', 'Expense EXP-20250601-001 for Fuel has been approved.', 'bi-currency-dollar', 'success', null],
            ['Driver Check-in', 'Driver Sokha Dara has checked in for shift.', 'bi-person-check', 'info', null],
        ];

        $users = User::all();

        foreach ($notifications as $n) {
            Notification::create([
                'id' => (string) Str::uuid(),
                'type' => 'general',
                'title' => $n[0],
                'message' => $n[1],
                'icon' => $n[2],
                'link' => $n[4],
                'is_read' => (bool) rand(0, 1),
                'read_at' => rand(0, 1) ? now()->subHours(rand(1, 48)) : null,
                'notifiable_id' => $users->random()->id,
                'notifiable_type' => User::class,
            ]);
        }
    }
}
