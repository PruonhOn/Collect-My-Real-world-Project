<?php

namespace Tests\Feature\Finance;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Party;
use App\Models\Payment;

/**
 * The payment module: money received against an invoice.
 *
 * The controller the whole finance design is arranged around. A payment is not a
 * record of its own - it is the thing that moves a balance - so what is worth
 * pinning down is that the payment and the invoice's new figures are one fact
 * rather than two, and the cases where money and status come apart.
 *
 * - a partial receipt is `partially_paid`, a full one is `paid`
 * - deleting a receipt gives the money back rather than decrementing
 * - a credit is a positive amount with the `adjustment` method, never a negative
 * - an overpayment is refused, because there is no screen for the credit it makes
 * - the payer is copied from the invoice and cannot be set by a client
 */
class PaymentTest extends FinanceTestCase
{
    public function test_it_requires_a_session(): void
    {
        $this->getJson('/api/v1/payments')->assertUnauthorized();
    }

    public function test_a_viewer_can_read_but_not_record(): void
    {
        $invoice = $this->invoiceFor('1000.0000');

        $payment = Payment::factory()->forInvoice($invoice)->create();

        $this->actingAsRole('viewer')->getJson('/api/v1/payments')->assertOk();

        $this->actingAsRole('viewer')
            ->postJson('/api/v1/payments', [
                'invoice_id' => $invoice->id,
                'reference' => 'PAY-2026-000001',
                'amount' => '10.0000',
                'method' => 'cash',
            ])
            ->assertForbidden();

        $this->assertNotNull($payment);
    }

    public function test_recording_a_payment_moves_the_invoice_balance(): void
    {
        $invoice = $this->invoiceFor('1000.0000');

        $this->actingAsAdmin()
            ->postJson('/api/v1/payments', [
                'invoice_id' => $invoice->id,
                'reference' => 'pay-2026-000451',
                'amount' => '400.0000',
                'method' => 'bank_transfer',
                'bank_reference' => 'FT99231',
            ])
            ->assertCreated()
            // Upper-cased on the way in, so the receipt number in an accounting
            // package and the one typed here are the same row.
            ->assertJsonPath('data.reference', 'PAY-2026-000451')
            ->assertJsonPath('data.amount', '400.0000')
            ->assertJsonPath('data.is_receipt', true)
            ->assertJsonPath('data.needs_reconciliation', true);

        $invoice->refresh();

        $this->assertSame('400.0000', $invoice->paid_amount);
        $this->assertSame('600.0000', $invoice->balance);
        $this->assertSame(InvoiceStatus::PartiallyPaid, $invoice->status);
    }

    public function test_settling_in_full_marks_the_invoice_paid(): void
    {
        $invoice = $this->invoiceFor('1000.0000');

        Payment::factory()->ofAmount('1000.0000')->forInvoice($invoice)->create();
        $invoice->settle()->save();

        $this->assertSame(InvoiceStatus::Paid, $invoice->refresh()->status);
        $this->assertSame('0.0000', $invoice->balance);
        $this->assertTrue($invoice->isSettled());
    }

    /**
     * The transaction, and the reason this controller overrides all three writes.
     * A payment written without the invoice's new balance would leave the ledger
     * contradicting itself, and a receivables screen that disagrees with the bank
     * is worse than one that has to resolve a reference.
     */
    public function test_the_payment_and_the_new_balance_are_one_fact(): void
    {
        $invoice = $this->invoiceFor('2500.0000');

        $this->actingAsAdmin()
            ->postJson('/api/v1/payments', [
                'invoice_id' => $invoice->id,
                'reference' => 'PAY-2026-000500',
                'amount' => '1500.0000',
                'method' => 'bank_transfer',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'reference' => 'PAY-2026-000500',
        ]);

        // Both committed, and consistent with each other.
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'paid_amount' => '1500.0000',
            'balance' => '1000.0000',
        ]);
    }

    /**
     * The payer is a copy of the invoice's customer, and a client that could set it
     * would make a receipt's history depend on a field nobody has to think about.
     */
    public function test_the_payer_is_copied_from_the_invoice_and_cannot_be_set(): void
    {
        $invoice = $this->invoiceFor('1000.0000');

        $other = Party::factory()->create();

        $payment = $this->actingAsAdmin()
            ->postJson('/api/v1/payments', [
                'invoice_id' => $invoice->id,
                'reference' => 'PAY-2026-000600',
                'amount' => '100.0000',
                'method' => 'bank_transfer',
                'customer_id' => $other->id,
            ])
            ->assertCreated();

        $this->assertSame($invoice->customer_id, $payment->json('data.customer.id'));
    }

    /**
     * A negative amount is refused. A credit is `PaymentMethod::Adjustment` with a
     * *positive* amount, and a negative bank transfer is a confusing thing to find
     * in a reconciliation.
     */
    public function test_it_rejects_a_negative_amount(): void
    {
        $invoice = $this->invoiceFor('1000.0000');

        $this->actingAsAdmin()
            ->postJson('/api/v1/payments', [
                'invoice_id' => $invoice->id,
                'reference' => 'PAY-2026-000700',
                'amount' => '-250.0000',
                'method' => 'bank_transfer',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);

        $this->assertDatabaseCount('payments', 0);
    }

    /**
     * A credit against the invoice. Positive amount, `adjustment` method, and the
     * balance goes *up* rather than down - which is what makes it a refund rather
     * than a receipt, and is why `settle()` signs the sum by the method instead of
     * adding every amount.
     */
    public function test_a_credit_is_a_positive_amount_with_the_adjustment_method(): void
    {
        $invoice = $this->invoiceFor('1000.0000');

        $payment = Payment::factory()
            ->forInvoice($invoice)
            ->credit()
            ->create(['amount' => '120.0000']);

        $invoice->settle()->save();

        $this->assertSame(PaymentMethod::Adjustment, $payment->method);
        $this->assertTrue($payment->amount > 0);
        $this->assertFalse($payment->isReceipt());

        $this->assertSame('-120.0000', $invoice->refresh()->paid_amount);
        $this->assertSame('1120.0000', $invoice->balance);
    }

    /**
     * An overpayment leaves a negative balance, which is money this business owes
     * the customer, and there is no screen for that here. Refused with a 422 that
     * names the field rather than stored.
     */
    public function test_it_refuses_a_payment_larger_than_the_balance(): void
    {
        $invoice = $this->invoiceFor('1000.0000');

        $this->actingAsAdmin()
            ->postJson('/api/v1/payments', [
                'invoice_id' => $invoice->id,
                'reference' => 'PAY-2026-000800',
                'amount' => '1500.0000',
                'method' => 'bank_transfer',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);

        $this->assertDatabaseCount('payments', 0);
        $this->assertSame('1000.0000', $invoice->refresh()->balance);
    }

    /**
     * Exactly the balance is fine - the guard is a strict greater-than, not a
     * greater-than-or-equal. Paying an invoice off exactly is the common case and
     * must not be rejected by an off-by-one.
     */
    public function test_paying_exactly_the_balance_is_allowed(): void
    {
        $invoice = $this->invoiceFor('1000.0000');

        $this->actingAsAdmin()
            ->postJson('/api/v1/payments', [
                'invoice_id' => $invoice->id,
                'reference' => 'PAY-2026-000850',
                'amount' => '1000.0000',
                'method' => 'bank_transfer',
            ])
            ->assertCreated();

        $this->assertSame(InvoiceStatus::Paid, $invoice->refresh()->status);
    }

    /**
     * A part payment does not lock the invoice, so the remainder has to be
     * receivable too.
     */
    public function test_the_guard_works_against_the_remaining_balance(): void
    {
        $invoice = $this->invoiceFor('1000.0000');

        Payment::factory()->ofAmount('600.0000')->forInvoice($invoice)->create();
        $invoice->settle()->save();

        $this->actingAsAdmin()
            ->postJson('/api/v1/payments', [
                'invoice_id' => $invoice->id,
                'reference' => 'PAY-2026-000900',
                'amount' => '500.0000',
                'method' => 'bank_transfer',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    /**
     * Editing a receipt upward is checked against the balance with the receipt's own
     * contribution taken back out first - otherwise correcting a 500 to 900 on an
     * invoice owing 700 would be rejected against a figure that still counted the
     * 500.
     */
    public function test_editing_a_receipt_upward_is_checked_against_the_remaining_balance(): void
    {
        $invoice = $this->invoiceFor('1000.0000');

        $payment = Payment::factory()->ofAmount('300.0000')->forInvoice($invoice)->create();
        $invoice->settle()->save();

        $this->actingAsAdmin()
            ->patchJson('/api/v1/payments/'.$payment->id, ['amount' => '700.0000'])
            ->assertOk();

        $invoice->refresh();

        $this->assertSame('700.0000', $invoice->paid_amount);
        $this->assertSame('300.0000', $invoice->balance);

        // 400 more would exceed what is left.
        $this->actingAsAdmin()
            ->patchJson('/api/v1/payments/'.$payment->id, ['amount' => '1100.0000'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    /**
     * Deleting gives the money back, and the balance is recomputed as a sum of what
     * exists rather than decremented. Decrementing would drift, and this is exactly
     * the case where somebody will try to reconstruct the figures.
     */
    public function test_deleting_a_receipt_gives_the_money_back(): void
    {
        $invoice = $this->invoiceFor('1000.0000');

        Payment::factory()->ofAmount('400.0000')->forInvoice($invoice)->create();
        Payment::factory()->ofAmount('250.0000')->forInvoice($invoice)->create();
        $invoice->settle()->save();

        $this->assertSame('350.0000', $invoice->refresh()->balance);

        $doomed = Payment::query()->where('amount', '400.0000')->firstOrFail();

        $this->actingAsAdmin()->deleteJson('/api/v1/payments/'.$doomed->id)->assertOk();

        $invoice->refresh();

        $this->assertSame('250.0000', $invoice->paid_amount);
        $this->assertSame('750.0000', $invoice->balance);
        $this->assertSame(InvoiceStatus::PartiallyPaid, $invoice->status);
    }

    /**
     * Deleting the last receipt moves the invoice *back* from `paid` to
     * `partially_paid`. The derived status is recomputed rather than only ever
     * moving forward, because a deleted receipt is a correction somebody has to be
     * able to make.
     */
    public function test_deleting_the_last_receipt_moves_the_status_back(): void
    {
        $invoice = $this->invoiceFor('1000.0000');

        $payment = Payment::factory()->ofAmount('1000.0000')->forInvoice($invoice)->create();
        $invoice->settle()->save();

        $this->assertSame(InvoiceStatus::Paid, $invoice->refresh()->status);

        $this->actingAsAdmin()->deleteJson('/api/v1/payments/'.$payment->id)->assertOk();

        $this->assertSame(InvoiceStatus::Issued, $invoice->refresh()->status);
        $this->assertSame('1000.0000', $invoice->balance);
    }

    /**
     * `paid_at` defaults to now in the model rather than being required, because an
     * operator entering a payment in the evening has not got the bank's timestamp to
     * hand.
     */
    public function test_the_payment_time_defaults_to_now(): void
    {
        $invoice = $this->invoiceFor('1000.0000');

        $this->actingAsAdmin()
            ->postJson('/api/v1/payments', [
                'invoice_id' => $invoice->id,
                'reference' => 'PAY-2026-000950',
                'amount' => '100.0000',
                'method' => 'cash',
            ])
            ->assertCreated()
            ->assertJsonPath('data.method', 'cash')
            // Counted at the counter, so it does not have to be matched to a
            // statement before it can be trusted.
            ->assertJsonPath('data.needs_reconciliation', false);
    }

    public function test_it_can_be_filtered_to_receipts_needing_reconciliation(): void
    {
        $invoice = $this->invoiceFor('1000.0000');

        Payment::factory()->count(2)->needingReconciliation()->forInvoice($invoice)->create();
        Payment::factory()->count(3)->countedInCash()->forInvoice($invoice)->create();

        $this->actingAsAdmin()
            ->getJson('/api/v1/payments?needs_reconciliation=1')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /**
     * A day's takings is cut by the clock, so the window is on `paid_at` and a bare
     * date goes through `whereDate` - otherwise the closing day would be compared
     * against midnight and silently dropped.
     */
    public function test_a_payment_window_includes_the_closing_day(): void
    {
        $invoice = $this->invoiceFor('5000.0000');

        Payment::factory()->forInvoice($invoice)->create([
            'paid_at' => now()->toDateString().' 09:00:00',
        ]);

        Payment::factory()->forInvoice($invoice)->create([
            'paid_at' => now()->addDay()->toDateString().' 09:00:00',
        ]);

        $this->actingAsAdmin()
            ->getJson('/api/v1/payments?paid_to='.now()->toDateString())
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_it_can_be_searched_by_bank_reference(): void
    {
        $invoice = $this->invoiceFor('1000.0000');

        $wanted = Payment::factory()->forInvoice($invoice)->create([
            'bank_reference' => 'FT-UNIQUE-8812',
        ]);

        Payment::factory()->count(2)->forInvoice($invoice)->create();

        $this->actingAsAdmin()
            ->getJson('/api/v1/payments?q=FT-UNIQUE-8812')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $wanted->id);
    }

    public function test_it_can_be_searched_by_invoice_reference(): void
    {
        $invoice = Invoice::factory()->create(['reference' => 'INV-2026-FIN99']);
        $other = $this->invoiceFor('100.0000');

        $payment = Payment::factory()->forInvoice($invoice)->create();
        Payment::factory()->forInvoice($other)->create();

        $this->actingAsAdmin()
            ->getJson('/api/v1/payments?q=FIN99')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $payment->id);
    }

    /**
     * A receipt is an *event* that happened at a moment, unlike an invoice which is
     * dated. Truncating `paid_at` to a date would let a payment made at 23:50 be
     * counted in a different reconciliation from the one it was entered in.
     */
    public function test_the_payment_time_is_a_full_timestamp(): void
    {
        $invoice = $this->invoiceFor('1000.0000');

        $payment = Payment::factory()->forInvoice($invoice)->create([
            'paid_at' => '2026-09-15T14:32:00+00:00',
        ]);

        $response = $this->actingAsAdmin()->getJson('/api/v1/payments/'.$payment->id)->assertOk();

        $this->assertStringContainsString('T', (string) $response->json('data.paid_at'));
    }

    public function test_a_credit_is_not_reported_as_reducing_the_balance(): void
    {
        $invoice = $this->invoiceFor('1000.0000');

        $payment = Payment::factory()->forInvoice($invoice)->credit()->create();

        $response = $this->actingAsAdmin()->getJson('/api/v1/payments/'.$payment->id)->assertOk();

        $this->assertFalse($response->json('data.is_receipt'));
        $this->assertTrue($response->json('data.amount') > 0);
    }

    /**
     * Moving a receipt is two invoices changing, not one.
     *
     * The invoice it left keeps a `paid_amount` that counted the money, and
     * nothing else would ever recompute it - there is no hook on `invoice_id`. An
     * aged receivables figure that still shows money which has left is precisely
     * the sort of stale number that gets acted on.
     */
    public function test_moving_a_receipt_resettles_both_invoices(): void
    {
        $from = $this->invoiceFor('1000.0000');
        $to = $this->invoiceFor('800.0000');

        $payment = Payment::factory()->ofAmount('400.0000')->forInvoice($from)->create();

        $from->settle()->save();
        $to->settle()->save();

        $this->actingAsAdmin()
            ->patchJson('/api/v1/payments/'.$payment->id, ['invoice_id' => $to->id])
            ->assertOk();

        $from->refresh();
        $to->refresh();

        $this->assertSame('0.0000', $from->paid_amount);
        $this->assertSame('1000.0000', $from->balance);
        $this->assertSame(InvoiceStatus::Issued, $from->status);

        $this->assertSame('400.0000', $to->paid_amount);
        $this->assertSame('400.0000', $to->balance);
        $this->assertSame(InvoiceStatus::PartiallyPaid, $to->status);
    }

    /**
     * The overpayment guard has to be honest about where the money currently is.
     *
     * A payment being moved onto an invoice is not counted in that invoice's
     * balance, so the guard must not add its amount back as though it were - which
     * would quietly allow a receipt larger than the balance on the one path
     * nobody exercises by hand.
     */
    public function test_the_overpayment_guard_is_not_given_a_free_allowance_when_moving(): void
    {
        $from = $this->invoiceFor('1000.0000');
        $to = $this->invoiceFor('300.0000');

        $payment = Payment::factory()->ofAmount('100.0000')->forInvoice($from)->create();

        $from->settle()->save();
        $to->settle()->save();

        $this->actingAsAdmin()
            ->patchJson('/api/v1/payments/'.$payment->id, [
                'invoice_id' => $to->id,
                'amount' => '500.0000',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);

        $this->assertSame('300.0000', $to->refresh()->balance);
    }

    /**
     * The payer follows the invoice. A receipt cannot stay attached to a customer
     * who is not the one being billed, or a statement and its receipts would
     * disagree about who owes what.
     */
    public function test_the_payer_follows_the_invoice_when_a_receipt_is_moved(): void
    {
        $from = $this->invoiceFor('1000.0000');
        $to = $this->invoiceFor('800.0000');

        $payment = Payment::factory()->ofAmount('400.0000')->forInvoice($from)->create();

        $this->actingAsAdmin()
            ->patchJson('/api/v1/payments/'.$payment->id, ['invoice_id' => $to->id])
            ->assertOk()
            ->assertJsonPath('data.invoice.id', $to->id)
            ->assertJsonPath('data.customer.id', $to->customer_id);
    }
}
