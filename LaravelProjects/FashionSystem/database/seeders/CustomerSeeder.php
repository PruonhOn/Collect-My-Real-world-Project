<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Customer::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $customers = [
            ['name' => 'Angela Reyes', 'email' => 'angela.reyes@email.com', 'phone' => '09171234001', 'address' => '12 San Juan St., Quezon City'],
            ['name' => 'Benigno Santos', 'email' => 'benigno.santos@email.com', 'phone' => '09171234002', 'address' => '34 Mabini St., Manila'],
            ['name' => 'Catherine Lopez', 'email' => 'catherine.lopez@email.com', 'phone' => '09171234003', 'address' => '56 Rizal Ave., Makati'],
            ['name' => 'Dante Villanueva', 'email' => 'dante.villanueva@email.com', 'phone' => '09171234004', 'address' => '78 Katipunan Rd., Pasig'],
            ['name' => 'Elena Gonzaga', 'email' => 'elena.gonzaga@email.com', 'phone' => '09171234005', 'address' => '90 Banawe St., Quezon City'],
            ['name' => 'Francisco Mateo', 'email' => 'francisco.mateo@email.com', 'phone' => '09171234006', 'address' => '11 Taft Ave., Pasay'],
            ['name' => 'Gloria Macapagal', 'email' => 'gloria.macapagal@email.com', 'phone' => '09171234007', 'address' => '22 EDSA, Mandaluyong'],
            ['name' => 'Henry Tan', 'email' => 'henry.tan@email.com', 'phone' => '09171234008', 'address' => '33 P. Burgos St., Caloocan'],
            ['name' => 'Isabela Dizon', 'email' => 'isabela.dizon@email.com', 'phone' => '09171234009', 'address' => '44 N. Domingo St., San Juan'],
            ['name' => 'Josefina Ramos', 'email' => 'josefina.ramos@email.com', 'phone' => '09171234010', 'address' => '55 Shaw Blvd., Pasig'],
            ['name' => 'Karlos Mercado', 'email' => 'karlos.mercado@email.com', 'phone' => '09171234011', 'address' => '66 Timog Ave., Quezon City'],
            ['name' => 'Lorna Hernandez', 'email' => 'lorna.hernandez@email.com', 'phone' => '09171234012', 'address' => '77 Jupiter St., Makati'],
            ['name' => 'Manuel Roxas', 'email' => 'manuel.roxas@email.com', 'phone' => '09171234013', 'address' => '88 Mindanao Ave., Quezon City'],
            ['name' => 'Nena Aquino', 'email' => 'nena.aquino@email.com', 'phone' => '09171234014', 'address' => '99 Sct. Borromeo St., Quezon City'],
            ['name' => 'Oscar Fernandez', 'email' => 'oscar.fernandez@email.com', 'phone' => '09171234015', 'address' => '100 Broadway, Taguig'],
        ];

        foreach ($customers as $customer) {
            Customer::create($customer);
        }
    }
}
