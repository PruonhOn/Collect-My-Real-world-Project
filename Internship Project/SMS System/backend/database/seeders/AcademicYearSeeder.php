<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    public function run(): void
    {
        $currentYear = now()->year;

        AcademicYear::updateOrCreate(
            ['name' => sprintf('%d-%d', $currentYear, $currentYear + 1)],
            [
                'start_date' => now()->startOfYear()->toDateString(),
                'end_date' => now()->addYear()->startOfYear()->subDay()->toDateString(),
                'is_current' => true,
            ],
        );
    }
}
