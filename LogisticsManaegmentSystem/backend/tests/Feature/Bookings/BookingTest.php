<?php

namespace Tests\Feature\Bookings;

use App\Enums\BookingStatus;
use App\Enums\PartyType;
use App\Models\Booking;
use App\Models\Carrier;
use App\Models\Party;
use App\Models\Shipment;
use Tests\Feature\MasterData\MasterDataTestCase;

/**
 * The bookings module.
 *
 * Beyond CRUD, what is worth pinning down here is the behaviour that is specific
 * to a booking: the reference is a document number and must normalise before the
 * unique check, the party references have to be the right *kind* of party, the
 * requested_eta rule has to survive a partial update, and `pending` has to mean
 * the requests a carrier has not answered rather than everything not yet
 * confirmed.
 */
class BookingTest extends MasterDataTestCase
{
    public function test_it_requires_a_session(): void
    {
        $this->getJson('/api/v1/bookings')
            ->assertUnauthorized();
    }

    public function test_a_view_can_read_but_not_write(): void
    {
        $this->actingAsRole('viewer');

        $this->getJson('/api/v1/bookings')->assertOk();

        $this->postJson('/api/v1/bookings', ['reference' => 'BKG-1'])
            ->assertForbidden();
    }

    public function test_it_creates_a_booking_and_returns_the_record(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/v1/bookings', [
                'reference' => 'bkg-1042',
                'cargo_description' => 'Frozen seafood',
                'weight_kg' => 12000,
                'volume_cbm' => '28.500',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            // Upper-cased on write, so the reference on the carrier's confirmation
            // and the one on our copy are the same string.
            ->assertJsonPath('data.reference', 'BKG-1042')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.status_label', 'Draft')
            ->assertJsonPath('data.is_closed', false);

        $this->assertDatabaseHas('bookings', [
            'reference' => 'BKG-1042',
            'status' => 'draft',
        ]);

        $this->assertSame(
            '/api/v1/bookings/'.$response->json('data.id'),
            $response->headers->get('Location'),
        );
    }

    /**
     * The reference is the one field a caller is certain to send in a different
     * case from the one already stored - it comes off a carrier's email. If
     * normalisation happened only on save, the unique check would compare the raw
     * payload, pass, and the database index would reject the write with a 500.
     */
    public function test_a_reference_that_differs_only_in_case_still_collides(): void
    {
        Booking::factory()->create(['reference' => 'BKG-2000']);

        $this->actingAsAdmin()
            ->postJson('/api/v1/bookings', ['reference' => '  bkg-2000 '])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reference');
    }

    public function test_a_booking_reference_cannot_be_reused_on_update(): void
    {
        Booking::factory()->create(['reference' => 'BKG-3000']);
        $booking = Booking::factory()->create(['reference' => 'BKG-3001']);

        $this->actingAsAdmin()
            ->patchJson('/api/v1/bookings/'.$booking->id, ['reference' => 'bkg-3000'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reference');

        // But a booking may keep its own reference, or every save would fail.
        $this->actingAsAdmin()
            ->patchJson('/api/v1/bookings/'.$booking->id, ['reference' => $booking->reference])
            ->assertOk();
    }

    public function test_the_shipper_must_be_a_shipper_and_not_merely_a_party(): void
    {
        $customer = Party::factory()->ofType(PartyType::Customer)->create();
        $shipper = Party::factory()->ofType(PartyType::Shipper)->create();

        // A real party id of the wrong kind: `exists:parties,id` alone would
        // accept this, and the shipper dropdown would then render blank.
        $this->actingAsAdmin()
            ->postJson('/api/v1/bookings', [
                'reference' => 'BKG-4000',
                'shipper_id' => $customer->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('shipper_id');

        $this->actingAsAdmin()
            ->postJson('/api/v1/bookings', [
                'reference' => 'BKG-4001',
                'shipper_id' => $shipper->id,
            ])
            ->assertCreated();
    }

    public function test_a_soft_deleted_party_cannot_be_named_as_a_shipper(): void
    {
        $shipper = Party::factory()->ofType(PartyType::Shipper)->create();
        $shipper->delete();

        $this->actingAsAdmin()
            ->postJson('/api/v1/bookings', [
                'reference' => 'BKG-4002',
                'shipper_id' => $shipper->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('shipper_id');
    }

    /**
     * A request is raised before the paperwork exists, so an empty booking has to
     * be saveable. This is the reason every partner column is nullable.
     */
    public function test_every_partner_is_optional_so_a_draft_can_be_started(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/bookings', ['reference' => 'BKG-5000'])
            ->assertCreated()
            ->assertJsonPath('data.carrier', null)
            ->assertJsonPath('data.vessel', null)
            ->assertJsonPath('data.requested_etd', null);
    }

    public function test_a_soft_deleted_carrier_cannot_be_assigned(): void
    {
        $carrier = Carrier::factory()->create();
        $carrier->delete();

        $this->actingAsAdmin()
            ->postJson('/api/v1/bookings', [
                'reference' => 'BKG-5001',
                'carrier_id' => $carrier->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('carrier_id');
    }

    public function test_it_rejects_an_unknown_status(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/bookings', ['reference' => 'BKG-5002', 'status' => 'quoted'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }

    public function test_it_rejects_an_unknown_incoterm(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/bookings', ['reference' => 'BKG-5003', 'incoterm' => 'XYZ'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('incoterm');
    }

    public function test_the_requested_eta_may_not_precede_the_requested_etd(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/bookings', [
                'reference' => 'BKG-6000',
                'requested_etd' => '2026-10-10',
                'requested_eta' => '2026-10-01',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('requested_eta');
    }

    /**
     * A carrier moving the sailing is the single most common edit to a booking,
     * and it is made one date at a time. `after_or_equal:requested_etd` would
     * compare against a field the PATCH did not carry and reject the change.
     */
    public function test_revising_the_requested_eta_alone_does_not_need_the_etd_resent(): void
    {
        $booking = Booking::factory()->create([
            'requested_etd' => '2026-10-10',
            'requested_eta' => '2026-10-30',
        ]);

        $this->actingAsAdmin()
            ->patchJson('/api/v1/bookings/'.$booking->id, ['requested_eta' => '2026-11-05'])
            ->assertOk()
            ->assertJsonPath('data.requested_eta', '2026-11-05');
    }

    public function test_a_requested_eta_is_allowed_when_there_is_no_etd_to_compare_it_to(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/bookings', [
                'reference' => 'BKG-6001',
                'requested_eta' => '2026-10-30',
            ])
            ->assertCreated();
    }

    public function test_it_updates_only_the_fields_that_were_sent(): void
    {
        $booking = Booking::factory()->create([
            'reference' => 'BKG-7000',
            'cargo_description' => 'Textiles',
        ]);

        $this->actingAsAdmin()
            ->patchJson('/api/v1/bookings/'.$booking->id, ['cargo_description' => 'Frozen seafood'])
            ->assertOk()
            ->assertJsonPath('data.cargo_description', 'Frozen seafood')
            // Untouched, not nulled: a PATCH is a change, not a replacement.
            ->assertJsonPath('data.reference', 'BKG-7000');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'reference' => 'BKG-7000',
            'cargo_description' => 'Frozen seafood',
        ]);
    }

    public function test_it_searches_by_reference_and_by_partner_name(): void
    {
        $carrier = Carrier::factory()->create(['name' => 'Meridian Lines']);
        $booking = Booking::factory()->create([
            'reference' => 'BKG-9001',
            'carrier_id' => $carrier->id,
        ]);

        Booking::factory()->create(['reference' => 'BKG-9002']);

        $this->actingAsAdmin()->getJson('/api/v1/bookings?q=BKG-9001')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $booking->id);

        // The relation search: "what has this carrier got committed" is the common
        // question about a booking.
        $this->actingAsAdmin()->getJson('/api/v1/bookings?q=Meridian')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $booking->id);
    }

    /**
     * A reference the booking does not carry must not be narrowed away by the
     * relation search. This is the failure mode of putting the two in separate
     * where groups: the record matches the reference but not any partner name, so
     * an ANDed second group drops it.
     */
    public function test_a_reference_match_survives_the_relation_search(): void
    {
        $booking = Booking::factory()->create(['reference' => 'BKG-9100']);
        Booking::factory()->create(['reference' => 'BKG-9101']);

        $this->actingAsAdmin()->getJson('/api/v1/bookings?q=BKG-9100')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $booking->id);
    }

    /**
     * `open_only` excludes the two closed states and nothing else. Confirmed
     * bookings stay in it, because a confirmed booking is still open work until
     * the shipment it produced has been delivered.
     */
    public function test_it_filters_by_status_and_by_open_only(): void
    {
        $draft = Booking::factory()->draft()->create();
        $requested = Booking::factory()->requested()->create();
        $confirmed = Booking::factory()->confirmed()->create();
        $cancelled = Booking::factory()->cancelled()->create();
        $expired = Booking::factory()->withStatus(BookingStatus::Expired)->create();

        $this->actingAsAdmin()->getJson('/api/v1/bookings?status=requested')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $requested->id);

        $open = $this->actingAsAdmin()
            ->getJson('/api/v1/bookings?open_only=1')
            ->assertOk()
            ->json('data');

        $this->assertSame(
            [$draft->id, $requested->id, $confirmed->id],
            collect($open)->pluck('id')->all(),
        );
    }

    /**
     * `pending` is the follow-up list: sent to the carrier, not yet answered. It
     * must not sweep in the drafts nobody has sent, or the follow-up list is
     * indistinguishable from the backlog.
     */
    public function test_it_lists_the_requests_awaiting_confirmation(): void
    {
        $pending = Booking::factory()->requested()->create();
        Booking::factory()->draft()->create();
        Booking::factory()->confirmed()->create();
        Booking::factory()->cancelled()->create();

        $this->actingAsAdmin()->getJson('/api/v1/bookings?pending=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pending->id);
    }

    public function test_it_lists_only_the_unassigned_ones(): void
    {
        $unassigned = Booking::factory()->unassigned()->create();
        Booking::factory()->confirmed()->create();

        $this->actingAsAdmin()->getJson('/api/v1/bookings?unassigned=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $unassigned->id);
    }

    public function test_it_filters_by_carrier_and_by_requested_etd_window(): void
    {
        $carrier = Carrier::factory()->create();

        Booking::factory()->create([
            'carrier_id' => $carrier->id,
            'requested_etd' => '2026-10-05',
        ]);
        Booking::factory()->create([
            'carrier_id' => $carrier->id,
            'requested_etd' => '2026-11-20',
        ]);
        Booking::factory()->create(['requested_etd' => '2026-10-06']);

        $this->actingAsAdmin()->getJson('/api/v1/bookings?carrier_id='.$carrier->id)
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->actingAsAdmin()->getJson('/api/v1/bookings?requested_etd_from=2026-10-01&requested_etd_to=2026-10-31')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_it_sorts_on_a_whitelisted_column_only(): void
    {
        Booking::factory()->create(['reference' => 'BKG-8002']);
        Booking::factory()->create(['reference' => 'BKG-8001']);

        $this->actingAsAdmin()->getJson('/api/v1/bookings?sort=reference&direction=desc')
            ->assertOk()
            ->assertJsonPath('data.0.reference', 'BKG-8002');

        // An attacker-controlled column name must not reach the query, and an
        // unknown one falls back to id rather than raising a 500.
        $this->actingAsAdmin()->getJson('/api/v1/bookings?sort=drop_table')
            ->assertOk();
    }

    public function test_it_soft_deletes_a_booking(): void
    {
        $booking = Booking::factory()->create();

        $this->actingAsAdmin()
            ->deleteJson('/api/v1/bookings/'.$booking->id)
            ->assertOk()
            ->assertJsonPath('data', null);

        $this->assertSoftDeleted('bookings', ['id' => $booking->id]);

        $this->actingAsAdmin()
            ->getJson('/api/v1/bookings/'.$booking->id)
            ->assertNotFound();
    }

    public function test_it_returns_not_found_for_an_unknown_booking(): void
    {
        $this->actingAsAdmin()
            ->getJson('/api/v1/bookings/999999')
            ->assertNotFound();
    }

    /**
     * A list row and a single record have to carry the same shape. The resource
     * uses whenLoaded everywhere, so if the controller's eager load list and its
     * present() path ever disagree, a client reads a key that is missing after it
     * creates the very same record.
     */
    public function test_a_list_row_and_a_single_record_have_the_same_shape(): void
    {
        $booking = Booking::factory()->create();

        $listed = $this->actingAsAdmin()->getJson('/api/v1/bookings')->assertOk()->json('data.0');
        $single = $this->actingAsAdmin()->getJson('/api/v1/bookings/'.$booking->id)->assertOk()->json('data');
        $created = $this->actingAsAdmin()
            ->postJson('/api/v1/bookings', ['reference' => 'BKG-9999'])
            ->assertCreated()
            ->json('data');

        $keys = fn (array $record): array => array_keys($record);

        $this->assertSame($keys($single), $keys($listed));
        $this->assertSame($keys($single), $keys($created));
    }

    public function test_relations_survive_a_carrier_being_hard_deleted(): void
    {
        $booking = Booking::factory()->create();

        // Hard delete, not the soft delete the API exposes: the nullOnDelete
        // constraint is what stops a row from being left pointing at nothing.
        $booking->carrier->forceDelete();

        $this->actingAsAdmin()
            ->getJson('/api/v1/bookings/'.$booking->id)
            ->assertOk()
            ->assertJsonPath('data.carrier', null);
    }

    /**
     * A booking is a request for a quantity of space, and one large enough to be
     * split is raised as a shipment per sailing. The relationship is one-to-many
     * and that is why the foreign key is on shipments.
     */
    public function test_one_booking_can_produce_several_shipments(): void
    {
        $booking = Booking::factory()->confirmed()->create();

        Shipment::factory()->count(2)->create(['booking_id' => $booking->id]);
        Shipment::factory()->create();

        $this->assertSame(2, $booking->shipments()->count());
        $this->assertTrue($booking->fresh()->hasShipments());
    }

    /**
     * Only a confirmed booking is meant to produce a consignment, and that is
     * advice rather than a rule. A carrier who confirms by phone and has it typed
     * in afterwards leaves a confirmed shipment pointing at a booking still marked
     * requested, and the correction has to be recordable - refusing the link would
     * mean the backdated consignment could not be entered at all.
     */
    public function test_a_shipment_may_be_linked_to_a_booking_that_is_not_confirmed(): void
    {
        $booking = Booking::factory()->requested()->create();

        $this->assertFalse($booking->status->canProduceShipment());

        Shipment::factory()->create(['booking_id' => $booking->id]);

        $this->assertDatabaseHas('shipments', ['booking_id' => $booking->id]);
    }

    /**
     * Cancelled and expired are closed; everything else is still live work. Both
     * halves matter: a dashboard counting outstanding requests and one counting
     * requests lost read the same column.
     */
    public function test_only_cancelled_and_expired_bookings_are_closed(): void
    {
        $this->assertFalse(BookingStatus::Draft->isClosed());
        $this->assertFalse(BookingStatus::Requested->isClosed());
        $this->assertFalse(BookingStatus::Confirmed->isClosed());
        $this->assertTrue(BookingStatus::Cancelled->isClosed());
        $this->assertTrue(BookingStatus::Expired->isClosed());

        $this->assertTrue(BookingStatus::Confirmed->canProduceShipment());
        $this->assertFalse(BookingStatus::Expired->canProduceShipment());
    }
}
