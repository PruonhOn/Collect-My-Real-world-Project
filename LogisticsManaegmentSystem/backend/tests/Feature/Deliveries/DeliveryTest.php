<?php

namespace Tests\Feature\Deliveries;

use App\Enums\DeliveryStatus;
use App\Models\Driver;
use App\Models\Shipment;
use App\Models\ShipmentDelivery;
use App\Models\Truck;
use Tests\Feature\MasterData\MasterDataTestCase;

/**
 * The delivery module: the last leg, from the quay to the door.
 *
 * CRUD is inherited. What is worth pinning down is the one-to-many shape and the
 * three states that are routinely conflated:
 *
 * - a consignment can have several deliveries, and a failed attempt must not be
 *   overwritten by its retry
 * - a failed attempt is not finished - the cargo is still in the vehicle
 * - cleared on time is not the same as not yet late, and a delivery with no slot at
 *   all has not missed anything
 */
class DeliveryTest extends MasterDataTestCase
{
    public function test_it_requires_a_session(): void
    {
        $this->getJson('/api/v1/deliveries')->assertUnauthorized();
    }

    public function test_a_viewer_can_read_but_not_write(): void
    {
        $delivery = ShipmentDelivery::factory()->scheduled()->create();

        $this->actingAsRole('viewer');
        $this->getJson('/api/v1/deliveries')->assertOk();

        $this->patchJson('/api/v1/deliveries/'.$delivery->id, ['status' => 'in_transit'])
            ->assertForbidden();
    }

    public function test_a_clerk_can_book_a_delivery_but_not_cancel_one(): void
    {
        $shipment = Shipment::factory()->create();

        $this->actingAsRole('operations-clerk')
            ->postJson('/api/v1/deliveries', [
                'shipment_id' => $shipment->id,
                'reference' => 'dlv-2026-000451',
                'status' => 'scheduled',
            ])
            ->assertCreated()
            // Upper-cased on the way in, so the job sheet and the manual entry that
            // quotes the same number are one record.
            ->assertJsonPath('data.reference', 'DLV-2026-000451')
            ->assertJsonPath('data.status_label', 'Scheduled');

        $this->actingAsRole('operations-clerk')
            ->deleteJson('/api/v1/deliveries/'.ShipmentDelivery::query()->firstOrFail()->id)
            ->assertForbidden();
    }

    public function test_a_delivery_starts_scheduled_with_nothing_on_it(): void
    {
        $delivery = ShipmentDelivery::factory()->create();

        $this->assertSame(DeliveryStatus::Scheduled, $delivery->status);
        $this->assertTrue($delivery->isOutstanding());
        $this->assertFalse($delivery->isClosed());
        $this->assertNull($delivery->delivered_at);
    }

    /**
     * The one-to-many case. A consignment is routinely delivered in parts, and
     * collapsing the attempts onto one row would lose the reason for the retry.
     */
    public function test_a_consignment_can_have_several_deliveries(): void
    {
        $shipment = Shipment::factory()->create();

        $first = ShipmentDelivery::factory()->failed()->create(['shipment_id' => $shipment->id]);
        $second = ShipmentDelivery::factory()->delivered()->create(['shipment_id' => $shipment->id]);

        $this->assertSame(2, $shipment->deliveries()->count());

        // Both are still on the record, and the failure is the earlier one.
        $this->assertDatabaseHas('shipment_deliveries', [
            'id' => $first->id,
            'status' => 'failed',
            'failure_reason' => $first->failure_reason,
        ]);
        $this->assertDatabaseHas('shipment_deliveries', [
            'id' => $second->id,
            'status' => 'delivered',
        ]);
    }

    public function test_two_deliveries_cannot_share_a_reference(): void
    {
        $shipment = Shipment::factory()->create();
        ShipmentDelivery::factory()->create([
            'shipment_id' => $shipment->id,
            'reference' => 'DLV-2026-000999',
        ]);

        $this->actingAsAdmin()
            ->postJson('/api/v1/deliveries', [
                'shipment_id' => Shipment::factory()->create()->id,
                'reference' => 'DLV-2026-000999',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reference');
    }

    /**
     * A failed attempt is emphatically not the end: the cargo is still in the
     * vehicle and somebody has to go back and try again.
     */
    public function test_a_failed_attempt_is_neither_closed_nor_finished(): void
    {
        $delivery = ShipmentDelivery::factory()->failed()->create();

        $this->assertTrue($delivery->hasFailed());
        $this->assertFalse($delivery->isClosed());
        $this->assertTrue($delivery->isOutstanding());
        $this->assertNotNull($delivery->failure_reason);
        // The collection happened and the handover did not. Collapsing the two would
        // make a failed drop indistinguishable from a slow success.
        $this->assertNotNull($delivery->picked_up_at);
        $this->assertNull($delivery->delivered_at);
    }

    public function test_a_cancelled_delivery_is_over(): void
    {
        $delivery = ShipmentDelivery::factory()->cancelled()->create();

        $this->assertTrue($delivery->isClosed());
        $this->assertFalse($delivery->isOutstanding());
    }

    public function test_it_records_a_handover(): void
    {
        $delivery = ShipmentDelivery::factory()->inTransit()->create();

        $this->actingAsAdmin()
            ->patchJson('/api/v1/deliveries/'.$delivery->id, [
                'status' => 'delivered',
                'delivered_at' => '2026-09-28T11:20:00Z',
                'pod_reference' => 'POD-88213',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'delivered')
            ->assertJsonPath('data.status_label', 'Delivered')
            ->assertJsonPath('data.pod_reference', 'POD-88213')
            ->assertJsonPath('data.is_closed', true);
    }

    public function test_it_records_a_failed_attempt_with_its_reason(): void
    {
        $delivery = ShipmentDelivery::factory()->inTransit()->create();

        $this->actingAsAdmin()
            ->patchJson('/api/v1/deliveries/'.$delivery->id, [
                'status' => 'failed',
                'failure_reason' => 'Nobody available to sign',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'failed')
            ->assertJsonPath('data.has_failed', true)
            ->assertJsonPath('data.failure_reason', 'Nobody available to sign');
    }

    /**
     * A slot has passed and the cargo has not been handed over. This is the
     * worklist, and the rule is deliberately not stored so a rescheduled slot cannot
     * leave a row disagreeing with itself.
     */
    public function test_it_flags_a_delivery_whose_slot_has_passed(): void
    {
        $delivery = ShipmentDelivery::factory()->overdue()->create();

        $this->assertTrue($delivery->isOverdue());

        $this->actingAsAdmin()
            ->getJson('/api/v1/deliveries/'.$delivery->id)
            ->assertOk()
            ->assertJsonPath('data.is_overdue', true)
            ->assertJsonPath('data.is_outstanding', true);
    }

    public function test_it_does_not_flag_a_delivery_that_is_on_time(): void
    {
        $delivery = ShipmentDelivery::factory()->scheduled()->create();

        $this->assertFalse($delivery->isOverdue());
        $this->assertTrue($delivery->isOutstanding());
    }

    /**
     * Nothing has been missed by a booking with no time on it. Treating it as late
     * before it has a slot is how a worklist fills with rows that were never late.
     */
    public function test_a_delivery_with_no_slot_has_not_missed_anything(): void
    {
        $delivery = ShipmentDelivery::factory()->create(['scheduled_at' => null]);

        $this->assertFalse($delivery->isOverdue());

        $this->actingAsAdmin()
            ->getJson('/api/v1/deliveries/'.$delivery->id)
            ->assertOk()
            ->assertJsonPath('data.is_overdue', false);
    }

    /**
     * A completed delivery is never overdue, however far in the past it finished.
     */
    public function test_a_delivered_leg_is_not_overdue_however_old_its_slot(): void
    {
        $delivery = ShipmentDelivery::factory()->delivered()->create([
            'scheduled_at' => now()->subMonth(),
        ]);

        $this->assertFalse($delivery->isOverdue());
        $this->assertFalse($delivery->isOutstanding());
    }

    /**
     * A failed attempt is outstanding but not overdue, and the two flags disagreeing
     * is the design rather than an oversight.
     *
     * The slot came due and the driver attended; the row needs a new slot booked, not
     * chasing. Left on the late list it would sit there indefinitely, burying the rows
     * where nobody turned up at all - which is the only thing that list is for.
     */
    public function test_a_failed_attempt_is_outstanding_but_not_overdue(): void
    {
        $delivery = ShipmentDelivery::factory()->failed()->create([
            'scheduled_at' => now()->subMonth(),
        ]);

        $this->assertTrue($delivery->hasFailed());
        $this->assertTrue($delivery->isOutstanding());
        $this->assertFalse($delivery->isOverdue());

        $this->actingAsAdmin()
            ->getJson('/api/v1/deliveries/'.$delivery->id)
            ->assertOk()
            ->assertJsonPath('data.is_outstanding', true)
            ->assertJsonPath('data.is_overdue', false);

        $this->assertNotContains(
            $delivery->id,
            $this->actingAsAdmin()
                ->getJson('/api/v1/deliveries?overdue=1')
                ->assertOk()
                ->json('data.*.id')
        );
    }

    /**
     * The alarm list, and the flag each row carries has to agree with it.
     */
    public function test_the_overdue_list_returns_exactly_the_rows_that_are_flagged(): void
    {
        $late = ShipmentDelivery::factory()->overdue()->create();
        $onTime = ShipmentDelivery::factory()->scheduled()->create();
        $done = ShipmentDelivery::factory()->delivered()->create([
            'scheduled_at' => now()->subMonth(),
        ]);

        $response = $this->actingAsAdmin()
            ->getJson('/api/v1/deliveries?overdue=1')
            ->assertOk();

        $this->assertSame([$late->id], collect($response->json('data'))->pluck('id')->all());
        $this->assertNotContains($onTime->id, $response->json('data.*.id'));
        $this->assertNotContains($done->id, $response->json('data.*.id'));
        $this->assertTrue($response->json('data.0.is_overdue'));
    }

    /**
     * The forward-looking list, and the reason it includes a failed attempt.
     */
    public function test_the_outstanding_list_includes_a_failed_attempt(): void
    {
        $failed = ShipmentDelivery::factory()->failed()->create();
        $delivered = ShipmentDelivery::factory()->delivered()->create();
        $cancelled = ShipmentDelivery::factory()->cancelled()->create();

        $response = $this->actingAsAdmin()
            ->getJson('/api/v1/deliveries?outstanding=1')
            ->assertOk();

        $this->assertContains($failed->id, $response->json('data.*.id'));
        $this->assertNotContains($delivered->id, $response->json('data.*.id'));
        $this->assertNotContains($cancelled->id, $response->json('data.*.id'));
    }

    /**
     * The open list keys on the delivery's own status, but an archived consignment's
     * deliveries are not rows pointing at something the operator can open.
     *
     * The narrow case that fixes: a delivery still `scheduled` under a consignment
     * that was soft-deleted. Its own status says it is on the books, and the
     * relation says the consignment is gone.
     */
    public function test_an_archived_consignment_leaves_no_orphan_row_on_the_open_list(): void
    {
        $shipment = Shipment::factory()->create(['status' => 'in_transit']);
        $delivery = ShipmentDelivery::factory()->scheduled()->create([
            'shipment_id' => $shipment->id,
        ]);

        $open = ShipmentDelivery::open()->pluck('id');
        $this->assertContains($delivery->id, $open);

        $shipment->delete();

        $this->assertNotContains($delivery->id, ShipmentDelivery::open()->pluck('id'));
    }

    /**
     * And the consignment's status does not close the delivery.
     *
     * The other half of the same decision, and the one that would have been a
     * `whereHas('shipment', open())`: a consignment marked `delivered` while this
     * row still says `scheduled` is a disagreement between two screens, and hiding
     * the row would leave nobody able to see it.
     */
    public function test_a_delivered_consignment_does_not_close_its_scheduled_delivery(): void
    {
        $shipment = Shipment::factory()->create(['status' => 'delivered']);
        $delivery = ShipmentDelivery::factory()->scheduled()->create([
            'shipment_id' => $shipment->id,
        ]);

        $this->assertContains($delivery->id, ShipmentDelivery::open()->pluck('id'));
    }

    public function test_it_lists_a_delivery_with_its_consignment_and_vehicle(): void
    {
        $shipment = Shipment::factory()->create(['reference' => 'OPWT-2288']);
        $truck = Truck::factory()->create(['plate_number' => 'SXY1234A']);
        $driver = Driver::factory()->create(['name' => 'Wei Ling Tan']);

        ShipmentDelivery::factory()->inTransit()->create([
            'shipment_id' => $shipment->id,
            'truck_id' => $truck->id,
            'driver_id' => $driver->id,
        ]);

        $this->actingAsAdmin()
            ->getJson('/api/v1/deliveries')
            ->assertOk()
            ->assertJsonPath('data.0.shipment.reference', 'OPWT-2288')
            ->assertJsonPath('data.0.truck.plate_number', 'SXY1234A')
            ->assertJsonPath('data.0.driver.name', 'Wei Ling Tan')
            ->assertJsonPath('data.0.status', 'in_transit')
            ->assertJsonPath('data.0.is_moving', true);
    }

    /**
     * A vehicle can be booked for before it is assigned, and it can leave the fleet
     * afterwards. Neither should blank out a delivery that happened.
     */
    public function test_a_delivery_survives_its_vehicle_leaving_the_fleet(): void
    {
        $delivery = ShipmentDelivery::factory()->delivered()->create([
            'truck_id' => Truck::factory()->create()->id,
        ]);

        $delivery->truck->forceDelete();

        $this->actingAsAdmin()
            ->getJson('/api/v1/deliveries/'.$delivery->id)
            ->assertOk()
            ->assertJsonPath('data.status', 'delivered')
            ->assertJsonPath('data.truck', null);
    }

    public function test_a_delivery_that_does_not_exist_is_a_404(): void
    {
        $this->actingAsAdmin()->getJson('/api/v1/deliveries/999999')->assertNotFound();
    }

    public function test_a_delivery_belongs_to_a_consignment(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/deliveries', ['reference' => 'DLV-2026-000001'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('shipment_id');
    }

    public function test_it_rejects_an_unknown_status(): void
    {
        $delivery = ShipmentDelivery::factory()->create();

        $this->actingAsAdmin()
            ->patchJson('/api/v1/deliveries/'.$delivery->id, ['status' => 'teleported'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_it_rejects_an_archived_consignment(): void
    {
        $shipment = Shipment::factory()->create();
        $shipment->delete();

        $this->actingAsAdmin()
            ->postJson('/api/v1/deliveries', [
                'shipment_id' => $shipment->id,
                'reference' => 'DLV-2026-000002',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('shipment_id');
    }

    /**
     * A correction of one date has to be possible on its own. The obvious
     * `after_or_equal` comparisons would compare against fields the PATCH did not
     * carry, so a driver who can say when the cargo was collected but not when it was
     * dropped off could not record either.
     */
    public function test_the_three_timestamps_are_independent_on_a_partial_update(): void
    {
        $delivery = ShipmentDelivery::factory()->scheduled()->create();

        $this->actingAsAdmin()
            ->patchJson('/api/v1/deliveries/'.$delivery->id, [
                'delivered_at' => '2026-09-30T09:00:00Z',
            ])
            ->assertOk()
            ->assertJsonPath('data.delivered_at', '2026-09-30T09:00:00+00:00')
            // The slot the operator did not resend survives untouched.
            ->assertJsonPath('data.scheduled_at', $delivery->scheduled_at->toIso8601String());
    }

    public function test_it_finds_a_delivery_by_its_consignment_reference(): void
    {
        $shipment = Shipment::factory()->create(['reference' => 'OPWT-6601']);
        ShipmentDelivery::factory()->create(['shipment_id' => $shipment->id]);

        $this->actingAsAdmin()
            ->getJson('/api/v1/deliveries?q=OPWT-6601')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    /**
     * The question a driver on the road is actually asked.
     */
    public function test_it_finds_a_delivery_by_the_vehicle_register(): void
    {
        $truck = Truck::factory()->create(['plate_number' => 'SXY1234A']);
        ShipmentDelivery::factory()->create(['truck_id' => $truck->id]);

        $this->actingAsAdmin()
            ->getJson('/api/v1/deliveries?q=SXY1234A')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_it_filters_to_slots_in_a_window(): void
    {
        $today = ShipmentDelivery::factory()->scheduled()->create([
            'scheduled_at' => now()->startOfDay()->addHours(10),
        ]);
        ShipmentDelivery::factory()->scheduled()->create([
            'scheduled_at' => now()->addDays(4)->startOfDay()->addHours(10),
        ]);

        $this->actingAsAdmin()
            ->getJson('/api/v1/deliveries?scheduled_from='.now()->toDateString().'&scheduled_to='.now()->toDateString())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $today->id);
    }

    public function test_it_filters_to_one_consignment(): void
    {
        $shipment = Shipment::factory()->create();
        ShipmentDelivery::factory()->count(2)->create(['shipment_id' => $shipment->id]);
        ShipmentDelivery::factory()->create();

        $this->actingAsAdmin()
            ->getJson('/api/v1/deliveries?shipment_id='.$shipment->id)
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_deleting_a_consignment_takes_its_deliveries_with_it(): void
    {
        $shipment = Shipment::factory()->create();
        $delivery = ShipmentDelivery::factory()->create(['shipment_id' => $shipment->id]);

        $shipment->forceDelete();

        $this->assertDatabaseMissing('shipment_deliveries', ['id' => $delivery->id]);
    }
}
