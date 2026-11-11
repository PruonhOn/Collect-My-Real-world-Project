<?php

namespace Tests\Feature\Finance;

use App\Enums\ChargeStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PartyType;
use App\Models\Charge;
use App\Models\Invoice;
use App\Models\Party;
use App\Models\Payment;
use App\Models\Shipment;

/**
 * The invoice module: what the business has asked a customer for.
 *
 * The centre of the chain, and the only record here that is a document. What is
 * worth pinning down is the split between the status a person sets and the status
 * the money implies, the two lifecycle actions, and the late rule.
 *
 * - `partially_paid` and `paid` cannot be set by a client, only reached
 * - issuing stamps the date once and never moves it again
 * - a void invoice is frozen, and nothing rewrites its figures
 * - due today is not overdue, and an invoice with no due date never is
 */
class InvoiceTest extends FinanceTestCase
{
    public function test_it_requires_a_session(): void
    {
        $this->getJson('/api/v1/invoices')->assertUnauthorized();
    }

    public function test_a_viewer_can_read_but_not_write(): void
    {
        $invoice = Invoice::factory()->create();

        $this->actingAsRole('viewer')->getJson('/api/v1/invoices')->assertOk();

        $this->actingAsRole('viewer')
            ->patchJson('/api/v1/invoices/'.$invoice->id, ['notes' => 'x'])
            ->assertForbidden();
    }

    /**
     * An auditor's view is the one this split is designed for: read a bill without
     * being able to void one.
     */
    public function test_a_viewer_cannot_issue_or_void(): void
    {
        $invoice = Invoice::factory()->create();

        $this->actingAsRole('viewer')
            ->postJson('/api/v1/invoices/'.$invoice->id.'/issue')
            ->assertForbidden();

        $this->actingAsRole('viewer')
            ->postJson('/api/v1/invoices/'.$invoice->id.'/void')
            ->assertForbidden();
    }

    public function test_it_needs_a_customer_and_normalises_the_reference(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/invoices', ['reference' => 'inv-2026-000451'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['customer_id']);

        $customer = Party::factory()->create();

        $this->actingAsAdmin()
            ->postJson('/api/v1/invoices', [
                'customer_id' => $customer->id,
                'reference' => 'inv-2026-000451',
            ])
            ->assertCreated()
            ->assertJsonPath('data.reference', 'INV-2026-000451')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.total', '0.0000');
    }

    /**
     * A shipper is not somebody you can bill. The invoice's rule is the strictest
     * place the party type matters, and a wrong one would put a receivable against
     * a party that never owed anything.
     */
    public function test_it_rejects_a_party_that_is_not_a_customer(): void
    {
        $shipper = Party::factory()->ofType(PartyType::Shipper)->create();

        $this->actingAsAdmin()
            ->postJson('/api/v1/invoices', [
                'customer_id' => $shipper->id,
                'reference' => 'INV-2026-000900',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['customer_id']);
    }

    public function test_a_client_cannot_mark_an_invoice_paid(): void
    {
        $invoice = $this->invoiceFor('1000.0000');

        $this->actingAsAdmin()
            ->patchJson('/api/v1/invoices/'.$invoice->id, ['status' => 'paid'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        $invoice->refresh();

        $this->assertSame(InvoiceStatus::Issued, $invoice->status);
        $this->assertSame('1000.0000', $invoice->balance);
    }

    /**
     * A bill cannot be sent with a PATCH.
     *
     * `issue` is an action rather than a field because sending an invoice is an act
     * with two guards attached: it re-settles the totals first, and it refuses a
     * zero total - "sending a bill for nothing is the one mistake here that reaches
     * a customer". A PATCH that happened to change `status` would carry neither, and
     * would leave `issue_date` unset, so the document a customer holds would have no
     * date on it.
     */
    public function test_a_draft_cannot_be_issued_through_a_patch(): void
    {
        $shipment = Shipment::factory()->create();
        $customer = Party::factory()->create();

        $charge = Charge::factory()->priced('1.0000', '300.0000')->create([
            'shipment_id' => $shipment->id,
        ]);

        $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);
        $invoice->charges()->save($charge);
        $invoice->settle(lineDerived: true)->save();

        $this->actingAsAdmin()
            ->patchJson('/api/v1/invoices/'.$invoice->id, ['status' => 'issued'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        $invoice->refresh();

        $this->assertSame(InvoiceStatus::Draft, $invoice->status);
        $this->assertNull($invoice->issue_date);
    }

    /**
     * `void` stays settable, unlike `issued`.
     *
     * Asserted on a *draft* because that is the only invoice a PATCH can reach at
     * all - an issued one is refused by `guardEditable()` before the rules are
     * consulted, and a write-off of a sent bill is the `void` action's job rather
     * than a field anybody can flip in a form.
     */
    public function test_a_write_off_is_still_reachable_through_a_patch(): void
    {
        $invoice = Invoice::factory()->create();

        $this->actingAsAdmin()
            ->patchJson('/api/v1/invoices/'.$invoice->id, ['status' => 'void'])
            ->assertOk()
            ->assertJsonPath('data.status', 'void');

        $this->assertSame(InvoiceStatus::Void, $invoice->refresh()->status);
    }

    /**
     * `paid_amount` and `balance` are dropped rather than rejected, the same
     * mechanism as a charge's `total`: `Request::validate()` returns only the keys
     * its rules cover, so a client cannot set the received figure. Asserted on a
     * *draft*, which is the only state a PATCH can reach - an issued one is refused
     * by `guardEditable()` first, so testing it here would be asserting the wrong
     * refusal.
     */
    public function test_a_client_cannot_set_the_paid_amount(): void
    {
        $invoice = Invoice::factory()->create(['total' => '1000.0000', 'balance' => '1000.0000']);

        $this->actingAsAdmin()
            ->patchJson('/api/v1/invoices/'.$invoice->id, [
                'paid_amount' => '1000.0000',
                'balance' => '0.0000',
            ])
            ->assertOk()
            ->assertJsonPath('data.paid_amount', '0.0000')
            ->assertJsonPath('data.balance', '1000.0000');

        $invoice->refresh();

        $this->assertSame('0.0000', $invoice->paid_amount);
        $this->assertSame(InvoiceStatus::Draft, $invoice->status);
    }

    /**
     * The derivation, and the reason it lives on the server. Two states a person
     * sets, two the money implies, and a client that could set the latter would put
     * a `paid` on an invoice with a balance outstanding.
     */
    public function test_the_paid_status_is_derived_from_the_payments(): void
    {
        $invoice = $this->invoiceFor('1000.0000');

        Payment::factory()->ofAmount('400.0000')->forInvoice($invoice)->create();
        $invoice->settle()->save();

        $this->assertSame(InvoiceStatus::PartiallyPaid, $invoice->refresh()->status);
        $this->assertSame('400.0000', $invoice->paid_amount);
        $this->assertSame('600.0000', $invoice->balance);

        Payment::factory()->ofAmount('600.0000')->forInvoice($invoice)->create();
        $invoice->settle()->save();

        $this->assertSame(InvoiceStatus::Paid, $invoice->refresh()->status);
        $this->assertSame('0.0000', $invoice->balance);
        $this->assertTrue($invoice->isSettled());
    }

    /**
     * A draft with money against it is promoted. An operator who took a payment
     * without sending the bill has still issued it in every way that matters to the
     * ledger, and leaving it looking unbilled would understate what is owed.
     */
    public function test_money_against_a_draft_promotes_it(): void
    {
        $invoice = Invoice::factory()->create(['total' => '500.0000', 'balance' => '500.0000']);

        Payment::factory()->ofAmount('500.0000')->forInvoice($invoice)->create();
        $invoice->settle()->save();

        $this->assertSame(InvoiceStatus::Paid, $invoice->refresh()->status);
    }

    public function test_issuing_stamps_the_issue_date_once(): void
    {
        $shipment = Shipment::factory()->create();
        $customer = Party::factory()->create();

        $charge = Charge::factory()->priced('1.0000', '800.0000')->create([
            'shipment_id' => $shipment->id,
        ]);

        $invoice = $this->actingAsAdmin()
            ->postJson('/api/v1/invoices', [
                'customer_id' => $customer->id,
                'reference' => 'INV-2026-000777',
                'due_date' => now()->addDays(30)->toDateString(),
                'charge_ids' => [$charge->id],
            ])
            ->assertCreated()
            ->assertJsonPath('data.total', '800.0000')
            ->assertJsonPath('data.status', 'draft')
            ->json('data');

        $this->actingAsAdmin()
            ->postJson('/api/v1/invoices/'.$invoice['id'].'/issue')
            ->assertOk()
            ->assertJsonPath('data.status', 'issued')
            ->assertJsonPath('data.issue_date', now()->toDateString());
    }

    public function test_an_issued_invoice_cannot_be_edited(): void
    {
        $invoice = $this->invoiceFor('1000.0000');

        $this->actingAsAdmin()
            ->patchJson('/api/v1/invoices/'.$invoice->id, ['due_date' => '2026-12-01'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_an_issued_invoice_cannot_be_deleted(): void
    {
        $invoice = $this->invoiceFor('1000.0000');

        $this->actingAsAdmin()
            ->deleteJson('/api/v1/invoices/'.$invoice->id)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
    }

    /**
     * Issuing twice is a conflict rather than a silent success, because the caller
     * almost certainly believes it did something and the clock has already started.
     */
    public function test_issuing_twice_is_a_conflict(): void
    {
        $customer = Party::factory()->create();

        $invoice = $this->actingAsAdmin()
            ->postJson('/api/v1/invoices', [
                'customer_id' => $customer->id,
                'reference' => 'INV-2026-000881',
                'due_date' => now()->addDays(30)->toDateString(),
                'charge_ids' => [Charge::factory()->create()->id],
            ])
            ->assertCreated()
            ->json('data');

        $this->actingAsAdmin()
            ->postJson('/api/v1/invoices/'.$invoice['id'].'/issue')
            ->assertOk()
            ->assertJsonPath('data.status', 'issued');

        $this->actingAsAdmin()
            ->postJson('/api/v1/invoices/'.$invoice['id'].'/issue')
            ->assertStatus(409);
    }

    /**
     * A bill for nothing is the one mistake here that reaches a customer.
     */
    public function test_an_invoice_with_nothing_on_it_cannot_be_issued(): void
    {
        $customer = Party::factory()->create();

        $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);

        $this->actingAsAdmin()
            ->postJson('/api/v1/invoices/'.$invoice->id.'/issue')
            ->assertStatus(409);

        $this->assertSame(InvoiceStatus::Draft, $invoice->refresh()->status);
    }

    public function test_a_void_invoice_cannot_be_issued(): void
    {
        $invoice = $this->invoiceFor('1000.0000');

        $this->actingAsAdmin()->postJson('/api/v1/invoices/'.$invoice->id.'/void')->assertOk();

        $this->actingAsAdmin()
            ->postJson('/api/v1/invoices/'.$invoice->id.'/issue')
            ->assertStatus(409);
    }

    /**
     * Voiding twice is idempotent, the opposite of issuing. A double-click or a
     * retried request has already achieved the caller's intent, and failing it would
     * be pedantry in the one place pedantry costs a support call.
     */
    public function test_voiding_twice_is_idempotent(): void
    {
        $invoice = $this->invoiceFor('1000.0000');

        $this->actingAsAdmin()
            ->postJson('/api/v1/invoices/'.$invoice->id.'/void')
            ->assertOk()
            ->assertJsonPath('data.status', 'void');

        $this->actingAsAdmin()
            ->postJson('/api/v1/invoices/'.$invoice->id.'/void')
            ->assertOk()
            ->assertJsonPath('data.status', 'void');
    }

    /**
     * A write-off is a decision, not an arithmetic result, so the figures stay
     * where they were - the amount written off has to be visible on the row rather
     * than implied by a status.
     */
    public function test_voiding_leaves_the_balance_where_it_was(): void
    {
        $invoice = $this->invoiceFor('1000.0000');

        $this->actingAsAdmin()->postJson('/api/v1/invoices/'.$invoice->id.'/void')->assertOk();

        $invoice->refresh();

        $this->assertSame(InvoiceStatus::Void, $invoice->status);
        $this->assertSame('1000.0000', $invoice->balance);
    }

    public function test_a_void_invoice_is_never_overdue(): void
    {
        $invoice = Invoice::factory()->overdue()->create(['status' => InvoiceStatus::Void]);

        $this->assertFalse($invoice->isOverdue());
        $this->assertSame(0, $invoice->daysOverdue());
        $this->assertSame(0, Invoice::query()->overdue()->count());
    }

    /**
     * Deleting a draft has to leave its lines behind as drafts rather than cascade:
     * they are quoted work on a live consignment and are owed money whichever piece
     * of paper happens to be attached to them.
     */
    public function test_deleting_a_draft_detaches_its_lines_rather_than_deleting_them(): void
    {
        $shipment = Shipment::factory()->create();
        $customer = Party::factory()->create();

        $charge = Charge::factory()->priced('1.0000', '300.0000')->create([
            'shipment_id' => $shipment->id,
        ]);

        $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);
        $charge->fill(['invoice_id' => $invoice->id])->save();

        $this->assertSame(ChargeStatus::Issued, $charge->refresh()->status);

        $this->actingAsAdmin()->deleteJson('/api/v1/invoices/'.$invoice->id)->assertOk();

        $charge->refresh();

        $this->assertNull($charge->invoice_id);
        $this->assertSame(ChargeStatus::Draft, $charge->status);
    }

    /**
     * `charge_ids` is the set of lines on the bill, and a PATCH that does not
     * mention it must not detach every line - which is the difference between
     * "these are the lines" and "add these".
     */
    public function test_a_patch_without_charge_ids_leaves_the_lines_alone(): void
    {
        $shipment = Shipment::factory()->create();
        $customer = Party::factory()->create();

        $charge = Charge::factory()->priced('1.0000', '300.0000')->create([
            'shipment_id' => $shipment->id,
        ]);

        $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);
        $charge->fill(['invoice_id' => $invoice->id])->save();

        $this->actingAsAdmin()
            ->patchJson('/api/v1/invoices/'.$invoice->id, ['notes' => 'Consolidated for October'])
            ->assertOk()
            ->assertJsonPath('data.total', '300.0000');

        $this->assertSame($invoice->id, $charge->refresh()->invoice_id);
    }

    /**
     * An empty list *is* a deliberate request, and strips the invoice bare.
     */
    public function test_an_empty_charge_ids_list_detaches_everything(): void
    {
        $shipment = Shipment::factory()->create();
        $customer = Party::factory()->create();

        $charge = Charge::factory()->priced('1.0000', '300.0000')->create([
            'shipment_id' => $shipment->id,
        ]);

        $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);
        $charge->fill(['invoice_id' => $invoice->id])->save();

        $this->actingAsAdmin()
            ->patchJson('/api/v1/invoices/'.$invoice->id, ['charge_ids' => []])
            ->assertOk()
            ->assertJsonPath('data.total', '0.0000');

        $this->assertNull($charge->refresh()->invoice_id);
    }

    /**
     * A line taken off a bill becomes a quote again, not an orphan.
     *
     * `status` is derived from `invoice_id` in `Charge::booted()`, and the detach
     * that removes a line from an invoice is a query-builder `update` - which does
     * not run model events. Without going through the model here the line keeps
     * reading `issued` while sitting on no invoice, and the consignment's billed
     * total and its quoted total then disagree with no way to tell which is right.
     */
    public function test_a_detached_line_becomes_a_draft_again(): void
    {
        $shipment = Shipment::factory()->create();
        $customer = Party::factory()->create();

        $kept = Charge::factory()->priced('1.0000', '300.0000')->create([
            'shipment_id' => $shipment->id,
        ]);

        $removed = Charge::factory()->priced('1.0000', '120.0000')->create([
            'shipment_id' => $shipment->id,
        ]);

        $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);

        $invoice->charges()->saveMany([$kept, $removed]);

        $this->assertSame(ChargeStatus::Issued, $kept->refresh()->status);

        $this->actingAsAdmin()
            ->patchJson('/api/v1/invoices/'.$invoice->id, ['charge_ids' => [$kept->id]])
            ->assertOk()
            ->assertJsonPath('data.total', '300.0000');

        $this->assertSame(ChargeStatus::Draft, $removed->refresh()->status);
        $this->assertNull($removed->invoice_id);
        $this->assertSame(ChargeStatus::Issued, $kept->refresh()->status);
    }

    /**
     * A line already on another invoice cannot be claimed, and the other bill is
     * left exactly as it was.
     *
     * `invoice_id` is the only record of which bill a line is on, so a request
     * naming somebody else's line is asking to invoice the same work twice - and
     * would leave the first bill's totals overstated, because the resettle chain
     * only ever reaches the invoice the request is about.
     */
    public function test_a_line_on_another_invoice_is_refused_and_its_invoice_is_untouched(): void
    {
        $shipment = Shipment::factory()->create();
        $customer = Party::factory()->create();

        $charge = Charge::factory()->priced('1.0000', '300.0000')->create([
            'shipment_id' => $shipment->id,
        ]);

        $first = Invoice::factory()->create(['customer_id' => $customer->id]);
        $first->charges()->save($charge);
        $first->settle(lineDerived: true)->save();

        $second = Invoice::factory()->create(['customer_id' => $customer->id]);

        $this->actingAsAdmin()
            ->patchJson('/api/v1/invoices/'.$second->id, ['charge_ids' => [$charge->id]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('charge_ids');

        $this->assertSame($first->id, $charge->refresh()->invoice_id);
        $this->assertSame('300.0000', $first->refresh()->total);
        $this->assertSame('0.0000', $second->refresh()->total);
    }

    /**
     * The late rule, and the two ways a naive comparison gets it wrong.
     *
     * Due today is not late - the whole day is still available - and an invoice with
     * no deadline has not missed anything, which is what keeps every open-ended
     * retainer off the alarm list.
     */
    public function test_due_today_is_not_overdue_and_an_undated_one_never_is(): void
    {
        $dueToday = $this->invoiceFor('1000.0000', now()->toDateString());
        $past = $this->invoiceFor('1000.0000', now()->subDay()->toDateString());
        $undated = $this->invoiceFor('1000.0000', null);

        $this->assertFalse($dueToday->isOverdue());
        $this->assertTrue($past->isOverdue());
        $this->assertSame(1, $past->daysOverdue());
        $this->assertFalse($undated->isOverdue());
    }

    /**
     * A draft has not been asked for, so it has missed nothing. Counting drafts in
     * the worklist would overstate what is owed by whatever is sitting in
     * somebody's drafts folder.
     */
    public function test_a_draft_is_never_overdue(): void
    {
        $draft = Invoice::factory()->create([
            'total' => '1000.0000',
            'balance' => '1000.0000',
            'due_date' => now()->subDays(20)->toDateString(),
        ]);

        $this->assertFalse($draft->isOverdue());
        $this->assertSame(0, Invoice::query()->overdue()->count());
    }

    /**
     * A settled invoice is not on the list however old it is.
     */
    public function test_a_settled_invoice_is_not_overdue(): void
    {
        $paid = $this->invoiceFor('1000.0000', now()->subDays(40)->toDateString());

        Payment::factory()->ofAmount('1000.0000')->forInvoice($paid)->create();
        $paid->settle()->save();

        $this->assertFalse($paid->refresh()->isOverdue());
        $this->assertSame(0, Invoice::query()->overdue()->count());
    }

    public function test_it_can_be_filtered_to_the_overdue_worklist(): void
    {
        Invoice::factory()->overdue()->count(2)->create();
        Invoice::factory()->issued()->create();
        Invoice::factory()->create();

        $this->actingAsAdmin()
            ->getJson('/api/v1/invoices?overdue=1')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /**
     * `status=overdue` is accepted as well as `?overdue=1`, because a filter
     * dropdown offering issued / partially_paid / paid / overdue reads as four
     * statuses when it is three plus a derived flag.
     */
    public function test_overdue_is_available_as_a_status_filter_too(): void
    {
        Invoice::factory()->overdue()->create();
        Invoice::factory()->issued()->create();

        $this->actingAsAdmin()
            ->getJson('/api/v1/invoices?status=overdue')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    /**
     * A due-date window that includes its closing day. Dropping the 30th from a
     * month-end report is the failure this guard exists to prevent.
     */
    public function test_a_due_date_window_includes_the_closing_day(): void
    {
        Invoice::factory()->create(['due_date' => '2026-09-30', 'total' => '1.0000', 'balance' => '1.0000']);
        Invoice::factory()->create(['due_date' => '2026-10-01', 'total' => '1.0000', 'balance' => '1.0000']);

        $this->actingAsAdmin()
            ->getJson('/api/v1/invoices?due_to=2026-09-30')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.due_date', '2026-09-30');
    }

    public function test_it_can_be_searched_by_customer_name(): void
    {
        $customer = Party::factory()->create(['name' => 'Harbour Freight Ltd']);
        $other = Party::factory()->create();

        $wanted = Invoice::factory()->create(['customer_id' => $customer->id]);
        Invoice::factory()->create(['customer_id' => $other->id]);

        $this->actingAsAdmin()
            ->getJson('/api/v1/invoices?q=Harbour')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $wanted->id);
    }

    /**
     * The dates are `Y-m-d` and not ISO-8601, unlike every other resource in this
     * API. An invoice is dated, not stamped, and a time on it would let a timezone
     * decide whether it was overdue.
     */
    public function test_the_dates_are_sent_as_calendar_days(): void
    {
        $invoice = $this->invoiceFor('1000.0000', '2026-10-15');

        $response = $this->actingAsAdmin()->getJson('/api/v1/invoices/'.$invoice->id)->assertOk();

        $this->assertSame('2026-10-15', $response->json('data.due_date'));
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $response->json('data.issue_date'));

        // And a created/updated_at, which *is* a moment.
        $this->assertStringContainsString('T', (string) $response->json('data.created_at'));
    }

    /**
     * `is_outstanding` is a strict positive, so an overpayment is not "outstanding"
     * - it is a credit this business owes the customer, and a negative balance read
     * as settled would hide it on the one screen somebody would look for it.
     */
    public function test_an_overpayment_is_not_reported_as_outstanding(): void
    {
        $invoice = $this->invoiceFor('0.0000');

        $invoice->applyTotals('100.0000', '0.0000', '100.0000');
        $invoice->applyPaid('150.0000');

        $this->assertSame('-50.0000', $invoice->balance);
        $this->assertFalse($invoice->isOutstanding());
    }

    public function test_the_amounts_are_sent_as_decimal_strings(): void
    {
        $invoice = $this->invoiceFor('1000.0000');

        $response = $this->actingAsAdmin()->getJson('/api/v1/invoices/'.$invoice->id)->assertOk();

        foreach (['subtotal', 'tax_amount', 'total', 'paid_amount', 'balance'] as $field) {
            $this->assertIsString($response->json('data.'.$field), $field.' should be a string');
        }
    }
}
