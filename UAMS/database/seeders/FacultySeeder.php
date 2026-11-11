<?php

namespace Database\Seeders;

use App\Models\Faculty;
use Illuminate\Database\Seeder;

class FacultySeeder extends Seeder
{
    public function run(): void
    {
        Faculty::create([
            'name' => 'Faculty of Science',
            'description' => 'The Faculty of Science offers programs in computer science, mathematics, physics, and other natural sciences.',
        ]);

        Faculty::create([
            'name' => 'Faculty of Engineering',
            'description' => 'The Faculty of Engineering provides programs in electrical, mechanical, and civil engineering.',
        ]);

        Faculty::create([
            'name' => 'Faculty of Business',
            'description' => 'The Faculty of Business offers programs in business administration, accounting, and finance.',
        ]);
    }
}
