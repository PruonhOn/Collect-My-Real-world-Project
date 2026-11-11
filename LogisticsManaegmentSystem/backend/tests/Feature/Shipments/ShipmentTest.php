<?php

namespace Tests\Feature\Shipments;

use App\Enums\PartyType;
use App\Enums\ShipmentStatus;
use App\Models\Booking;
use App\Models\Carrier;
use App\Models\Party;
use App\Models\Shipment;
use Tests\Feature\MasterData\MasterDataTestCase;

/**
 * The shipments module.
 *
 * Beyond CRUD, what is worth pinning down here is the behaviour that is specific
 * to a consignment: the reference is a document number and must normalise before
 * the unique check, the party references have to be the right *kind* of party,
 * the ETA rule has to survive a partial update, and the derived late flag must
 * not light up on a shipment that was never planned.
 */
class ShipmentTest extends MasterDataTestCase
{
    public function test_it_requires_a_session(): void
    {
        $this->getJson('/api/v1/shipments')
            ->assertUnauthorized();
    }

    public function test_a_view_can_read_but_not_write(): void
    {
        $this->actingAsRole('viewer');

        $this->getJson('/api/v1/shipments')->assertOk();

        $this->postJson('/api/v1/shipments', ['reference' => 'SHP-1'])
            ->assertForbidden();
    }

    public function test_it_creates_a_shipment_and_returns_the_record(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/v1/shipments', [
                'reference' => 'shp-1042',
                'cargo_description' => 'Frozen seafood',
                'weight_kg' => 12000,
                'volume_cbm' => '28.500',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            // Upper-cased on write, so the reference on the booking and the one on
            // the invoice are the same string.
            ->assertJsonPath('data.reference', 'SHP-1042')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.status_label', 'Draft')
            ->assertJsonPath('data.is_closed', false)
            ->assertJsonPath('data.is_late', false);

        $this->assertDatabaseHas('shipments', [
            'reference' => 'SHP-1042',
            'status' => 'draft',
        ]);

        $id = $response->json('data.id');

        $this->assertSame(
            '/api/v1/shipments/'.$id,
            $response->headers->get('Location'),
        );
    }

    /**
     * The reference is the one field a caller is certain to send in a different
     * case from the one already stored. If normalisation happened only on save,
     * the unique check would compare the raw payload, pass, and the database
     * index would reject the write with a 500.
     */
    public function test_a_reference_that_differs_only_in_case_still_collides(): void
    {
        Shipment::factory()->create(['reference' => 'SHP-2000']);

        $this->actingAsAdmin()
            ->postJson('/api/v1/shipments', ['reference' => '  shp-2000 '])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reference');
    }

    public function test_a_shipment_reference_cannot_be_reused_on_update(): void
    {
        Shipment::factory()->create(['reference' => 'SHP-3000']);
        $shipment = Shipment::factory()->create(['reference' => 'SHP-3001']);

        $this->actingAsAdmin()
            ->patchJson('/api/v1/shipments/'.$shipment->id, ['reference' => 'shp-3000'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reference');

        // But a shipment may keep its own reference, or every save would fail.
        $this->actingAsAdmin()
            ->patchJson('/api/v1/shipments/'.$shipment->id, ['reference' => $shipment->reference])
            ->assertOk();
    }

    public function test_the_shipper_must_be_a_shipper_and_not_merely_a_party(): void
    {
        $customer = Party::factory()->ofType(PartyType::Customer)->create();
        $shipper = Party::factory()->ofType(PartyType::Shipper)->create();

        // A real party id of the wrong kind: `exists:parties,id` alone would
        // accept this, and the shipper dropdown would then render blank.
        $this->actingAsAdmin()
            ->postJson('/api/v1/shipments', [
                'reference' => 'SHP-4000',
                'shipper_id' => $customer->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('shipper_id');

        $this->actingAsAdmin()
            ->postJson('/api/v1/shipments', [
                'reference' => 'SHP-4001',
                'shipper_id' => $shipper->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.shipper.id', $shipper->id)
            ->assertJsonPath('data.shipper.name', $shipper->name);
    }

    public function test_the_consignee_must_be_a_consignee(): void
    {
        $shipper = Party::factory()->ofType(PartyType::Shipper)->create();

        $this->actingAsAdmin()
            ->postJson('/api/v1/shipments', [
                'reference' => 'SHP-4100',
                'consignee_id' => $shipper->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('consignee_id');
    }

    public function test_a_soft_deleted_party_cannot_be_named_as_a_shipper(): void
    {
        $shipper = Party::factory()->ofType(PartyType::Shipper)->create();
        $shipper->delete();

        $this->actingAsAdmin()
            ->postJson('/api/v1/shipments', [
                'reference' => 'SHP-4200',
                'shipper_id' => $shipper->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('shipper_id');
    }

    public function test_every_partner_is_optional_so_a_draft_can_be_started(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/shipments', ['reference' => 'SHP-5000'])
            ->assertCreated()
            ->assertJsonPath('data.carrier', null)
            ->assertJsonPath('data.shipper', null)
            ->assertJsonPath('data.etd', null);
    }

    public function test_a_soft_deleted_carrier_cannot_be_assigned(): void
    {
        $carrier = Carrier::factory()->create();
        $carrier->delete();

        $this->actingAsAdmin()
            ->postJson('/api/v1/shipments', [
                'reference' => 'SHP-5100',
                'carrier_id' => $carrier->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('carrier_id');
    }

    public function test_it_rejects_an_unknown_status(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/shipments', [
                'reference' => 'SHP-6000',
                'status' => 'teleported',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }

    public function test_it_rejects_an_unknown_incoterm(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/shipments', [
                'reference' => 'SHP-6100',
                'incoterm' => 'MAYBE',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('incoterm');
    }

    public function test_the_eta_may_not_precede_the_etd(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/shipments', [
                'reference' => 'SHP-7000',
                'etd' => '2026-11-20',
                'eta' => '2026-11-01',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('eta');
    }

    /**
     * The regression this pins down: `after_or_equal:etd` compares against the
     * request payload, so revising only the ETA would fail because the PATCH did
     * not carry the ETD. The stored value has to be used instead.
     */
    public function test_revising_the_eta_alone_does_not_need_the_etd_resent(): void
    {
        $shipment = Shipment::factory()->create([
            'etd' => '2026-10-01',
            'eta' => '2026-10-15',
        ]);

        $this->actingAsAdmin()
            ->patchJson('/api/v1/shipments/'.$shipment->id, ['eta' => '2026-09-20'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('eta');

        $this->actingAsAdmin()
            ->patchJson('/api/v1/shipments/'.$shipment->id, ['eta' => '2026-10-25'])
            ->assertOk()
            ->assertJsonPath('data.eta', '2026-10-25')
            // And the ETD was not touched by a partial update.
            ->assertJsonPath('data.etd', '2026-10-01');
    }

    public function test_an_eta_is_allowed_when_there_is_no_etd_to_compare_it_to(): void
    {
        // A revision on the water: the departure is not known, only the arrival.
        // Not a contradiction, so not an error.
        $this->actingAsAdmin()
            ->postJson('/api/v1/shipments', [
                'reference' => 'SHP-7100',
                'eta' => '2026-12-01',
            ])
            ->assertCreated()
            ->assertJsonPath('data.eta', '2026-12-01');
    }

    public function test_it_updates_only_the_fields_that_were_sent(): void
    {
        $shipment = Shipment::factory()->create([
            'reference' => 'SHP-8000',
            'cargo_description' => 'Textiles',
            'notes' => 'Keep dry',
        ]);

        $this->actingAsAdmin()
            ->patchJson('/api/v1/shipments/'.$shipment->id, ['cargo_description' => 'Auto parts'])
            ->assertOk()
            ->assertJsonPath('data.cargo_description', 'Auto parts')
            ->assertJsonPath('data.reference', 'SHP-8000')
            ->assertJsonPath('data.notes', 'Keep dry');
    }

    public function test_a_reference_can_be_cleared_by_omitting_it_but_not_by_nulling_it(): void
    {
        $shipment = Shipment::factory()->create(['reference' => 'SHP-8100']);

        $this->actingAsAdmin()
            ->patchJson('/api/v1/shipments/'.$shipment->id, ['reference' => null])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reference');
    }

    /**
     * `is_late` is derived, so it has to be right on every path that returns a
     * shipment. A draft with no ETA is unplanned, not late, and treating it as an
     * overrun would light up every unfinished record on a screen filtered for
     * problems.
     */
    public function test_the_late_flag_only_applies_to_an_open_shipment_past_its_eta(): void
    {
        $overdue = Shipment::factory()->overdue()->create();

        $this->actingAsAdmin()
            ->getJson('/api/v1/shipments/'.$overdue->id)
            ->assertOk()
            ->assertJsonPath('data.is_late', true);

        $delivered = Shipment::factory()->overdue()->delivered()->create();

        $this->actingAsAdmin()
            ->getJson('/api/v1/shipments/'.$delivered->id)
            ->assertOk()
            // Arrived three weeks ago, so the ETA is past - but it is closed, so
            // flagging it would bury the consignment that is actually late.
            ->assertJsonPath('data.status', 'delivered')
            ->assertJsonPath('data.is_closed', true)
            ->assertJsonPath('data.is_late', false);

        $unplanned = Shipment::factory()->unassigned()->create();

        $this->actingAsAdmin()
            ->getJson('/api/v1/shipments/'.$unplanned->id)
            ->assertOk()
            ->assertJsonPath('data.is_late', false);
    }

    public function test_it_searches_by_reference_and_by_partner_name(): void
    {
        $carrier = Carrier::factory()->create(['name' => 'Meridian Lines']);
        $shipment = Shipment::factory()->create([
            'reference' => 'SHP-9001',
            'carrier_id' => $carrier->id,
        ]);

        Shipment::factory()->create(['reference' => 'SHP-9002']);

        $this->actingAsAdmin()->getJson('/api/v1/shipments?q=SHP-9001')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $shipment->id);

        // The relation search, which is the reason `q` is not just the column
        // list: "what has this carrier got on the water" is the common question.
        $this->actingAsAdmin()->getJson('/api/v1/shipments?q=Meridian')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $shipment->id);
    }

    /**
     * A reference the shipment does not carry must not be narrowed away by the
     * relation search. This is the failure mode of putting the two in separate
     * where groups: the record matches the reference but not any partner name, so
     * an ANDed second group drops it and searching for a known consignment
     * returns nothing.
     */
    public function test_a_reference_match_survives_the_relation_search(): void
    {
        $shipment = Shipment::factory()->create(['reference' => 'SHP-9100']);
        Shipment::factory()->create(['reference' => 'SHP-9101']);

        $this->actingAsAdmin()->getJson('/api/v1/shipments?q=SHP-9100')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $shipment->id);
    }

    public function test_it_searches_a_party_by_code_as_well_as_by_name(): void
    {
        $shipper = Party::factory()->ofType(PartyType::Shipper)->create([
            'code' => 'SHPR-UNIQUE',
            'name' => 'Ridgeline Produce',
        ]);

        $shipment = Shipment::factory()->create(['shipper_id' => $shipper->id]);
        Shipment::factory()->create();

        // The screen shows the code in a column and the name in another, so a
        // search that matched only one of them would look broken in half the rows.
        $this->actingAsAdmin()->getJson('/api/v1/shipments?q=SHPR-UNIQUE')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $shipment->id);

        $this->actingAsAdmin()->getJson('/api/v1/shipments?q=Ridgeline')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $shipment->id);
    }

    public function test_it_filters_by_status_and_by_open_only(): void
    {
        $booked = Shipment::factory()->withStatus(ShipmentStatus::Booked)->create();
        $done = Shipment::factory()->delivered()->create();

        $this->actingAsAdmin()->getJson('/api/v1/shipments?status=booked')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $booked->id);

        // The counterpart, so a screen can ask for "still moving" without
        // enumerating the statuses that count as open.
        $this->actingAsAdmin()->getJson('/api/v1/shipments?open_only=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $booked->id)
            // Matched on the reference rather than the id: every row nests its
            // carrier and consignee as objects that carry an id of their own, so
            // an absence check on `id` happily matches a party and passes for the
            // wrong reason.
            ->assertJsonMissing(['reference' => $done->reference]);
    }

    public function test_it_lists_only_the_late_ones(): void
    {
        $late = Shipment::factory()->overdue()->create();
        Shipment::factory()->create();

        $this->actingAsAdmin()->getJson('/api/v1/shipments?late=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $late->id);
    }

    public function test_it_lists_only_the_unassigned_ones(): void
    {
        $unassigned = Shipment::factory()->unassigned()->create();
        Shipment::factory()->create();

        $this->actingAsAdmin()->getJson('/api/v1/shipments?unassigned=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $unassigned->id);
    }

    public function test_it_filters_by_carrier_and_by_etd_window(): void
    {
        $carrier = Carrier::factory()->create();
        $shipment = Shipment::factory()->create([
            'carrier_id' => $carrier->id,
            'etd' => '2026-10-15',
        ]);

        Shipment::factory()->create(['etd' => '2026-12-01']);

        $this->actingAsAdmin()
            ->getJson('/api/v1/shipments?carrier_id='.$carrier->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $shipment->id);

        $this->actingAsAdmin()
            ->getJson('/api/v1/shipments?etd_from=2026-10-01&etd_to=2026-10-31')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $shipment->id);
    }

    public function test_it_sorts_on_a_whitelisted_column_only(): void
    {
        Shipment::factory()->create(['reference' => 'SHP-AAA']);
        Shipment::factory()->create(['reference' => 'SHP-ZZZ']);

        $this->actingAsAdmin()->getJson('/api/v1/shipments?sort=reference&direction=desc')
            ->assertOk()
            ->assertJsonPath('data.0.reference', 'SHP-ZZZ');

        // Not in the whitelist, so it falls back to id rather than becoming a 500
        // or, worse, a way to sort on a column the caller guessed.
        $this->actingAsAdmin()->getJson('/api/v1/shipments?sort=password')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_it_soft_deletes_a_shipment(): void
    {
        $shipment = Shipment::factory()->create();

        $this->actingAsAdmin()
            ->deleteJson('/api/v1/shipments/'.$shipment->id)
            ->assertOk()
            ->assertJsonPath('success', true);

        // Still in the table, gone from the API: a consignment that was cancelled
        // is evidence, and the reference must not be re-usable.
        $this->assertSoftDeleted('shipments', ['id' => $shipment->id]);

        $this->actingAsAdmin()
            ->getJson('/api/v1/shipments')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->actingAsAdmin()
            ->getJson('/api/v1/shipments/'.$shipment->id)
            ->assertNotFound();
    }

    public function test_it_returns_not_found_for_an_unknown_shipment(): void
    {
        $this->actingAsAdmin()->getJson('/api/v1/shipments/999999')->assertNotFound();
        $this->actingAsAdmin()->deleteJson('/api/v1/shipments/999999')->assertNotFound();
    }

    /**
     * A list row and a single record have to carry the same shape. The resource
     * uses whenLoaded everywhere, so if the controller's eager load list and its
     * present() path ever disagree, a client reads a key that is missing after it
     * creates the very same record.
     */
    public function test_a_list_row_and_a_single_record_have_the_same_shape(): void
    {
        $shipment = Shipment::factory()->create();

        $listed = $this->actingAsAdmin()->getJson('/api/v1/shipments')->assertOk()->json('data.0');
        $single = $this->actingAsAdmin()->getJson('/api/v1/shipments/'.$shipment->id)->assertOk()->json('data');
        $created = $this->actingAsAdmin()
            ->postJson('/api/v1/shipments', ['reference' => 'SHP-9999'])
            ->assertCreated()
            ->json('data');

        $keys = fn (array $record): array => array_keys($record);

        $this->assertSame($keys($single), $keys($listed));
        $this->assertSame($keys($single), $keys($created));
    }

    public function test_relations_survive_a_carrier_being_hard_deleted(): void
    {
        $shipment = Shipment::factory()->create();

        // Hard delete, not the soft delete the API exposes: the nullOnDelete
        // constraint is what stops a row from being left pointing at nothing.
        $shipment->carrier->forceDelete();

        $this->actingAsAdmin()
            ->getJson('/api/v1/shipments/'.$shipment->id)
            ->assertOk()
            ->assertJsonPath('data.carrier', null);
    }

    /**
     * The booking link is optional, and that is the common case: a consignment
     * entered from a paper bill of lading, or one where the operator already held
     * the space, has no booking behind it. A shipment with no `booking` key at all
     * is the correct response, and is what makes the column honest.
     */
    public function test_a_shipment_can_be_raised_without_a_booking(): void
    {
        Shipment::factory()->create(['reference' => 'SHP-NB-1', 'booking_id' => null]);

        $this->actingAsAdmin()
            ->getJson('/api/v1/shipments?q=SHP-NB-1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.booking', null);
    }

    public function test_a_shipment_can_be_linked_to_a_booking(): void
    {
        $booking = Booking::factory()->confirmed()->create(['reference' => 'BKG-77']);

        $this->actingAsAdmin()
            ->postJson('/api/v1/shipments', [
                'reference' => 'SHP-LINK-1',
                'booking_id' => $booking->id,
            ])
            ->assertCreated()
            // Reference and status only: the link says which request this came
            // from, and the booking's own screen holds its routing and cargo.
            ->assertJsonPath('data.booking.id', $booking->id)
            ->assertJsonPath('data.booking.reference', 'BKG-77')
            ->assertJsonPath('data.booking.status', 'confirmed');

        $this->assertDatabaseHas('shipments', [
            'reference' => 'SHP-LINK-1',
            'booking_id' => $booking->id,
        ]);
    }

    public function test_the_booking_link_can_be_set_and_cleared_on_update(): void
    {
        $booking = Booking::factory()->create();
        $shipment = Shipment::factory()->create(['booking_id' => null]);

        $this->actingAsAdmin()
            ->patchJson('/api/v1/shipments/'.$shipment->id, ['booking_id' => $booking->id])
            ->assertOk()
            ->assertJsonPath('data.booking.id', $booking->id);

        $this->actingAsAdmin()
            ->patchJson('/api/v1/shipments/'.$shipment->id, ['booking_id' => null])
            ->assertOk()
            ->assertJsonPath('data.booking', null);
    }

    public function test_a_soft_deleted_booking_cannot_be_linked(): void
    {
        $booking = Booking::factory()->create();
        $booking->delete();

        $this->actingAsAdmin()
            ->postJson('/api/v1/shipments', [
                'reference' => 'SHP-LINK-2',
                'booking_id' => $booking->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('booking_id');
    }

    /**
     * A shipment must not vanish because the booking it came from was tidied away.
     * The link is dropped and the consignment stays exactly as it was, which is
     * the truth after the parent is gone.
     */
    public function test_a_shipment_survives_its_booking_being_hard_deleted(): void
    {
        $booking = Booking::factory()->create();
        $shipment = Shipment::factory()->create(['booking_id' => $booking->id]);

        // Hard delete, not the soft delete the API exposes: the nullOnDelete
        // constraint is what is under test.
        $booking->forceDelete();

        $this->actingAsAdmin()
            ->getJson('/api/v1/shipments/'.$shipment->id)
            ->assertOk()
            ->assertJsonPath('data.reference', $shipment->reference)
            ->assertJsonPath('data.booking', null);

        $this->assertDatabaseHas('shipments', [
            'id' => $shipment->id,
            'booking_id' => null,
        ]);
    }

    /**
     * "What did we ship under BKG-2026-0001" is asked with the booking reference
     * in hand, so `q` has to reach through the link.
     */
    public function test_it_searches_by_the_booking_reference(): void
    {
        $booking = Booking::factory()->create(['reference' => 'BKG-SEARCH-1']);
        $linked = Shipment::factory()->create(['booking_id' => $booking->id]);
        Shipment::factory()->create();

        $this->actingAsAdmin()->getJson('/api/v1/shipments?q=BKG-SEARCH-1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $linked->id);
    }

    public function test_it_filters_by_booking(): void
    {
        $booking = Booking::factory()->create();
        $linked = Shipment::factory()->create(['booking_id' => $booking->id]);
        Shipment::factory()->create();

        $this->actingAsAdmin()->getJson('/api/v1/shipments?booking_id='.$booking->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $linked->id);
    }
}
