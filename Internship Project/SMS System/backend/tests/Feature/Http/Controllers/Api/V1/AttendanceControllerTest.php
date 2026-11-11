<?php

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\Score;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class AssessmentControllerTest extends TestCase
{
    use LazilyRefreshDatabase;
    use SeedsRolesAndPermissions;

    public function test_index_lists_incomplete_assessments_for_an_enrolled_course(...): void
    {
        $this->seedRolesAndPermissions();
        $teacher = $this->teacherUser();
        $course = Course::factory()->create(['teacher_id' => $teacher->teacher->id, 'academic_year_id' => AcademicYear::factory()->create()->id]);
        $assessment = Assessment::factory()->create(['course_id' => $course->id, 'weight' => 40, 'max_score' => 100, 'type' => \App\Enums\AssessmentType::Quiz->value]);

        $this->actingAs($teacher, 'sanctum')
            ->getJson("/api/v1/my/courses/{$course->id}/assessments")
            ->assertOk()
            ->assertJsonPath('data.0.id', $assessment->id)
            ->assertJsonStructure(['meta' => ['pagination']]);
    }
}
</content></content>
