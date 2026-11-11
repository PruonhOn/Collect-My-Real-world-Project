<?php

namespace Database\Seeders;

use App\Models\AcademicTerm;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AcademicTermSeeder extends Seeder
{
    public function run(): void
    {
        AcademicTerm::create([
            'name' => 'Fall 2025',
            'start_date' => Carbon::create(2025, 9, 1),
            'end_date' => Carbon::create(2025, 12, 15),
            'is_active' => false,
        ]);

        AcademicTerm::create([
            'name' => 'Spring 2026',
            'start_date' => Carbon::create(2026, 1, 15),
            'end_date' => Carbon::create(2026, 5, 30),
            'is_active' => false,
        ]);

        AcademicTerm::create([
            'name' => 'Fall 2026',
            'start_date' => Carbon::create(2026, 9, 1),
            'end_date' => Carbon::create(2026, 12, 15),
            'is_active' => true,
        ]);
    }
}
