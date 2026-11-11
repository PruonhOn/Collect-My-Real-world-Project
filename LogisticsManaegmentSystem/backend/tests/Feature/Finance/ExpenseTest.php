<?php

namespace Tests\Feature\Finance;

use App\Enums\ExpenseCategory;
use App\Models\Booking;
use App\Models\Carrier;
use App\Models\Expense;
use App\Models\Shipment;
use App\Support\Money;

/**
 * The expense module: what the business paid out.
 *
 * The plainest of the four, and deliberately so - nothing here writes to another
 * record, so none of it needs a transaction. What is worth pinning down is the one
 * real decision in the model: duty and tax is money passing *through* the business
 * rather than money spent, and it therefore answers two different questions with
 * two different figures.
 *
 * - all three references are optional, because running costs belong to none of them
 * - `margin_amount` excludes duty; `spend_amount` includes it
 * - `is_billable` is forced false for duty, which is never recharged
 * - a carrier's invoice is the common case and the reason the carrier is joinable
 */
class ExpenseTest extends FinanceTestCase
{
    public function test_it_requires_a_session(): void
    {
        $this->getJson('/api/v1/expenses')->assertUnauthorized();
    }

    public function test_a_viewer_can_read_but_not_write(): void
    {
        $expense = Expense::factory()->create();

        $this->actingAsRole('viewer')->getJson('/api/v1/expenses')->assertOk();

        $this->actingAsRole('viewer')
            ->postJson('/api/v1/expenses', [
                'reference' => 'EXP-2026-000001',
                'category' => 'freight',
                'amount' => '10.0000',
            ])
            ->assertForbidden();
    }

    public function test_it_creates_an_expense_and_normalises_the_reference(): void
    {
        $shipment = Shipment::factory()->create();

        $this->actingAsAdmin()
            ->postJson('/api/v1/expenses', [
                'shipment_id' => $shipment->id,
                'reference' => 'exp-2026-000451',
                'category' => 'demurrage',
                'description' => 'Terminal detention, days 4-6',
                'amount' => '1250.50',
                'incurred_on' => '2026-09-20',
                'is_billable' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.reference', 'EXP-2026-000451')
            ->assertJsonPath('data.category', 'demurrage')
            ->assertJsonPath('data.incurred_on', '2026-09-20')
            ->assertJsonPath('data.is_billable', true)
            ->assertJsonPath('data.counts_against_margin', true);
    }

    /**
     * All three references are optional, and this is why: rent, a licence and a
     * salary belong to no consignment, and requiring one would make it impossible
     * to record the cost of running the business at all.
     */
    public function test_an_expense_can_have_no_references_at_all(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/expenses', [
                'reference' => 'EXP-2026-000500',
                'category' => 'overhead',
                'description' => 'Office rent, quarterly',
                'amount' => '4500.0000',
            ])
            ->assertCreated()
            ->assertJsonPath('data.shipment', null)
            ->assertJsonPath('data.booking', null)
            ->assertJsonPath('data.carrier', null)
            ->assertJsonPath('data.total', null);
    }

    /**
     * The two figures, and the reason they are not the same number.
     *
     * Duty and tax is collected from the customer and remitted to the authority, so
     * it is money passing through rather than money spent. It belongs in a
     * total-spend figure and not in a margin one - and a margin report wrong by
     * exactly the amount of the tax is the kind of wrong a manager notices once and
     * then stops trusting the whole thing over.
     */
    public function test_duty_counts_as_spend_but_not_as_margin(): void
    {
        $duty = Expense::factory()->dutyAndTax()->create(['amount' => '500.0000']);

        $this->assertFalse($duty->countsAgainstMargin());
        $this->assertSame('500.0000', $duty->spendAmount());
        $this->assertSame('0.0000', $duty->marginAmount());
    }

    public function test_a_real_cost_counts_as_both(): void
    {
        $freight = Expense::factory()->ofAmount('2000.0000')->create();

        $this->assertTrue($freight->countsAgainstMargin());
        $this->assertSame('2000.0000', $freight->marginAmount());
        $this->assertSame('2000.0000', $freight->spendAmount());
    }

    /**
     * `is_billable` is forced false for held money. Duty is owed to an authority,
     * never recharged to a customer, so a row marked billable would be a statement
     * of intent that the accounting rule overrides.
     *
     * The column is set to true deliberately rather than left to the factory's
     * state, because the point is that the *model* is what refuses it - a
     * mislabelled row can arrive from an import just as easily as from a screen.
     */
    public function test_duty_cannot_be_marked_billable(): void
    {
        $duty = Expense::factory()->create([
            'category' => ExpenseCategory::Duty,
            'is_billable' => true,
        ]);

        $this->assertTrue($duty->getRawOriginal('is_billable'));
        $this->assertFalse($duty->isBillable());

        $this->actingAsAdmin()
            ->getJson('/api/v1/expenses/'.$duty->id)
            ->assertOk()
            ->assertJsonPath('data.is_billable', false);
    }

    public function test_a_regular_cost_keeps_its_billable_flag(): void
    {
        $this->assertTrue(Expense::factory()->billable()->create()->isBillable());
        $this->assertFalse(Expense::factory()->create(['is_billable' => false])->isBillable());
    }

    public function test_the_margin_scope_excludes_duty(): void
    {
        $shipment = Shipment::factory()->create();

        Expense::factory()->count(3)->create(['shipment_id' => $shipment->id]);
        Expense::factory()->count(2)->dutyAndTax()->create(['shipment_id' => $shipment->id]);

        $this->assertSame(3, Expense::query()->marginBearing()->count());
        $this->assertSame(5, Expense::query()->count());
    }

    /**
     * The query the table exists to answer. Both totals are read through `Money` so
     * the figures are exact, and the two are deliberately different numbers.
     */
    public function test_a_consignments_cost_answers_both_questions_differently(): void
    {
        $shipment = Shipment::factory()->create();

        Expense::factory()->ofAmount('1200.0000')->create(['shipment_id' => $shipment->id]);
        Expense::factory()->ofAmount('300.0000')->create(['shipment_id' => $shipment->id]);
        Expense::factory()->dutyAndTax()->ofAmount('180.0000')->create(['shipment_id' => $shipment->id]);

        $expenses = $shipment->expenses()->get();

        $this->assertSame(
            '1500.0000',
            Money::sum($expenses->map(fn (Expense $e): string => $e->marginAmount())->all()),
        );

        $this->assertSame(
            '1680.0000',
            Money::sum($expenses->map(fn (Expense $e): string => $e->spendAmount())->all()),
        );
    }

    public function test_it_rejects_a_negative_amount(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/expenses', [
                'reference' => 'EXP-2026-000600',
                'category' => 'freight',
                'amount' => '-100.0000',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_it_needs_a_category(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/expenses', [
                'reference' => 'EXP-2026-000700',
                'amount' => '100.0000',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['category']);
    }

    public function test_the_vendor_reference_is_not_unique(): void
    {
        $supplier = 'STMT-2026-09';

        // A monthly carrier statement carries the same number on a dozen lines, so
        // making it unique would mean inventing numbers to satisfy the index.
        $first = Expense::factory()->create(['vendor_reference' => $supplier]);
        $second = Expense::factory()->create(['vendor_reference' => $supplier]);

        $this->assertSame($supplier, $first->vendor_reference);
        $this->assertSame($supplier, $second->vendor_reference);
    }

    public function test_the_date_defaults_to_today(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/expenses', [
                'reference' => 'EXP-2026-000800',
                'category' => 'brokerage',
                'amount' => '100.0000',
            ])
            ->assertCreated()
            ->assertJsonPath('data.incurred_on', now()->toDateString());
    }

    /**
     * A booking-level cost has no consignment behind it, and a window on the day the
     * cost was *incurred* is what a month-end report is cut by.
     */
    public function test_an_incurred_date_window_includes_the_closing_day(): void
    {
        Expense::factory()->incurredOn('2026-09-30')->create();
        Expense::factory()->incurredOn('2026-10-01')->create();

        $this->actingAsAdmin()
            ->getJson('/api/v1/expenses?incurred_to=2026-09-30')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.incurred_on', '2026-09-30');
    }

    public function test_it_can_be_filtered_to_unattributed_running_costs(): void
    {
        Expense::factory()->count(2)->overhead()->create();
        Expense::factory()->count(3)->create();

        $this->actingAsAdmin()
            ->getJson('/api/v1/expenses?unattributed=1')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_it_can_be_filtered_by_category_and_carrier(): void
    {
        $carrier = Carrier::factory()->create();

        Expense::factory()->ofAmount('10.0000')->create([
            'category' => ExpenseCategory::Demurrage,
            'carrier_id' => $carrier->id,
        ]);

        Expense::factory()->ofAmount('20.0000')->create(['category' => ExpenseCategory::Fuel]);

        $this->actingAsAdmin()
            ->getJson('/api/v1/expenses?category=demurrage')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAsAdmin()
            ->getJson('/api/v1/expenses?carrier_id='.$carrier->id)
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_it_can_be_searched_by_carrier_name(): void
    {
        $carrier = Carrier::factory()->create(['name' => 'Meridian Ocean Carriers']);

        $wanted = Expense::factory()->create(['carrier_id' => $carrier->id]);

        Shipment::factory()->count(2)->create();

        $this->actingAsAdmin()
            ->getJson('/api/v1/expenses?q=Meridian')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $wanted->id);
    }

    /**
     * Both figures travel to the client, so a chart does not re-derive the duty rule
     * itself and get it wrong in a different place from the report.
     */
    public function test_the_resource_sends_both_figures(): void
    {
        $expense = Expense::factory()->dutyAndTax()->ofAmount('75.5000')->create();

        $response = $this->actingAsAdmin()->getJson('/api/v1/expenses/'.$expense->id)->assertOk();

        $this->assertSame('75.5000', $response->json('data.spend_amount'));
        $this->assertSame('0.0000', $response->json('data.margin_amount'));
        $this->assertFalse($response->json('data.counts_against_margin'));
        $this->assertFalse($response->json('data.is_billable'));
    }

    /**
     * The amount is a decimal string, and so are both derived figures - a float
     * would lose the trailing zeros the column means and a total re-added in the
     * browser would not match the server's.
     */
    public function test_the_amounts_are_sent_as_decimal_strings(): void
    {
        $expense = Expense::factory()->ofAmount('8.5000')->create();

        $response = $this->actingAsAdmin()->getJson('/api/v1/expenses/'.$expense->id)->assertOk();

        foreach (['amount', 'margin_amount', 'spend_amount'] as $field) {
            $this->assertIsString($response->json('data.'.$field), $field.' should be a string');
        }

        $this->assertSame('8.5000', $response->json('data.amount'));
    }

    /**
     * `incurred_on` is a calendar day and `created_at` is a moment, and the resource
     * sends them differently on purpose - a month-end figure has to land in the
     * month the cost belongs to, not the week somebody got round to entering it.
     */
    public function test_the_incurred_date_is_a_day_and_created_at_is_a_moment(): void
    {
        $expense = Expense::factory()->incurredOn('2026-08-15')->create();

        $response = $this->actingAsAdmin()->getJson('/api/v1/expenses/'.$expense->id)->assertOk();

        $this->assertSame('2026-08-15', $response->json('data.incurred_on'));
        $this->assertStringContainsString('T', (string) $response->json('data.created_at'));
    }

    public function test_a_booking_level_cost_attaches_to_no_consignment(): void
    {
        $booking = Booking::factory()->create();

        $expense = Expense::factory()->bookingCost()->create(['booking_id' => $booking->id]);

        $this->assertNull($expense->shipment_id);
        $this->assertSame($booking->id, $expense->booking_id);

        $this->actingAsAdmin()
            ->getJson('/api/v1/expenses/'.$expense->id)
            ->assertOk()
            ->assertJsonPath('data.shipment', null)
            ->assertJsonPath('data.booking.id', $booking->id);
    }
}
