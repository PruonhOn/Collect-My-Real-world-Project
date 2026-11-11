<?php

namespace Tests\Feature\Finance;

use App\Enums\ChargeStatus;
use App\Models\Charge;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Support\Money;

/**
 * The charge module: priced lines raised against a consignment.
 *
 * CRUD is inherited from `RecordController` and needs no tests of its own. What is
 * worth pinning down here is the arithmetic, the two status rules that are easy to
 * get backwards, and the resettle that has to happen on every write.
 *
 * - the three totals are computed on save, not accepted from a request
 * - a line's status follows its `invoice_id`, and a client cannot set it
 * - moving a line between invoices rewrites *both* totals
 * - a void line leaves the invoice's figure, and a deleted one restores it
 */
class ChargeTest extends FinanceTestCase
{
    public function test_it_requires_a_session(): void
    {
        $this->getJson('/api/v1/charges')->assertUnauthorized();
    }

    public function test_a_viewer_can_read_but_not_write(): void
    {
        $charge = Charge::factory()->create();

        $this->actingAsRole('viewer')->getJson('/api/v1/charges')->assertOk();

        $this->actingAsRole('viewer')
            ->patchJson('/api/v1/charges/'.$charge->id, ['unit_price' => '1.0000'])
            ->assertForbidden();
    }

    public function test_a_charge_computes_its_own_totals_on_save(): void
    {
        $charge = Charge::factory()->priced('3.0000', '250.0000', '5.00')->create();

        // 3 x 250 = 750, plus 5% = 37.50, total 787.50. Written by the model's
        // `creating` hook, not by the factory - the factory omits them entirely.
        $this->assertSame('750.0000', $charge->subtotal);
        $this->assertSame('37.5000', $charge->tax_amount);
        $this->assertSame('787.5000', $charge->total);
    }

    /**
     * The case the four-decimal scale exists for. 0.0875 x 3 is exact in decimal and
     * 0.26249999999999996 in binary, and it is the second number that ends up on a
     * customer's invoice.
     */
    public function test_the_arithmetic_is_decimal_and_not_binary(): void
    {
        $charge = Charge::factory()->priced('3.0000', '0.0875')->create();

        $this->assertSame('0.2625', $charge->total);
        $this->assertNotSame('0.26249999999999996', $charge->total);
    }

    /**
     * A client cannot set the totals, and the mechanism is worth being precise
     * about: `Request::validate()` returns only the keys its rules cover, so an
     * unlisted `total` is dropped from the payload before the model ever sees it.
     * The request is not rejected - it is created, at the server's figure.
     *
     * Asserting the *stored* value rather than a 422 is therefore the honest test.
     * It also checks the thing that actually matters: a client sending its own
     * arithmetic does not get its number onto the invoice.
     */
    public function test_a_client_cannot_set_the_totals(): void
    {
        $shipment = Shipment::factory()->create();

        $this->actingAsAdmin()
            ->postJson('/api/v1/charges', [
                'shipment_id' => $shipment->id,
                'description' => 'Ocean freight',
                'quantity' => '2.0000',
                'unit_price' => '100.0000',
                'tax_rate' => '0.00',

                // 2 x 100 = 200, and 9% of 200 is 18, so a client that guessed would
                // expect 218. The server's own figures are 200 and 200.
                'subtotal' => '1.0000',
                'tax_amount' => '17.0000',
                'total' => '1.0000',
            ])
            ->assertCreated()
            ->assertJsonPath('data.subtotal', '200.0000')
            ->assertJsonPath('data.tax_amount', '0.0000')
            ->assertJsonPath('data.total', '200.0000');

        $this->assertDatabaseHas('charges', [
            'subtotal' => '200.0000',
            'total' => '200.0000',
        ]);
    }

    public function test_a_charge_needs_a_shipment(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/charges', ['description' => 'Ocean freight'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['shipment_id']);
    }

    public function test_it_rejects_a_non_numeric_amount_rather_than_storing_zero(): void
    {
        $shipment = Shipment::factory()->create();

        $this->actingAsAdmin()
            ->postJson('/api/v1/charges', [
                'shipment_id' => $shipment->id,
                'description' => 'Ocean freight',
                'unit_price' => 'not a number',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['unit_price']);

        $this->assertDatabaseCount('charges', 0);
    }

    /**
     * The status follows the invoice and is not the client's to set. A request
     * posting `status: draft` alongside an `invoice_id` cannot leave a bill showing
     * a line that is not on it.
     */
    public function test_the_status_follows_the_invoice_and_cannot_be_set(): void
    {
        $shipment = Shipment::factory()->create();
        $invoice = $this->invoiceFor('1000.0000');

        $this->actingAsAdmin()
            ->postJson('/api/v1/charges', [
                'shipment_id' => $shipment->id,
                'invoice_id' => $invoice->id,
                'description' => 'Ocean freight',
                'status' => 'draft',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        $charge = Charge::factory()->onInvoice($invoice)->create(['shipment_id' => $shipment->id]);

        $this->assertSame(ChargeStatus::Issued, $charge->status);
    }

    /**
     * Detaching a line takes it back to a draft, because it is being quoted again
     * rather than billed.
     */
    public function test_detaching_a_line_returns_it_to_draft(): void
    {
        $charge = Charge::factory()->create();

        $this->assertSame(ChargeStatus::Draft, $charge->status);

        $charge->fill(['invoice_id' => $this->invoiceFor('100.0000')->id])->save();

        $this->assertSame(ChargeStatus::Issued, $charge->status);

        $charge->fill(['invoice_id' => null])->save();

        $this->assertSame(ChargeStatus::Draft, $charge->status);
    }

    /**
     * A void is a person's decision about the line, and attaching or detaching an
     * invoice is not that decision - so the model leaves it alone.
     */
    public function test_a_void_line_is_not_resurrected_by_an_invoice_id(): void
    {
        $charge = Charge::factory()->voided()->create();

        $charge->fill(['invoice_id' => $this->invoiceFor('100.0000')->id])->save();

        $this->assertSame(ChargeStatus::Void, $charge->status);
        $this->assertFalse($charge->isBillable());
    }

    /**
     * The resettle. Attaching a line through the API has to move the invoice's
     * total, and it does so inside the same transaction as the write.
     */
    public function test_attaching_a_line_through_the_api_rewrites_the_invoice_total(): void
    {
        $shipment = Shipment::factory()->create();
        $invoice = $this->invoiceFor('0.0000');

        $this->actingAsAdmin()
            ->postJson('/api/v1/charges', [
                'shipment_id' => $shipment->id,
                'invoice_id' => $invoice->id,
                'description' => 'Ocean freight',
                'quantity' => '2.0000',
                'unit_price' => '250.0000',
                'tax_rate' => '0.00',
            ])
            ->assertCreated()
            ->assertJsonPath('data.total', '500.0000')
            ->assertJsonPath('data.is_invoiced', true);

        $invoice->refresh();

        $this->assertSame('500.0000', $invoice->subtotal);
        $this->assertSame('500.0000', $invoice->total);
        $this->assertSame('500.0000', $invoice->balance);
    }

    /**
     * Moving a line has to rewrite the invoice it left as well as the one it
     * joined. Doing only the second is the bug this test exists to prevent: the
     * first invoice would keep charging the customer for work now billed
     * elsewhere.
     */
    public function test_moving_a_line_rewrites_both_invoices(): void
    {
        $shipment = Shipment::factory()->create();
        $first = $this->invoiceFor('0.0000');
        $second = $this->invoiceFor('0.0000');

        $charge = Charge::factory()
            ->priced('1.0000', '400.0000')
            ->onInvoice($first)
            ->create(['shipment_id' => $shipment->id]);

        $first->settle()->save();

        $this->assertSame('400.0000', $first->refresh()->total);

        $this->actingAsAdmin()
            ->patchJson('/api/v1/charges/'.$charge->id, ['invoice_id' => $second->id])
            ->assertOk();

        $this->assertSame('0.0000', $first->refresh()->total);
        $this->assertSame('400.0000', $second->refresh()->total);
    }

    /**
     * A void line leaves the invoice's figure, which is the whole point of
     * `Invoice::recalculate()` summing only billable lines.
     */
    public function test_voiding_a_line_takes_it_off_the_invoice_total(): void
    {
        $shipment = Shipment::factory()->create();
        $invoice = $this->invoiceFor('0.0000');

        $charge = Charge::factory()->priced('1.0000', '400.0000')->create([
            'shipment_id' => $shipment->id,
            'invoice_id' => $invoice->id,
        ]);

        $invoice->settle()->save();
        $this->assertSame('400.0000', $invoice->refresh()->total);

        $this->actingAsAdmin()
            ->patchJson('/api/v1/charges/'.$charge->id, ['status' => 'void'])
            ->assertOk()
            ->assertJsonPath('data.status', 'void')
            ->assertJsonPath('data.is_billable', false);

        $this->assertSame('0.0000', $invoice->refresh()->total);
    }

    /**
     * Deleting a line has to give the money back, or the invoice keeps billing for
     * work that is no longer on it.
     */
    public function test_deleting_a_line_restores_the_invoice_total(): void
    {
        $shipment = Shipment::factory()->create();
        $invoice = $this->invoiceFor('0.0000');

        $charge = Charge::factory()->priced('1.0000', '150.0000')->create([
            'shipment_id' => $shipment->id,
            'invoice_id' => $invoice->id,
        ]);

        $invoice->settle()->save();
        $this->assertSame('150.0000', $invoice->refresh()->total);

        $this->actingAsAdmin()
            ->deleteJson('/api/v1/charges/'.$charge->id)
            ->assertOk();

        $this->assertSame('0.0000', $invoice->refresh()->total);
    }

    public function test_it_can_be_filtered_to_uninvoiced_work(): void
    {
        $invoice = $this->invoiceFor('1000.0000');

        Charge::factory()->count(3)->create();
        Charge::factory()->count(2)->onInvoice($invoice)->create();

        $this->actingAsAdmin()
            ->getJson('/api/v1/charges?uninvoiced=1')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_it_can_be_searched_by_consignment_reference(): void
    {
        $wanted = Shipment::factory()->create(['reference' => 'SHP-2026-FIN01']);
        Shipment::factory()->count(2)->create();

        $charge = Charge::factory()->create(['shipment_id' => $wanted->id]);

        $this->actingAsAdmin()
            ->getJson('/api/v1/charges?q=FIN01')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $charge->id);
    }

    public function test_the_listing_sends_the_consignment_and_invoice_eager_loaded(): void
    {
        $shipment = Shipment::factory()->create();
        $invoice = $this->invoiceFor('0.0000');

        Charge::factory()->onInvoice($invoice)->create(['shipment_id' => $shipment->id]);

        $this->actingAsAdmin()
            ->getJson('/api/v1/charges')
            ->assertOk()
            ->assertJsonPath('data.0.shipment.id', $shipment->id)
            ->assertJsonPath('data.0.invoice.id', $invoice->id)
            ->assertJsonStructure(['data' => [['shipment' => ['id'], 'invoice' => ['id', 'reference']]]]);
    }

    /**
     * The decimal strings, not numbers. A `decimal(15,4)` read into a JS number is
     * a float, and the trailing zeros the column means are gone - so a form
     * round-tripping `8.5000` would send `8.5` and a total re-added in the browser
     * would differ from the server's.
     */
    public function test_the_totals_are_sent_as_decimal_strings(): void
    {
        $charge = Charge::factory()->priced('1.0000', '8.5000')->create();

        $response = $this->actingAsAdmin()->getJson('/api/v1/charges/'.$charge->id)->assertOk();

        $this->assertIsString($response->json('data.unit_price'));
        $this->assertIsString($response->json('data.total'));
        $this->assertSame('8.5000', $response->json('data.total'));
    }

    /**
     * A consignment's billed figure is the sum over its billable charges, and the
     * relation is scoped to exactly that set - so the figure can be checked by hand
     * against the invoice.
     */
    public function test_a_consignment_billed_total_excludes_void_lines(): void
    {
        $shipment = Shipment::factory()->create();

        Charge::factory()->priced('1.0000', '100.0000')->create(['shipment_id' => $shipment->id]);
        Charge::factory()->priced('1.0000', '60.0000')->create(['shipment_id' => $shipment->id]);
        Charge::factory()->priced('1.0000', '999.0000')->voided()->create(['shipment_id' => $shipment->id]);

        $this->assertSame(2, $shipment->charges()->count());
        $this->assertSame(
            '160.0000',
            Money::sum($shipment->charges()->pluck('total')->all()),
        );
    }

    /**
     * A charge reference is a description, and the model's `REFERENCE` constant
     * points at it. Nothing is generated, so this is mostly here to record that the
     * search target is the description and not the notes.
     */
    public function test_the_searchable_column_is_the_description(): void
    {
        $this->assertSame('description', Charge::REFERENCE);

        $charge = Charge::factory()->create([
            'description' => 'Demurrage, terminal Rotterdam',
            'notes' => 'internal aside nobody searches for',
        ]);

        $this->actingAsAdmin()
            ->getJson('/api/v1/charges?q=Demurrage')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $charge->id);
    }
}
