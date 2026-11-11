<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['name' => 'Piece', 'short_name' => 'pc', 'operator' => 'multiply', 'operation_value' => 1],
            ['name' => 'Kilogram', 'short_name' => 'kg', 'operator' => 'multiply', 'operation_value' => 1],
            ['name' => 'Gram', 'short_name' => 'g', 'operator' => 'divide', 'operation_value' => 1000],
            ['name' => 'Liter', 'short_name' => 'L', 'operator' => 'multiply', 'operation_value' => 1],
            ['name' => 'Milliliter', 'short_name' => 'ml', 'operator' => 'divide', 'operation_value' => 1000],
            ['name' => 'Box', 'short_name' => 'box', 'operator' => 'multiply', 'operation_value' => 1],
            ['name' => 'Pack', 'short_name' => 'pack', 'operator' => 'multiply', 'operation_value' => 1],
            ['name' => 'Carton', 'short_name' => 'ctn', 'operator' => 'multiply', 'operation_value' => 1],
        ];

        foreach ($units as $unit) {
            Unit::create($unit);
        }
    }
}
