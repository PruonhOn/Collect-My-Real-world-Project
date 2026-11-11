<?php

namespace Tests\Feature\Api;

use App\Models\Course;
use Illuminate\Foundation\Testing\WithFaker;

class EnrollmentApiTest extends ApiTestCase
{
    use WithFaker;

    public function test_student_can_enroll_in_published_course(): void
    {
        $user = $this->createUser(['role' => 'student'], 'student');
        $student = $this->createStudent($user);
        $course = $this->createPublishedCourse();

        $this->withHeaders($this->bearerAuth($user))
            ->postJson('/api/v1/enrollments', ['course_id' => $course->id])
            ->assertCreated()
            ->assertJsonPath('enrollment.status', 'active');

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
    }

    public function test_student_cannot_enroll_twice_in_same_course(): void
    {
        $user = $this->createUser(['role' => 'student'], 'student');
        $student = $this->createStudent($user);
        $course = $this->createPublishedCourse();

        $this->createEnrollment($student, $course);

        $this->withHeaders($this->bearerAuth($user))
            ->postJson('/api/v1/enrollments', ['course_id' => $course->id])
            ->assertStatus(422);
    }

    public function test_student_cannot_enroll_in_draft_course(): void
    {
        $user = $this->createUser(['role' => 'student'], 'student');
        $this->createStudent($user);
        $course = $this->createPublishedCourse();
        $course->update(['status' => 'draft']);

        $this->withHeaders($this->bearerAuth($user))
            ->postJson('/api/v1/enrollments', ['course_id' => $course->id])
            ->assertStatus(422);
    }

    public function test_student_can_list_their_enrollments(): void
    {
        $user = $this->createUser(['role' => 'student'], 'student');
        $student = $this->createStudent($user);
        $course = $this->createPublishedCourse();
        $this->createEnrollment($student, $course);

        $this->withHeaders($this->bearerAuth($user))
            ->getJson('/api/v1/my/courses')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
