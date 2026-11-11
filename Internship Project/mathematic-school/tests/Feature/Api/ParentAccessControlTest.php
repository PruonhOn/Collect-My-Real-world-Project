<?php

namespace Tests\Feature\Api;

use App\Models\Enrollment;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\Feature\Api\ApiTestCase;

/**
 * Critical authorization test: a parent must only access their OWN linked
 * children's data. Parent A must NEVER be able to view Student B's progress,
 * results, certificates, or payments.
 */
class ParentAccessControlTest extends ApiTestCase
{
    use WithFaker;

    public function test_parent_can_view_their_own_children(): void
    {
        $parentUser = $this->createUser(['role' => 'parent'], 'parent');
        $parent = $this->createParent($parentUser);

        $child = $this->createStudent();
        $course = $this->createPublishedCourse();
        $this->createEnrollment($child, $course);

        $parent->children()->attach($child->id, ['relation' => 'parent']);

        $response = $this->withHeaders($this->bearerAuth($parentUser))
            ->getJson('/api/v1/parents/children');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['student_id' => $child->student_id]);
    }

    public function test_parent_cannot_view_another_parents_child(): void
    {
        $parentUserA = $this->createUser(['role' => 'parent'], 'parent');
        $parentA = $this->createParent($parentUserA);

        // Student B belongs to Parent A
        $studentB = $this->createStudent();
        $parentA->children()->attach($studentB->id, ['relation' => 'parent']);

        // Parent X has no link to Student B
        $parentUserX = $this->createUser(['role' => 'parent'], 'parent');
        $this->createParent($parentUserX);

        $this->withHeaders($this->bearerAuth($parentUserX))
            ->getJson('/api/v1/parents/children')
            ->assertOk()
            ->assertJsonCount(0);
    }

    public function test_unlinked_parent_cannot_access_child_progress(): void
    {
        $studentB = $this->createStudent();
        $course = $this->createPublishedCourse();
        $enrollment = $this->createEnrollment($studentB, $course);

        // Parent X is NOT linked to Student B
        $parentUserX = $this->createUser(['role' => 'parent'], 'parent');
        $this->createParent($parentUserX);

        // Without the authorization check this would leak Student B's data
        $this->withHeaders($this->bearerAuth($parentUserX))
            ->getJson("/api/v1/parents/children/{$studentB->id}/progress")
            ->assertForbidden();
    }

    public function test_linked_parent_can_access_child_progress(): void
    {
        $parentUser = $this->createUser(['role' => 'parent'], 'parent');
        $parent = $this->createParent($parentUser);

        $child = $this->createStudent();
        $parent->children()->attach($child->id, ['relation' => 'parent']);

        $course = $this->createPublishedCourse();
        $this->createEnrollment($child, $course);

        $this->withHeaders($this->bearerAuth($parentUser))
            ->getJson("/api/v1/parents/children/{$child->id}/progress")
            ->assertOk()
            ->assertJsonPath('student.id', $child->id);
    }

    public function test_unlinked_parent_cannot_access_child_results(): void
    {
        $studentB = $this->createStudent();

        $parentUserX = $this->createUser(['role' => 'parent'], 'parent');
        $this->createParent($parentUserX);

        $this->withHeaders($this->bearerAuth($parentUserX))
            ->getJson("/api/v1/parents/children/{$studentB->id}/results")
            ->assertForbidden();
    }

    public function test_unlinked_parent_cannot_access_child_certificates(): void
    {
        $studentB = $this->createStudent();

        $parentUserX = $this->createUser(['role' => 'parent'], 'parent');
        $this->createParent($parentUserX);

        $this->withHeaders($this->bearerAuth($parentUserX))
            ->getJson("/api/v1/parents/children/{$studentB->id}/certificates")
            ->assertForbidden();
    }

    public function test_unlinked_parent_cannot_access_child_payments(): void
    {
        $studentB = $this->createStudent();

        $parentUserX = $this->createUser(['role' => 'parent'], 'parent');
        $this->createParent($parentUserX);

        $this->withHeaders($this->bearerAuth($parentUserX))
            ->getJson("/api/v1/parents/children/{$studentB->id}/payments")
            ->assertForbidden();
    }

    public function test_student_cannot_access_parent_endpoints(): void
    {
        $studentUser = $this->createUser(['role' => 'student'], 'student');
        $this->createStudent($studentUser);

        $otherStudent = $this->createStudent();

        // A student has no parent profile, so the parent endpoints must
        // reject them (they must NOT see any children data).
        $this->withHeaders($this->bearerAuth($studentUser))
            ->getJson('/api/v1/parents/children')
            ->assertNotFound();

        $this->withHeaders($this->bearerAuth($studentUser))
            ->getJson("/api/v1/parents/children/{$otherStudent->id}/progress")
            ->assertForbidden();
    }
}
