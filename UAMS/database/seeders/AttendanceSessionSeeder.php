<?php

namespace Database\Seeders;

use App\Models\AttendanceSession;
use App\Models\ClassSection;
use App\Models\AcademicTerm;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AttendanceSessionSeeder extends Seeder
{
    public function run(): void
    {
        $term = AcademicTerm::where('name', 'Fall 2026')->first();
        $sections = ClassSection::where('term_id', $term->id)->get();

        // "Today" is Wed Oct 14, 2026 (mid-term reference point)
        $today = Carbon::create(2026, 10, 14);
        $dayMap = [
            'Mon' => Carbon::MONDAY,
            'Tue' => Carbon::TUESDAY,
            'Wed' => Carbon::WEDNESDAY,
            'Thu' => Carbon::THURSDAY,
            'Fri' => Carbon::FRIDAY,
        ];

        foreach ($sections as $section) {
            $schedule = $section->schedule_json;
            if (! $schedule) {
                continue;
            }

            $daysStr = $schedule['days'] ?? '';
            $startTime = $schedule['start_time'] ?? '09:00';
            $endTime = $schedule['end_time'] ?? '10:30';

            // Parse days from the schedule (e.g., "Mon/Wed" -> [Mon, Wed])
            $dayKeys = array_map('trim', explode('/', $daysStr));

            // Generate session dates for 4 weeks before and after "today"
            $sessionDates = [];
            for ($weekOffset = -4; $weekOffset <= 4; $weekOffset++) {
                foreach ($dayKeys as $dayKey) {
                    if (! isset($dayMap[$dayKey])) {
                        continue;
                    }
                    $date = Carbon::create($today->year, $today->month, $today->day)
                        ->startOfWeek()
                        ->addWeeks($weekOffset)
                        ->day($dayMap[$dayKey]);

                    // Only include dates within the term
                    if ($date->gte($term->start_date) && $date->lte($term->end_date)) {
                        $sessionDates[] = $date->copy();
                    }
                }
            }

            // Sort dates
            usort($sessionDates, fn ($a, $b) => $a <=> $b);

            // Limit to 8 sessions max per section
            $sessionDates = array_slice($sessionDates, 0, 8);

            foreach ($sessionDates as $date) {
                $status = 'closed';
                $qrToken = null;

                if ($date->isAfter($today)) {
                    $status = 'scheduled';
                } elseif ($date->isSameDay($today)) {
                    $status = 'active';
                    $qrToken = Str::random(32);
                }

                AttendanceSession::create([
                    'class_section_id' => $section->id,
                    'session_date' => $date->format('Y-m-d'),
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'qr_token' => $qrToken,
                    'grace_period_minutes' => 15,
                    'status' => $status,
                ]);
            }
        }
    }
}
