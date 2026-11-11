<?php

namespace Tests\Feature\Customs;

use App\Enums\CustomsStatus;
use App\Models\Port;
use App\Models\Shipment;
use App\Models\ShipmentCustomsClearance;
use Illuminate\Database\QueryException;
use Tests\Feature\MasterData\MasterDataTestCase;

/**
 * The customs module: where a consignment stands with the authority at one port.
 *
 * CRUD is inherited. What is worth pinning down is the key and the two worklists:
 *
 * - a filing belongs to a consignment and a port, so a transshipment has more than
 *   one and a refusal does not overwrite the previous attempt
 * - a port can be removed without putting a hole in a cargo manifest
 * - `held` and `needs_attention` are different questions and must stay different
 */
class CustomsTest extends MasterDataTestCase
{
    public function test_it_requires_a_session(): void
    {
        $this->getJson('/api/v1/customs')->assertUnauthorized();
    }

    public function test_a_viewer_can_read_but_not_write(): void
    {
        $clearance = ShipmentCustomsClearance::factory()->submitted()->create();

        $this->actingAsRole('viewer');
        $this->getJson('/api/v1/customs')->assertOk();

        $this->patchJson('/api/v1/customs/'.$clearance->id, ['status' => 'approved'])
            ->assertForbidden();
    }

    public function test_a_clerk_can_file_a_clearance_but_not_remove_one(): void
    {
        $shipment = Shipment::factory()->create();
        $port = Port::factory()->create();

        $this->actingAsRole('operations-clerk')
            ->postJson('/api/v1/customs', [
                'shipment_id' => $shipment->id,
                'port_id' => $port->id,
                'status' => 'submitted',
                'entry_number' => '1234/2026',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'submitted')
            ->assertJsonPath('data.status_label', 'Submitted');

        $clearance = ShipmentCustomsClearance::query()->firstOrFail();

        $this->actingAsRole('operations-clerk')
            ->deleteJson('/api/v1/customs/'.$clearance->id)
            ->assertForbidden();
    }

    /**
     * A record is opened when somebody needs to chase something, and the truth at
     * that moment is that nothing has been filed.
     */
    public function test_a_clearance_starts_pending_with_nothing_on_it(): void
    {
        $clearance = ShipmentCustomsClearance::factory()->create();

        $this->assertSame(CustomsStatus::Pending, $clearance->status);
        $this->assertTrue($clearance->isHeld());
        $this->assertFalse($clearance->needsAttention());
        $this->assertFalse($clearance->isClosed());
        $this->assertNull($clearance->entry_number);
    }

    /**
     * Cleared on paper is not the same as collected, and the gap between them is
     * where the quay charges start - so an approved filing is neither finished nor
     * still being waited on. It is permitted to leave, which is its own state.
     */
    public function test_approved_is_not_the_end_of_it(): void
    {
        $clearance = ShipmentCustomsClearance::factory()->approved()->create();

        $this->assertFalse($clearance->isClosed(), 'Approved was treated as final; it is not.');
        $this->assertFalse($clearance->isHeld(), 'The goods are not in an unresolved queue once approved.');
    }

    public function test_a_rejection_needs_chasing_and_is_not_still_being_waited_on(): void
    {
        $clearance = ShipmentCustomsClearance::factory()->rejected()->create();

        $this->assertTrue($clearance->needsAttention());
        // Refused, so the goods are not sitting in an unresolved queue any more -
        // they have been turned away, which is a different problem.
        $this->assertFalse($clearance->isHeld());
        // And the filing is not expected to change.
        $this->assertFalse($clearance->isClosed());
        $this->assertNotNull($clearance->hold_reason);
    }

    public function test_it_lists_a_filing_with_its_consignment_and_port(): void
    {
        $shipment = Shipment::factory()->create(['reference' => 'OPWT-7711']);
        $port = Port::factory()->create(['un_locode' => 'LKCMB', 'name' => 'Colombo']);

        ShipmentCustomsClearance::factory()->underReview()->atPort($port)->create([
            'shipment_id' => $shipment->id,
        ]);

        $this->actingAsAdmin()
            ->getJson('/api/v1/customs')
            ->assertOk()
            ->assertJsonPath('data.0.shipment.reference', 'OPWT-7711')
            ->assertJsonPath('data.0.port.un_locode', 'LKCMB')
            ->assertJsonPath('data.0.status', 'under_review')
            ->assertJsonPath('data.0.status_label', 'Under review')
            ->assertJsonPath('data.0.is_held', true);
    }

    public function test_a_filing_that_does_not_exist_is_a_404(): void
    {
        $this->actingAsAdmin()->getJson('/api/v1/customs/999999')->assertNotFound();
    }

    public function test_a_filing_belongs_to_a_consignment(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/customs', ['port_id' => Port::factory()->create()->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('shipment_id');
    }

    public function test_it_rejects_an_archived_consignment(): void
    {
        $shipment = Shipment::factory()->create();
        $shipment->delete();

        $this->actingAsAdmin()
            ->postJson('/api/v1/customs', [
                'shipment_id' => $shipment->id,
                'port_id' => Port::factory()->create()->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('shipment_id');
    }

    /**
     * The one case the pair exists for: a transshipment clears at two ports and both
     * have to be on the record.
     */
    public function test_a_consignment_can_clear_at_more_than_one_port(): void
    {
        $shipment = Shipment::factory()->create();
        $singapore = Port::factory()->create();
        $colombo = Port::factory()->create();

        $this->actingAsAdmin()->postJson('/api/v1/customs', [
            'shipment_id' => $shipment->id, 'port_id' => $singapore->id,
        ])->assertCreated();

        $this->actingAsAdmin()->postJson('/api/v1/customs', [
            'shipment_id' => $shipment->id, 'port_id' => $colombo->id,
        ])->assertCreated();

        $this->assertSame(2, $shipment->customsClearances()->count());
    }

    public function test_the_same_port_cannot_be_filed_twice_for_one_consignment(): void
    {
        $shipment = Shipment::factory()->create();
        $port = Port::factory()->create();

        ShipmentCustomsClearance::factory()->atPort($port)->create([
            'shipment_id' => $shipment->id,
        ]);

        $this->actingAsAdmin()
            ->postJson('/api/v1/customs', [
                'shipment_id' => $shipment->id,
                'port_id' => $port->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('port_id');
    }

    /**
     * ...and the same port on two different consignments is fine, which is what makes
     * the index a pair rather than a single column.
     */
    public function test_two_consignments_can_clear_at_the_same_port(): void
    {
        $port = Port::factory()->create();
        $first = Shipment::factory()->create();
        $second = Shipment::factory()->create();

        ShipmentCustomsClearance::factory()->atPort($port)->create(['shipment_id' => $first->id]);

        $this->actingAsAdmin()->postJson('/api/v1/customs', [
            'shipment_id' => $second->id,
            'port_id' => $port->id,
        ])->assertCreated();
    }

    /**
     * A refusal has to stay on the record, so a second filing at the same port is a
     * second row rather than a rewrite. This is the case a unique index on
     * `shipment_id` alone would have made impossible.
     */
    public function test_a_re_presented_consignment_keeps_the_first_attempt_on_record(): void
    {
        $shipment = Shipment::factory()->create();
        $port = Port::factory()->create();

        ShipmentCustomsClearance::factory()->rejected()->atPort($port)->create([
            'shipment_id' => $shipment->id,
        ]);

        // The authority is the same and the consignment is the same, so the pair is
        // genuinely taken. Forcing it past the rule is the only way to express "a
        // corrected filing supersedes nothing", and it has to fail at the database
        // rather than quietly replace the refusal.
        $this->expectException(QueryException::class);

        ShipmentCustomsClearance::query()->create([
            'shipment_id' => $shipment->id,
            'port_id' => $port->id,
            'status' => CustomsStatus::Submitted,
        ]);
    }

    public function test_it_rejects_an_unknown_status(): void
    {
        $clearance = ShipmentCustomsClearance::factory()->create();

        $this->actingAsAdmin()
            ->patchJson('/api/v1/customs/'.$clearance->id, ['status' => 'wishful'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    /**
     * The authority's reference is recorded as it arrives.
     *
     * No case folding and no separator rewriting: whether `1234/2026` and
     * `1234-2026` are one filing is a question for the authority's numbering rules,
     * and guessing at it on the way into the database is how a consignment ends up
     * with two entry numbers for one filing. (Whitespace is the one thing that is
     * folded, by TrimStrings on every request rather than by this module.)
     */
    public function test_an_entry_number_is_recorded_exactly_as_it_arrived(): void
    {
        $clearance = ShipmentCustomsClearance::factory()->create();

        $this->actingAsAdmin()
            ->patchJson('/api/v1/customs/'.$clearance->id, ['entry_number' => '1234/2026-ab'])
            ->assertOk()
            ->assertJsonPath('data.entry_number', '1234/2026-ab');
    }

    /**
     * A filing is assembled from whatever the authority said, and it is usual to
     * know the outcome before knowing the reference number.
     */
    public function test_it_accepts_a_partial_update_that_records_an_outcome(): void
    {
        $clearance = ShipmentCustomsClearance::factory()->submitted()->create();

        $this->actingAsAdmin()
            ->patchJson('/api/v1/customs/'.$clearance->id, [
                'status' => 'completed',
                'cleared_at' => '2026-09-26T14:00:00Z',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.is_closed', true)
            // Everything the broker told us earlier survives.
            ->assertJsonPath('data.entry_number', $clearance->entry_number)
            ->assertJsonPath('data.submitted_at', $clearance->submitted_at->toIso8601String());
    }

    /**
     * A port going out of the lookup table says nothing about whether the clearance
     * happened, so the row keeps its history rather than gaining a hole.
     */
    public function test_a_filing_survives_the_port_being_removed(): void
    {
        $clearance = ShipmentCustomsClearance::factory()->completed()->atPort(
            Port::factory()->create(['un_locode' => 'SGSIN']),
        )->create();

        $port = $clearance->port;
        $port->forceDelete();

        $this->actingAsAdmin()
            ->getJson('/api/v1/customs/'.$clearance->id)
            ->assertOk()
            ->assertJsonPath('data.id', $clearance->id)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.port', null);
    }

    /**
     * The two worklists answer different questions and must not be merged.
     */
    public function test_held_and_needs_attention_are_different_lists(): void
    {
        $shipment = Shipment::factory()->create(['status' => 'in_transit']);
        $port = Port::factory()->create();

        $inQueue = ShipmentCustomsClearance::factory()->submitted()->atPort($port)->create([
            'shipment_id' => $shipment->id,
        ]);
        $refused = ShipmentCustomsClearance::factory()->rejected()->create([
            'shipment_id' => $shipment->id,
        ]);
        $done = ShipmentCustomsClearance::factory()->completed()->create([
            'shipment_id' => $shipment->id,
        ]);

        $held = $this->actingAsAdmin()->getJson('/api/v1/customs?held=1')->assertOk();
        $this->assertSame([$inQueue->id], collect($held->json('data'))->pluck('id')->all());

        // A refusal is a problem, but the goods are not in an unresolved queue, so
        // folding it into `held` would put it on both lists.
        $attention = $this->actingAsAdmin()->getJson('/api/v1/customs?needs_attention=1')->assertOk();
        $this->assertSame([$refused->id], collect($attention->json('data'))->pluck('id')->all());

        $this->assertNotContains($done->id, $held->json('data.*.id'));
        $this->assertNotContains($done->id, $attention->json('data.*.id'));
    }

    /**
     * Every row a worklist returns has to agree with that worklist's own flag.
     *
     * The two are defined separately - the scope answers a query, the method answers
     * for a model in a response - and nothing makes them agree except a test. When
     * they drift, a filtered page hands back rows labelled `needs_attention: false`,
     * which is worse than either definition on its own because the list looks like it
     * is working.
     */
    public function test_the_worklists_return_only_rows_whose_own_flag_agrees(): void
    {
        $shipment = Shipment::factory()->create(['status' => 'in_transit']);
        $port = Port::factory()->create();

        // No state: a new filing arrives pending with nothing on it, which is the
        // state this test needs and the reason the factory has no `pending()` state.
        $unfiled = ShipmentCustomsClearance::factory()->atPort($port)->create([
            'shipment_id' => $shipment->id,
        ]);
        $refused = ShipmentCustomsClearance::factory()->rejected()->create([
            'shipment_id' => $shipment->id,
        ]);

        $attention = $this->actingAsAdmin()->getJson('/api/v1/customs?needs_attention=1')->assertOk();
        $this->assertSame([$refused->id], collect($attention->json('data'))->pluck('id')->all());
        $this->assertTrue($attention->json('data.0.needs_attention'));
        $this->assertNotContains(
            $unfiled->id,
            $attention->json('data.*.id'),
            'an unfiled clearance is stuck rather than refused, and its own flag says so'
        );

        $held = $this->actingAsAdmin()->getJson('/api/v1/customs?held=1')->assertOk();
        $this->assertSame([$unfiled->id], collect($held->json('data'))->pluck('id')->all());
        $this->assertTrue($held->json('data.0.is_held'));
        $this->assertFalse($held->json('data.0.needs_attention'));

        // And the same agreement in the other direction: the refused row is not held,
        // because a refusal is not an unresolved queue.
        $this->assertTrue($refused->needsAttention());
        $this->assertFalse($refused->isHeld());
    }

    /**
     * A cancelled consignment is not waiting for a customs answer.
     */
    public function test_the_worklists_exclude_a_consignment_that_is_no_longer_moving(): void
    {
        $shipment = Shipment::factory()->create(['status' => 'cancelled']);
        ShipmentCustomsClearance::factory()->submitted()->create(['shipment_id' => $shipment->id]);

        $this->actingAsAdmin()->getJson('/api/v1/customs?held=1')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_it_finds_a_filing_by_the_authoritys_entry_number(): void
    {
        ShipmentCustomsClearance::factory()->underReview()->create([
            'entry_number' => '4471/2026',
        ]);

        $this->actingAsAdmin()
            ->getJson('/api/v1/customs?q=4471/2026')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.entry_number', '4471/2026');
    }

    public function test_it_finds_a_filing_by_its_consignment_reference(): void
    {
        $shipment = Shipment::factory()->create(['reference' => 'OPWT-3311']);
        ShipmentCustomsClearance::factory()->create(['shipment_id' => $shipment->id]);

        $this->actingAsAdmin()
            ->getJson('/api/v1/customs?q=OPWT-3311')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_it_filters_to_one_consignment(): void
    {
        $shipment = Shipment::factory()->create();
        ShipmentCustomsClearance::factory()->count(2)->create(['shipment_id' => $shipment->id]);
        ShipmentCustomsClearance::factory()->create();

        $this->actingAsAdmin()
            ->getJson('/api/v1/customs?shipment_id='.$shipment->id)
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_deleting_a_consignment_takes_its_filings_with_it(): void
    {
        $shipment = Shipment::factory()->create();
        $clearance = ShipmentCustomsClearance::factory()->create(['shipment_id' => $shipment->id]);

        $shipment->forceDelete();

        $this->assertDatabaseMissing('shipment_customs_clearances', ['id' => $clearance->id]);
    }
}
