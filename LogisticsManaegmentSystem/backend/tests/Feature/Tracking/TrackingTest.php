<?php

namespace Tests\Feature\Tracking;

use App\Enums\TrackingMilestone;
use App\Models\Carrier;
use App\Models\Shipment;
use App\Models\ShipmentTracking;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Feature\MasterData\MasterDataTestCase;

/**
 * The tracking module.
 *
 * CRUD is the easy half and is mostly inherited. What is worth pinning down here
 * is everything that is specific to a module with no create and no delete:
 *
 * - a tracking row exists for every consignment, without anybody asking for one
 * - the row cannot be re-parented onto a different consignment
 * - there is no route that creates or destroys one, rather than a route that
 *   refuses
 * - `delayed` means what the flag on a row says, and `stale` is not just "old"
 * - the worklists exclude consignments that are finished, because those rows
 *   would sit on the list forever being nothing anybody can act on
 */
class TrackingTest extends MasterDataTestCase
{
    public function test_it_requires_a_session(): void
    {
        $this->getJson('/api/v1/tracking')->assertUnauthorized();
    }

    public function test_a_view_can_read_but_not_write(): void
    {
        $tracking = ShipmentTracking::factory()->create();

        $this->actingAsRole('viewer');
        $this->getJson('/api/v1/tracking')->assertOk();

        $this->patchJson('/api/v1/tracking/'.$tracking->id, ['current_location' => 'Somewhere'])
            ->assertForbidden();
    }

    /**
     * The substitute for a create endpoint.
     *
     * If this fails, every consignment raised from now on is invisible to the
     * screen whose entire purpose is to say where the consignment is.
     */
    public function test_a_consignment_is_tracked_from_the_moment_it_is_raised(): void
    {
        $shipment = Shipment::factory()->create(['reference' => 'SHP-TRACK-1']);

        $this->assertDatabaseHas('shipment_trackings', ['shipment_id' => $shipment->id]);

        $tracking = $shipment->tracking;

        // The honest starting state: raised, and nobody has reported on it. A row
        // invented with a plausible milestone would hide the case the worklist
        // exists to find.
        $this->assertNull($tracking->last_milestone);
        $this->assertNull($tracking->last_reported_at);
        $this->assertTrue($tracking->isUnreported());
        $this->assertFalse($tracking->isDelayed());
    }

    /**
     * The hook is on the model, so this holds for a factory and for a seeder too -
     * anything that raises a consignment gets a tracking row.
     */
    public function test_a_consignment_raised_outside_the_api_is_also_tracked(): void
    {
        $shipment = Shipment::factory()->create();

        $this->assertSame(1, ShipmentTracking::query()->where('shipment_id', $shipment->id)->count());
    }

    public function test_it_lists_tracking_with_the_consignment_it_describes(): void
    {
        $shipment = Shipment::factory()->create([
            'reference' => 'SHP-9001',
            'status' => 'in_transit',
        ]);
        $shipment->tracking->update([
            'last_milestone' => TrackingMilestone::Departed,
            'current_location' => 'Indian Ocean',
        ]);

        $this->actingAsAdmin()
            ->getJson('/api/v1/tracking')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.shipment.reference', 'SHP-9001')
            ->assertJsonPath('data.0.shipment.status', 'in_transit')
            ->assertJsonPath('data.0.last_milestone', 'departed')
            ->assertJsonPath('data.0.last_milestone_label', 'Departed')
            ->assertJsonPath('data.0.current_location', 'Indian Ocean');
    }

    public function test_it_shows_one_tracking_record(): void
    {
        $tracking = ShipmentTracking::factory()->reported()->create();

        $this->actingAsAdmin()
            ->getJson('/api/v1/tracking/'.$tracking->id)
            ->assertOk()
            ->assertJsonPath('data.id', $tracking->id)
            ->assertJsonPath('data.is_unreported', false);
    }

    public function test_a_record_that_does_not_exist_is_a_404(): void
    {
        $this->actingAsAdmin()->getJson('/api/v1/tracking/999999')->assertNotFound();
    }

    /**
     * There is no create route, so this is a 405 from the router rather than a 403
     * from a permission check. Both are refusals; only one of them means there is
     * no such capability in the API at all.
     */
    public function test_it_cannot_be_created_through_the_api(): void
    {
        $shipment = Shipment::factory()->create();

        $this->actingAsAdmin()
            ->postJson('/api/v1/tracking', ['shipment_id' => $shipment->id])
            ->assertMethodNotAllowed();
    }

    /**
     * Nor deleted. The consignment it describes is the only thing that may end it,
     * and that happens by cascade rather than by a request.
     */
    public function test_it_cannot_be_deleted_through_the_api(): void
    {
        $tracking = ShipmentTracking::factory()->create();

        $this->actingAsAdmin()
            ->deleteJson('/api/v1/tracking/'.$tracking->id)
            ->assertMethodNotAllowed();

        $this->assertDatabaseHas('shipment_trackings', ['id' => $tracking->id]);
    }

    public function test_it_records_a_carrier_report(): void
    {
        $tracking = ShipmentTracking::factory()->unreported()->create();

        $this->actingAsAdmin()
            ->patchJson('/api/v1/tracking/'.$tracking->id, [
                'last_milestone' => 'customs_hold',
                'current_location' => 'Jurong checkpoint',
                'last_reported_at' => '2026-09-20T08:30:00Z',
                'source' => 'carrier email',
            ])
            ->assertOk()
            ->assertJsonPath('data.last_milestone', 'customs_hold')
            ->assertJsonPath('data.last_milestone_label', 'Customs hold')
            ->assertJsonPath('data.is_problem', true)
            ->assertJsonPath('data.is_unreported', false);

        $this->assertDatabaseHas('shipment_trackings', [
            'id' => $tracking->id,
            'last_milestone' => 'customs_hold',
            'current_location' => 'Jurong checkpoint',
        ]);
    }

    /**
     * A partial update has to be possible. An operator told only the new arrival
     * date cannot say where the cargo is, and requiring the rest of the report
     * would mean inventing it.
     */
    public function test_it_accepts_a_partial_update_that_fills_in_one_field(): void
    {
        $tracking = ShipmentTracking::factory()->reported()->create();

        $this->actingAsAdmin()
            ->patchJson('/api/v1/tracking/'.$tracking->id, [
                'revised_eta' => '2026-10-15',
            ])
            ->assertOk()
            ->assertJsonPath('data.revised_eta', '2026-10-15')
            // Everything else survives untouched.
            ->assertJsonPath('data.current_location', $tracking->current_location);

        $this->assertSame('2026-10-15', $tracking->refresh()->revised_eta->format('Y-m-d'));
    }

    /**
     * The one field a reader would expect to be settable, and is not.
     *
     * A tracking record belongs to its consignment for the life of both. If a
     * consignment's report could be moved onto another consignment, the two rows
     * would describe the same journey and the worklist would show one of them
     * twice with the other's facts.
     */
    public function test_it_cannot_be_moved_to_another_consignment(): void
    {
        $tracking = ShipmentTracking::factory()->create();
        $other = Shipment::factory()->create();

        $this->actingAsAdmin()
            ->patchJson('/api/v1/tracking/'.$tracking->id, [
                'shipment_id' => $other->id,
                'current_location' => 'Singapore',
            ])
            ->assertOk();

        $this->assertSame(
            $tracking->shipment_id,
            $tracking->refresh()->shipment_id,
            'The tracking row was re-parented onto a different consignment.',
        );
    }

    public function test_it_rejects_an_unknown_milestone(): void
    {
        $tracking = ShipmentTracking::factory()->create();

        $this->actingAsAdmin()
            ->patchJson('/api/v1/tracking/'.$tracking->id, ['last_milestone' => 'teleported'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('last_milestone');
    }

    public function test_it_rejects_an_over_long_location(): void
    {
        $tracking = ShipmentTracking::factory()->create();

        $this->actingAsAdmin()
            ->patchJson('/api/v1/tracking/'.$tracking->id, ['current_location' => str_repeat('x', 161)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_location');
    }

    /**
     * A revised date earlier than the plan is good news, not a contradiction.
     *
     * There is no comparison rule here on purpose, and it is worth saying so: the
     * obvious `after_or_equal` against the shipment's ETA would reject the one
     * update an operator is always delighted to make.
     */
    public function test_a_revised_date_earlier_than_the_plan_is_accepted(): void
    {
        $shipment = Shipment::factory()->create([
            'status' => 'in_transit',
            'eta' => now()->addDays(20)->format('Y-m-d'),
        ]);
        $tracking = $shipment->tracking;

        $this->actingAsAdmin()
            ->patchJson('/api/v1/tracking/'.$tracking->id, [
                'revised_eta' => now()->addDays(12)->format('Y-m-d'),
            ])
            ->assertOk();
    }

    public function test_it_filters_by_milestone(): void
    {
        ShipmentTracking::factory()->milestone(TrackingMilestone::Departed)->create();
        ShipmentTracking::factory()->milestone(TrackingMilestone::Delivered)->create();

        $this->actingAsAdmin()
            ->getJson('/api/v1/tracking?last_milestone=departed')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.last_milestone', 'departed');
    }

    public function test_it_searches_the_consignment_reference(): void
    {
        $wanted = Shipment::factory()->create(['reference' => 'SHP-FIND-ME']);
        ShipmentTracking::factory()->create();
        $other = Shipment::factory()->create(['reference' => 'SHP-OTHER']);
        ShipmentTracking::factory()->create();

        $response = $this->actingAsAdmin()
            ->getJson('/api/v1/tracking?q=FIND-ME')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->assertSame($wanted->id, $response->json('data.0.shipment.id'));
        $this->assertNotSame($other->id, $response->json('data.0.shipment.id'));
    }

    public function test_it_searches_the_carrier(): void
    {
        $carrier = Carrier::factory()->create(['name' => 'CMA CGM Kerguelen']);
        $shipment = Shipment::factory()->create(['carrier_id' => $carrier->id]);
        $other = Shipment::factory()->create(['carrier_id' => Carrier::factory()]);

        $response = $this->actingAsAdmin()
            ->getJson('/api/v1/tracking?q=Kerguelen')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->assertSame($shipment->id, $response->json('data.0.shipment.id'));
        $this->assertNotSame($other->id, $response->json('data.0.shipment.id'));
    }

    public function test_it_searches_the_free_text_an_operator_typed(): void
    {
        $tracking = ShipmentTracking::factory()->reported()->create([
            'current_location' => 'Singapore Freeport',
        ]);
        ShipmentTracking::factory()->reported()->create([
            'current_location' => 'Port of Long Beach',
        ]);

        $response = $this->actingAsAdmin()
            ->getJson('/api/v1/tracking?q=Freeport')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->assertSame($tracking->id, $response->json('data.0.id'));
    }

    /**
     * The filter and the flag on the row have to agree.
     *
     * A caller listing `delayed=1` and getting rows whose own `is_delayed` says
     * false would be looking at a bug, not a filter.
     */
    public function test_the_delayed_filter_and_the_delayed_flag_agree(): void
    {
        ShipmentTracking::factory()->delayed()->create();
        ShipmentTracking::factory()->delayed()->create();
        ShipmentTracking::factory()->reported()->create();

        $response = $this->actingAsAdmin()
            ->getJson('/api/v1/tracking?delayed=1')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        foreach ($response->json('data') as $row) {
            $this->assertTrue($row['is_delayed'], 'A row listed as delayed says it is not.');
        }
    }

    /**
     * A revised date with no reason is still a delay: the carrier moved the date,
     * and an operator who cannot say why is not making that up.
     */
    public function test_a_revised_date_counts_as_a_delay_without_a_reason(): void
    {
        $tracking = ShipmentTracking::factory()->revisedWithoutReason()->create();

        $this->assertTrue($tracking->isDelayed());

        $this->actingAsAdmin()
            ->getJson('/api/v1/tracking?delayed=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $tracking->id);
    }

    /**
     * "Stale" is not "old". A consignment nobody has ever reported on is the case
     * the worklist most needs to show, and a filter that only matched old reports
     * would leave it out.
     */
    public function test_the_stale_filter_includes_consignments_never_reported_on(): void
    {
        $never = ShipmentTracking::factory()->unreported()->create();
        $quiet = ShipmentTracking::factory()->stale()->create();
        $fresh = ShipmentTracking::factory()->reported()->create();

        $response = $this->actingAsAdmin()
            ->getJson('/api/v1/tracking?stale=1')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $ids = array_column($response->json('data'), 'id');

        $this->assertContains($never->id, $ids);
        $this->assertContains($quiet->id, $ids);
        $this->assertNotContains($fresh->id, $ids, 'A consignment reported on an hour ago is not stale.');
    }

    /**
     * The same agreement as `delayed`, for the same reason.
     *
     * Staleness needs this more than delay does, because the flag is relative to
     * the clock: the filter and the badge are two evaluations of "how long since
     * we heard", and only sharing `staleCutoff` keeps them from disagreeing at
     * three days and a second. The flag is what the browser draws, so a filter
     * that quietly used a different threshold would produce a worklist whose own
     * rows deny being on it.
     *
     * Checked in both directions, since "every listed row claims to be stale" alone
     * would still pass if the filter quietly matched nothing.
     */
    public function test_the_stale_filter_and_the_stale_flag_agree(): void
    {
        $never = ShipmentTracking::factory()->unreported()->create();
        $quiet = ShipmentTracking::factory()->stale()->create();
        $fresh = ShipmentTracking::factory()->reported()->create();

        $response = $this->actingAsAdmin()
            ->getJson('/api/v1/tracking?stale=1')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $ids = array_column($response->json('data'), 'id');

        foreach ($response->json('data') as $row) {
            $this->assertTrue($row['is_stale'], 'A row listed as stale says it is not.');
        }

        // The reverse direction: the row that the model calls stale is one the
        // filter would have found.
        $this->assertTrue($never->fresh()->isStale());
        $this->assertTrue($quiet->fresh()->isStale());
        $this->assertFalse($fresh->fresh()->isStale());

        $this->assertContains($never->id, $ids);
        $this->assertContains($quiet->id, $ids);
    }

    /**
     * The threshold is a policy, not a per-request parameter, and both the filter
     * and the flag are measured against it.
     */
    public function test_staleness_is_measured_against_the_shared_cutoff(): void
    {
        $onTheBoundary = ShipmentTracking::factory()->reported()->create([
            'last_reported_at' => ShipmentTracking::staleCutoff()->subSecond(),
        ]);

        $inside = ShipmentTracking::factory()->reported()->create([
            'last_reported_at' => ShipmentTracking::staleCutoff()->addSecond(),
        ]);

        $this->assertTrue($onTheBoundary->fresh()->isStale());
        $this->assertFalse($inside->fresh()->isStale());

        $ids = array_column(
            $this->actingAsAdmin()->getJson('/api/v1/tracking?stale=1')->assertOk()->json('data'),
            'id'
        );

        $this->assertContains($onTheBoundary->id, $ids);
        $this->assertNotContains($inside->id, $ids);
    }

    /**
     * A consignment that has been delivered going quiet is history, and one that
     * is being flagged as delayed is a record that will never change. Left in,
     * they sit on the worklist forever being nothing anybody can act on.
     */
    public function test_the_worklists_exclude_consignments_that_are_finished(): void
    {
        $delivered = Shipment::factory()->create(['status' => 'delivered']);
        ShipmentTracking::factory()->stale()->create(['shipment_id' => $delivered->id]);
        ShipmentTracking::factory()->delayed()->create(['shipment_id' => $delivered->id]);

        $cancelled = Shipment::factory()->create(['status' => 'cancelled']);
        ShipmentTracking::factory()->stale()->create(['shipment_id' => $cancelled->id]);

        $this->actingAsAdmin()
            ->getJson('/api/v1/tracking?stale=1')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->actingAsAdmin()
            ->getJson('/api/v1/tracking?delayed=1')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    /**
     * Held at customs, or broken. Neither has necessarily had a date revised, so
     * this is a worklist the delayed filter cannot produce on its own.
     */
    public function test_it_lists_problem_reports(): void
    {
        $held = ShipmentTracking::factory()->problem()->create();
        $rolling = ShipmentTracking::factory()->milestone(TrackingMilestone::Departed)->create();

        $response = $this->actingAsAdmin()
            ->getJson('/api/v1/tracking?problems=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $held->id);

        $this->assertNotSame($rolling->id, $response->json('data.0.id'));
    }

    /**
     * The filter an operations desk actually uses: everything of theirs that has
     * gone quiet. The carrier is a consignment's column, not a tracking column, so
     * this reaches through.
     */
    public function test_it_filters_by_the_consignments_carrier(): void
    {
        $mine = Carrier::factory()->create();
        $theirs = Carrier::factory()->create();

        $consignment = Shipment::factory()->create([
            'status' => 'in_transit',
            'carrier_id' => $mine->id,
        ]);
        ShipmentTracking::factory()->stale()->create(['shipment_id' => $consignment->id]);

        $other = Shipment::factory()->create([
            'status' => 'in_transit',
            'carrier_id' => $theirs->id,
        ]);
        ShipmentTracking::factory()->stale()->create(['shipment_id' => $other->id]);

        $response = $this->actingAsAdmin()
            ->getJson('/api/v1/tracking?stale=1&carrier_id='.$mine->id)
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->assertSame($consignment->id, $response->json('data.0.shipment.id'));
    }

    /**
     * Soft-deleting a consignment must not leave a row in the worklist pointing at
     * nothing, which is what the `open` scope's whereHas does as a side effect.
     */
    public function test_a_deleted_consignment_leaves_no_orphan_row_on_the_worklist(): void
    {
        $shipment = Shipment::factory()->create(['status' => 'in_transit']);
        ShipmentTracking::factory()->stale()->create(['shipment_id' => $shipment->id]);

        $this->actingAsAdmin()
            ->getJson('/api/v1/tracking?stale=1')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $shipment->delete();

        $this->actingAsAdmin()
            ->getJson('/api/v1/tracking?stale=1')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    /**
     * One consignment, one row.
     *
     * Inserted through the query builder rather than the model, because the model
     * and the factory both respect the invariant by never trying. Eloquent has no
     * way to say "at most one", so the unique index is the only thing enforcing
     * it - and a test that went through Eloquent would still pass with the index
     * dropped.
     */
    public function test_a_consignment_cannot_have_two_tracking_rows(): void
    {
        $shipment = Shipment::factory()->create();

        $this->assertSame(1, ShipmentTracking::query()->where('shipment_id', $shipment->id)->count());

        $this->expectException(QueryException::class);

        DB::table('shipment_trackings')->insert([
            'shipment_id' => $shipment->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * The row dies with the consignment it describes, and only with it.
     */
    public function test_force_deleting_a_consignment_takes_its_tracking_row_with_it(): void
    {
        $shipment = Shipment::factory()->create();
        $tracking = ShipmentTracking::factory()->create(['shipment_id' => $shipment->id]);

        // Soft delete keeps the row: the consignment is still there, just hidden,
        // and its history is worth more than a tidy table.
        $shipment->delete();
        $this->assertDatabaseHas('shipment_trackings', ['id' => $tracking->id]);

        $shipment->forceDelete();
        $this->assertDatabaseMissing('shipment_trackings', ['id' => $tracking->id]);
    }

    public function test_it_sorts_by_the_most_recent_report(): void
    {
        $old = ShipmentTracking::factory()->create(['last_reported_at' => now()->subDays(10)]);
        $new = ShipmentTracking::factory()->create(['last_reported_at' => now()->subHour()]);

        $this->actingAsAdmin()
            ->getJson('/api/v1/tracking?sort=last_reported_at&direction=desc')
            ->assertOk()
            ->assertJsonPath('data.0.id', $new->id)
            ->assertJsonPath('data.1.id', $old->id);
    }

    /**
     * A sort column is never passed through unvalidated: an attacker-controlled
     * column name is a way to probe the schema, and a bad one is a 500 rather than
     * a helpful 422.
     */
    public function test_it_ignores_an_unknown_sort_column(): void
    {
        ShipmentTracking::factory()->create();

        $this->actingAsAdmin()
            ->getJson('/api/v1/tracking?sort=drop_table')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }
}
