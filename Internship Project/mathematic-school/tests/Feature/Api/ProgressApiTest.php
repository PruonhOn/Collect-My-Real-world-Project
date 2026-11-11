<?php

namespace Tests\Feature\Api;

use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use Illuminate\Foundation\Testing\WithFaker;

class ProgressApiTest extends ApiTestCase
{
    use WithFaker;

    public function test_student_can_view_their_progress(): void
    {
        $user = $this->createUser(['role' => 'student'], 'student');
        $student = $this->createStudent($user);
        $course = $this->createPublishedCourse();
        $enrollment = $this->createEnrollment($student, $course);

        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson 1',
            'description' => 'Desc',
            'order' => 1,
            'is_free' => false,
            'is_published' => true,
        ]);

        LessonProgress::create([
            'student_id' => $student->id,
            'lesson_id' => $lesson->id,
            'enrollment_id' => $enrollment->id,
            'is_completed' => true,
            'completed_at' => now(),
            'last_activity_at' => now(),
        ]);

        $this->withHeaders($this->bearerAuth($user))
            ->getJson('/api/v1/my/progress')
            ->assertOk()
            ->assertJsonStructure(['enrollments', 'summary'])
            ->assertJsonPath('summary.total_courses', 1);
    }

    public function test_student_can_complete_lesson(): void
    {
        $user = $this->createUser(['role' => 'student'], 'student');
        $student = $this->createStudent($user);
        $course = $this->createPublishedCourse();
        $enrollment = $this->createEnrollment($student, $course);

        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson 1',
            'description' => 'Desc',
            'order' => 1,
            'is_free' => false,
            'is_published' => true,
        ]);

        $this->withHeaders($this->bearerAuth($user))
            ->postJson("/api/v1/lessons/{$lesson->id}/complete")
            ->assertOk();

        $this->assertDatabaseHas('lesson_progress', [
            'student_id' => $student->id,
            'lesson_id' => $lesson->id,
            'is_completed' => true,
        ]);

        $enrollment->refresh();
        $this->assertEquals(100, $enrollment->completion_percentage);
    }

    public function test_non_enrolled_student_cannot_complete_lesson(): void
    {
        $user = $this->createUser(['role' => 'student'], 'student');
        $this->createStudent($user);
        $course = $this->createPublishedCourse();

        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson 1',
            'description' => 'Desc',
            'order' => 1,
            'is_free' => false,
            'is_published' => true,
        ]);

        $this->withHeaders($this->bearerAuth($user))
            ->postJson("/api/v1/lessons/{$lesson->id}/complete")
            ->assertForbidden();
    }

    public function test_student_can_view_certificate_list(): void
    {
        $user = $this->createUser(['role' => 'student'], 'student');
        $student = $this->createStudent($user);
        $course = $this->createPublishedCourse();

        Certificate::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'certificate_id' => 'CERT-' . strtoupper($this->faker->unique()->bothify('######')),
            'issued_date' => now(),
        ]);

        $this->withHeaders($this->bearerAuth($user))
            ->getJson('/api/v1/my/certificates')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
