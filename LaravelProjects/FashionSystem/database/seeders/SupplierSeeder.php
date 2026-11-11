<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Supplier::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $suppliers = [
            [
                'name' => 'Juan dela Cruz',
                'email' => 'juan.delacruz@example.com',
                'phone' => '09171234567',
                'address' => '123 Rizal Avenue, Manila, Metro Manila',
                'company' => 'Manila Fashion Distributors',
            ],
            [
                'name' => 'Maria Santos',
                'email' => 'maria.santos@example.com',
                'phone' => '09182345678',
                'address' => '456 Mabini Street, Makati, Metro Manila',
                'company' => 'Makati Apparel Supply',
            ],
            [
                'name' => 'Jose Rizal Jr.',
                'email' => 'jose.rizal@example.com',
                'phone' => '09193456789',
                'address' => '789 Bonifacio Highway, Quezon City, Metro Manila',
                'company' => 'QC Textile Traders',
            ],
            [
                'name' => 'Ana Gonzales',
                'email' => 'ana.gonzales@example.com',
                'phone' => '09204567890',
                'address' => '321 Katipunan Road, Mandaluyong, Metro Manila',
                'company' => 'Mandaluyong Garment House',
            ],
            [
                'name' => 'Pedro Reyes',
                'email' => 'pedro.reyes@example.com',
                'phone' => '09215678901',
                'address' => '654 Taft Avenue, Pasay, Metro Manila',
                'company' => 'Pasay Fashion Wholesale',
            ],
            [
                'name' => 'Luisa Mercado',
                'email' => 'luisa.mercado@example.com',
                'phone' => '09226789012',
                'address' => '987 Quezon Boulevard, Caloocan, Metro Manila',
                'company' => 'Caloocan Clothing Suppliers',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}
