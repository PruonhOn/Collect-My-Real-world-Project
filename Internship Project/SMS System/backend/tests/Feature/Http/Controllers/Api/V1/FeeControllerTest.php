<?php

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\Models\Student;
use App\Models\StudentFee;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class FeeControllerTest extends TestCase
{
    use LazilyRefreshDatabase;
    use SeedsRolesAndPermissions;

    public function test_index_returns_all_fees_for_admin(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();

        $studentA = Student::factory()->create();
        $studentB = Student::factory()->create();
        StudentFee::factory()->for($studentA, 'student')->count(2)->create();
        StudentFee::factory()->for($studentB, 'student')->count(2)->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/fees')
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('meta.pagination.total', 4)
            ->assertJsonStructure(['data' => [['id', 'invoice_no', 'title', 'amount', 'paid_amount', 'balance', 'status', 'due_date']]]);
    }

    public function test_index_scopes_fees_to_a_parents_own_children(): void
    {
        $this->seedRolesAndPermissions();
        $parent = $this->parentUser();
        $guardian = $parent->parentProfile;

        $childA = Student::factory()->create();
        $childB = Student::factory()->create();
        $guardian->students()->attach([$childA->id, $childB->id]);

        $ownFeeA = StudentFee::factory()->for($childA, 'student')->create(['title' => 'Child A Tuition']);
        $ownFeeB = StudentFee::factory()->for($childB, 'student')->create(['title' => 'Child B Tuition']);
        $otherFee = StudentFee::factory()->create(['title' => 'Unrelated Tuition']);

        $response = $this->actingAs($parent, 'sanctum')
            ->getJson('/api/v1/fees')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $invoiceNos = array_column($response->json('data'), 'invoice_no');
        $this->assertContains($ownFeeA->invoice_no, $invoiceNos);
        $this->assertContains($ownFeeB->invoice_no, $invoiceNos);
        $this->assertNotContains($otherFee->invoice_no, $invoiceNos);
    }

    public function test_index_returns_an_empty_list_for_a_user_without_visible_students(): void
    {
        $this->seedRolesAndPermissions();
        $teacher = $this->teacherUser();
        StudentFee::factory()->count(2)->create();

        $this->actingAs($teacher, 'sanctum')
            ->getJson('/api/v1/fees')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.pagination.total', 0);
    }

    public function test_summary_returns_status_counts_and_overdue_balance(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();

        StudentFee::factory()->create([
            'amount' => 500.25,
            'due_date' => now()->subDays(10)->toDateString(),
        ]);
        StudentFee::factory()->partial()->create([
            'amount' => 400.50,
            'due_date' => now()->subDays(3)->toDateString(),
        ]);
        StudentFee::factory()->paid()->create(['amount' => 300.00]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/fees/summary')
            ->assertOk()
            ->assertJsonPath('data.by_status.unpaid.count', 1)
            ->assertJsonPath('data.by_status.partial.count', 1)
            ->assertJsonPath('data.by_status.paid.count', 1);

        $this->assertNull($response->json('data.by_status.waived'));
        $this->assertSame(700.5, $response->json('data.overdue_balance'));
    }

    public function test_statement_returns_summary_and_fees_for_linked_child(): void
    {
        $this->seedRolesAndPermissions();
        $parent = $this->parentUser();
        $child = Student::factory()->create();
        $parent->parentProfile->students()->attach($child->id);

        $fee = StudentFee::factory()->for($child, 'student')->partial()->create([
            'amount' => 2500.50,
            'title' => 'Semester Tuition',
        ]);

        $response = $this->actingAs($parent, 'sanctum')
            ->getJson("/api/v1/fees/statement/{$child->id}")
            ->assertOk()
            ->assertJsonPath('data.student.id', $child->id)
            ->assertJsonPath('data.student.student_code', $child->student_code)
            ->assertJsonPath('data.student_name', $child->user->name)
            ->assertJsonPath('data.summary.total_billed', 2500.5)
            ->assertJsonPath('data.summary.total_paid', 1250.25)
            ->assertJsonPath('data.summary.balance', 1250.25)
            ->assertJsonPath('data.fees.0.invoice_no', $fee->invoice_no)
            ->assertJsonPath('data.fees.0.status', 'partial');

        $this->assertSame([], $response->json('data.enrolled_courses'));
    }

    public function test_statement_returns_403_for_a_student_not_linked_to_the_parent(): void
    {
        $this->seedRolesAndPermissions();
        $parent = $this->parentUser();
        $unrelated = Student::factory()->create();

        $this->actingAs($parent, 'sanctum')
            ->getJson("/api/v1/fees/statement/{$unrelated->id}")
            ->assertForbidden();
    }

    public function test_store_creates_an_invoice_and_returns_201(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();
        $student = Student::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/fees', [
                'student_id' => $student->id,
                'title' => 'Tuition Fee',
                'amount' => 750.00,
                'due_date' => now()->addDays(30)->toDateString(),
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'unpaid')
            ->assertJsonPath('data.title', 'Tuition Fee')
            ->assertJsonPath('data.student.id', $student->id);

        $this->assertMatchesRegularExpression('/^INV-\d{4}-\d{6}$/', $response->json('data.invoice_no'));
        $this->assertDatabaseHas('student_fees', [
            'student_id' => $student->id,
            'title' => 'Tuition Fee',
            'status' => 'unpaid',
        ]);
    }

    public function test_store_returns_403_without_fees_manage_permission(): void
    {
        $this->seedRolesAndPermissions();
        $teacher = $this->teacherUser();
        $student = Student::factory()->create();

        $this->actingAs($teacher, 'sanctum')
            ->postJson('/api/v1/admin/fees', [
                'student_id' => $student->id,
                'title' => 'Tuition Fee',
                'amount' => 750.00,
            ])
            ->assertForbidden();
    }

    public function test_update_returns_422_when_amount_is_below_paid_amount(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();
        $fee = StudentFee::factory()->partial()->create([
            'amount' => 1000.00,
            'title' => 'Semester Fee',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/fees/{$fee->id}", ['amount' => 250])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);
    }
}