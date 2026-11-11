<?php

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentFee;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use LazilyRefreshDatabase;
    use SeedsRolesAndPermissions;

    public function test_index_returns_all_payments_for_admin(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();
        Payment::factory()->count(3)->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/payments')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.pagination.total', 3)
            ->assertJsonStructure(['data' => [['id', 'transaction_id', 'method', 'gateway', 'status', 'amount']]]);
    }

    public function test_index_scopes_payments_to_a_parents_own_children(): void
    {
        $this->seedRolesAndPermissions();
        $parent = $this->parentUser();
        $guardian = $parent->parentProfile;

        $childA = Student::factory()->create();
        $childB = Student::factory()->create();
        $guardian->students()->attach([$childA->id, $childB->id]);

        $feeA = StudentFee::factory()->for($childA, 'student')->create();
        $feeB = StudentFee::factory()->for($childB, 'student')->create();
        $feeC = StudentFee::factory()->create();

        Payment::factory()->for($feeA, 'studentFee')->create(['student_id' => $childA->id]);
        Payment::factory()->for($feeB, 'studentFee')->create(['student_id' => $childB->id]);
        Payment::factory()->for($feeC, 'studentFee')->create(['student_id' => $feeC->student_id]);

        $response = $this->actingAs($parent, 'sanctum')
            ->getJson('/api/v1/payments')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $studentIds = collect($response->json('data'))->pluck('student.id')->all();
        $this->assertContains($childA->id, $studentIds);
        $this->assertContains($childB->id, $studentIds);
        $this->assertNotContains($feeC->student_id, $studentIds);
    }

    public function test_charge_returns_403_for_a_fee_of_a_student_not_linked_to_the_parent(): void
    {
        $this->seedRolesAndPermissions();
        $parent = $this->parentUser();
        $unrelatedFee = StudentFee::factory()->create();

        $this->actingAs($parent, 'sanctum')
            ->postJson("/api/v1/fees/{$unrelatedFee->id}/pay", ['amount' => $unrelatedFee->amount])
            ->assertForbidden()
            ->assertJsonPath('message', 'You are not allowed to pay this fee.');
    }

    public function test_charge_creates_a_confirmed_payment_and_marks_the_fee_paid(): void
    {
        $this->seedRolesAndPermissions();
        $parent = $this->parentUser();
        $child = Student::factory()->create();
        $parent->parentProfile->students()->attach($child->id);
        $fee = StudentFee::factory()->for($child, 'student')->create(['amount' => 500.00, 'title' => 'Tuition']);

        $pdf = Mockery::mock();
        $pdf->shouldReceive('output')->andReturn('%PDF-1.4 fake');
        Pdf::shouldReceive('loadHTML')->andReturn($pdf);

        $response = $this->actingAs($parent, 'sanctum')
            ->postJson("/api/v1/fees/{$fee->id}/pay", ['amount' => 500.00, 'method' => 'manual'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.gateway', 'manual')
            ->assertJsonPath('data.amount', '500.00');

        $paymentId = $response->json('data.id');
        $this->assertGreaterThan(0, $paymentId);

        $this->assertDatabaseHas('payments', [
            'id' => $paymentId,
            'student_fee_id' => $fee->id,
            'status' => 'confirmed',
        ]);
        $this->assertDatabaseHas('payment_receipts', ['payment_id' => $paymentId]);

        $fee->refresh();
        $this->assertSame('paid', $fee->status->value);
        $this->assertSame('500.00', $fee->paid_amount);
        $this->assertSame(0.0, $fee->balance());
    }

    public function test_charge_returns_500_when_amount_exceeds_the_outstanding_balance(): void
    {
        $this->seedRolesAndPermissions();
        $parent = $this->parentUser();
        $child = Student::factory()->create();
        $parent->parentProfile->students()->attach($child->id);
        $fee = StudentFee::factory()->for($child, 'student')->create(['amount' => 500.00]);

        $this->actingAs($parent, 'sanctum')
            ->postJson("/api/v1/fees/{$fee->id}/pay", ['amount' => 900.00])
            ->assertStatus(500);
    }

    public function test_confirm_marks_a_pending_payment_as_confirmed(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();
        $fee = StudentFee::factory()->create(['amount' => 500.00]);
        $payment = Payment::factory()->for($fee, 'studentFee')->pending()->create([
            'student_id' => $fee->student_id,
            'amount' => 500.00,
        ]);

        $pdf = Mockery::mock();
        $pdf->shouldReceive('output')->andReturn('%PDF-1.4 fake');
        Pdf::shouldReceive('loadHTML')->andReturn($pdf);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/payments/{$payment->id}/confirm")
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed');

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'confirmed']);
        $this->assertDatabaseHas('payment_receipts', ['payment_id' => $payment->id]);
        $this->assertDatabaseHas('student_fees', ['id' => $fee->id, 'status' => 'paid']);
    }

    public function test_confirm_returns_403_without_the_payments_process_permission(): void
    {
        $this->seedRolesAndPermissions();
        $student = $this->studentUser();
        $fee = StudentFee::factory()->create(['amount' => 500.00]);
        $payment = Payment::factory()->for($fee, 'studentFee')->pending()->create([
            'student_id' => $fee->student_id,
            'amount' => 500.00,
        ]);

        $this->actingAs($student, 'sanctum')
            ->postJson("/api/v1/admin/payments/{$payment->id}/confirm")
            ->assertForbidden();
    }

    public function test_refund_marks_a_payment_as_refunded_and_recomputes_the_fee_status(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();
        $fee = StudentFee::factory()->create(['amount' => 500.00]);
        $payment = Payment::factory()->for($fee, 'studentFee')->create([
            'student_id' => $fee->student_id,
            'amount' => 500.00,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/payments/{$payment->id}/refund")
            ->assertOk()
            ->assertJsonPath('data.status', 'refunded');

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'refunded']);
        $this->assertDatabaseHas('student_fees', ['id' => $fee->id, 'status' => 'unpaid']);

        $fee->refresh();
        $this->assertSame('0.00', $fee->paid_amount);
        $this->assertSame(500.0, $fee->balance());
    }
}