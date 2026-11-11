<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            ['name' => 'Sophia Martin', 'phone' => '+1 555 010 1101', 'email' => 'sophia.martin@example.com', 'address' => '14 Oak Street, Springfield'],
            ['name' => 'James Anderson', 'phone' => '+1 555 010 1102', 'email' => 'james.anderson@example.com', 'address' => '22 Maple Avenue, Riverdale'],
            ['name' => 'Olivia Bennett', 'phone' => '+1 555 010 1103', 'email' => 'olivia.bennett@example.com', 'address' => '8 Pine Road, Lakeside'],
            ['name' => 'Noah Carter', 'phone' => '+1 555 010 1104', 'email' => 'noah.carter@example.com', 'address' => '31 Birch Lane, Hilltop'],
            ['name' => 'Ava Mitchell', 'phone' => '+1 555 010 1105', 'email' => 'ava.mitchell@example.com', 'address' => '5 Cedar Court, Meadowbrook'],
            ['name' => 'Ethan Walker', 'phone' => '+1 555 010 1106', 'email' => 'ethan.walker@example.com', 'address' => '77 Willow Way, Sunset Ridge'],
            ['name' => 'Isabella Hayes', 'phone' => '+1 555 010 1107', 'email' => 'isabella.hayes@example.com', 'address' => '12 Elm Drive, Greenfield'],
            ['name' => 'Lucas Grant', 'phone' => '+1 555 010 1108', 'email' => 'lucas.grant@example.com', 'address' => '43 Ash Street, Riverside'],
            ['name' => 'Mia Collins', 'phone' => '+1 555 010 1109', 'email' => 'mia.collins@example.com', 'address' => '9 Spruce Avenue, Fairview'],
            ['name' => 'Henry Foster', 'phone' => '+1 555 010 1110', 'email' => 'henry.foster@example.com', 'address' => '58 Poplar Road, Lakeshore'],
        ];

        foreach ($customers as $data) {
            Customer::updateOrCreate(['email' => $data['email']], [
                'name' => $data['name'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'loyalty_points' => 0,
            ]);
        }
    }
}
