<?php

namespace Tests\Feature\Containers;

use App\Enums\ContainerStatus;
use App\Models\Shipment;
use App\Models\ShipmentContainer;
use Illuminate\Database\QueryException;
use Tests\Feature\MasterData\MasterDataTestCase;

/**
 * The container module: the equipment a consignment travels in.
 *
 * CRUD here is inherited and mostly uninteresting. What is worth pinning down is
 * the one thing about containers that is unlike every other module in the schema:
 *
 * - a container number identifies a physical asset that goes round again, so the
 *   same number is legal on a second consignment and illegal twice on the first
 * - a consignment need not have any, and that is a state rather than a gap
 * - the worklist flag and the worklist filter are the same predicate
 */
class ContainerTest extends MasterDataTestCase
{
    public function test_it_requires_a_session(): void
    {
        $this->getJson('/api/v1/containers')->assertUnauthorized();
    }

    public function test_a_viewer_can_read_but_not_write(): void
    {
        $container = ShipmentContainer::factory()->create();

        $this->actingAsRole('viewer');
        $this->getJson('/api/v1/containers')->assertOk();

        $this->patchJson('/api/v1/containers/'.$container->id, ['status' => 'loaded'])
            ->assertForbidden();
    }

    /**
     * The clerk holds create and update but not delete, which is the whole point of
     * the read/write split on this module: a box that was booked and released has to
     * be removable, but not by the day-to-day role.
     */
    public function test_a_clerk_can_book_a_container_but_not_remove_one(): void
    {
        $shipment = Shipment::factory()->create();

        $this->actingAsRole('operations-clerk')
            ->postJson('/api/v1/containers', [
                'shipment_id' => $shipment->id,
                'container_number' => 'MSCU1234567-9',
                'iso_code' => '45G1',
                'status' => 'empty',
            ])
            ->assertCreated()
            ->assertJsonPath('data.container_number', 'MSCU1234567-9')
            ->assertJsonPath('data.status', 'empty');

        $container = ShipmentContainer::query()->firstOrFail();

        $this->actingAsRole('operations-clerk')
            ->deleteJson('/api/v1/containers/'.$container->id)
            ->assertForbidden();

        $this->assertDatabaseHas('shipment_containers', ['id' => $container->id]);
    }

    public function test_a_container_starts_empty_because_that_is_the_truth(): void
    {
        $container = ShipmentContainer::factory()->create();

        $this->assertSame(ContainerStatus::Empty, $container->status);
        $this->assertTrue($container->isEmpty());
        $this->assertFalse($container->needsAttention());
        $this->assertFalse($container->isClosed());
    }

    /**
     * A manifest of bare box numbers would send the operator back to the consignment
     * for every row, which is the work this list exists to save.
     */
    public function test_it_lists_containers_with_the_consignment_they_are_on(): void
    {
        $shipment = Shipment::factory()->create([
            'reference' => 'OPWT-9001',
            'status' => 'in_transit',
        ]);

        ShipmentContainer::factory()->inTransit()->create([
            'shipment_id' => $shipment->id,
            'container_number' => 'TGHU7654321-0',
        ]);

        $this->actingAsAdmin()
            ->getJson('/api/v1/containers')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.container_number', 'TGHU7654321-0')
            ->assertJsonPath('data.0.shipment.reference', 'OPWT-9001')
            ->assertJsonPath('data.0.shipment.status', 'in_transit');
    }

    public function test_it_shows_one_container(): void
    {
        $container = ShipmentContainer::factory()->released()->create();

        $this->actingAsAdmin()
            ->getJson('/api/v1/containers/'.$container->id)
            ->assertOk()
            ->assertJsonPath('data.id', $container->id)
            ->assertJsonPath('data.status', 'released')
            ->assertJsonPath('data.status_label', 'Released');
    }

    public function test_a_container_that_does_not_exist_is_a_404(): void
    {
        $this->actingAsAdmin()->getJson('/api/v1/containers/999999')->assertNotFound();
    }

    public function test_a_container_belongs_to_a_consignment(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/containers', ['container_number' => 'MSCU0000001-0'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('shipment_id');
    }

    /**
     * Not a link into the void. `shipments` soft deletes, so an archived consignment
     * is still a row, and offering it in the picker would let a container be booked
     * onto something no operator can open.
     */
    public function test_it_rejects_an_archived_consignment(): void
    {
        $shipment = Shipment::factory()->create();
        $shipment->delete();

        $this->actingAsAdmin()
            ->postJson('/api/v1/containers', [
                'shipment_id' => $shipment->id,
                'container_number' => 'MSCU0000002-0',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('shipment_id');
    }

    /**
     * The pair is unique, because two rows for one box on one consignment read on a
     * manifest as a consignment with a spare container nobody can account for.
     */
    public function test_the_same_container_cannot_be_booked_twice_on_one_consignment(): void
    {
        $shipment = Shipment::factory()->create();
        ShipmentContainer::factory()->create([
            'shipment_id' => $shipment->id,
            'container_number' => 'MSCU1234567-9',
        ]);

        $this->actingAsAdmin()
            ->postJson('/api/v1/containers', [
                'shipment_id' => $shipment->id,
                'container_number' => 'MSCU1234567-9',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('container_number');

        $this->assertSame(1, ShipmentContainer::query()->where('shipment_id', $shipment->id)->count());
    }

    /**
     * ...and the box itself is not, because containers are reused.
     *
     * This is the test that fails if the uniqueness is ever tightened to a single
     * column. The failure would be a 422 refusing a real, physical container that is
     * standing in the yard, which is a considerably worse bug than a missing unique
     * index.
     */
    public function test_the_same_container_can_be_booked_on_a_later_consignment(): void
    {
        $first = Shipment::factory()->create();
        $second = Shipment::factory()->create();

        ShipmentContainer::factory()->create([
            'shipment_id' => $first->id,
            'container_number' => 'MSCU1234567-9',
        ]);

        $this->actingAsAdmin()
            ->postJson('/api/v1/containers', [
                'shipment_id' => $second->id,
                'container_number' => 'MSCU1234567-9',
            ])
            ->assertCreated()
            ->assertJsonPath('data.shipment.id', $second->id);

        $this->assertSame(2, ShipmentContainer::query()->where('container_number', 'MSCU1234567-9')->count());
    }

    /**
     * The same refusal, enforced by the index when two requests race past the
     * validation rule. Both requests were individually correct, so the database is
     * the only thing left that can say no.
     */
    public function test_the_database_refuses_a_duplicate_the_validator_let_through(): void
    {
        $shipment = Shipment::factory()->create();
        ShipmentContainer::factory()->create([
            'shipment_id' => $shipment->id,
            'container_number' => 'MSCU1234567-9',
        ]);

        $this->expectException(QueryException::class);

        ShipmentContainer::query()->create([
            'shipment_id' => $shipment->id,
            'container_number' => 'MSCU1234567-9',
        ]);
    }

    /**
     * A booking confirmation and a manual entry will not agree on casing, and the
     * unique index is not going to help with a case-insensitive match.
     */
    public function test_a_container_number_is_the_same_number_in_any_casing(): void
    {
        $shipment = Shipment::factory()->create();

        $this->actingAsAdmin()
            ->postJson('/api/v1/containers', [
                'shipment_id' => $shipment->id,
                'container_number' => '  mscu1234567-9  ',
            ])
            ->assertCreated()
            ->assertJsonPath('data.container_number', 'MSCU1234567-9');

        $this->actingAsAdmin()
            ->postJson('/api/v1/containers', [
                'shipment_id' => $shipment->id,
                'container_number' => 'mscu1234567-9',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('container_number');
    }

    public function test_it_rejects_an_unknown_status(): void
    {
        $container = ShipmentContainer::factory()->create();

        $this->actingAsAdmin()
            ->patchJson('/api/v1/containers/'.$container->id, ['status' => 'teleported'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    /**
     * An equipment report is assembled from whatever the carrier said, and a
     * container's weight is not something the office can work out.
     */
    public function test_it_accepts_a_partial_update_of_a_single_measurement(): void
    {
        $container = ShipmentContainer::factory()->create([
            'iso_code' => '22G1',
            'tare_weight_kg' => 2250,
        ]);

        $this->actingAsAdmin()
            ->patchJson('/api/v1/containers/'.$container->id, ['gross_weight_kg' => 18000])
            ->assertOk()
            ->assertJsonPath('data.gross_weight_kg', 18000)
            ->assertJsonPath('data.iso_code', '22G1')
            ->assertJsonPath('data.tare_weight_kg', 2250);
    }

    public function test_it_rejects_a_negative_weight(): void
    {
        $container = ShipmentContainer::factory()->create();

        $this->actingAsAdmin()
            ->patchJson('/api/v1/containers/'.$container->id, ['tare_weight_kg' => -1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('tare_weight_kg');
    }

    /**
     * The flag on a row and the filter that produced it are the same predicate, or
     * a caller listing "needs attention" gets rows that say otherwise.
     */
    public function test_needs_attention_returns_exactly_the_rows_that_are_flagged(): void
    {
        $shipment = Shipment::factory()->create(['status' => 'in_transit']);

        $held = ShipmentContainer::factory()->customsHold()->create([
            'shipment_id' => $shipment->id,
        ]);
        $broken = ShipmentContainer::factory()->damaged()->create([
            'shipment_id' => $shipment->id,
        ]);
        $moving = ShipmentContainer::factory()->inTransit()->create([
            'shipment_id' => $shipment->id,
        ]);

        $response = $this->actingAsAdmin()
            ->getJson('/api/v1/containers?needs_attention=1')
            ->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();

        sort($ids);
        $expected = [$held->id, $broken->id];
        sort($expected);

        $this->assertSame($expected, $ids);
        $this->assertNotContains($moving->id, $ids);

        // And the flag each row carries agrees with the filter that returned it.
        foreach ($response->json('data') as $row) {
            $this->assertTrue($row['needs_attention']);
        }
    }

    /**
     * A box on a finished consignment is history. Leaving it on the worklist would
     * mean an operator chasing something that cannot change.
     */
    public function test_the_worklist_excludes_a_consignment_that_is_already_delivered(): void
    {
        $shipment = Shipment::factory()->create(['status' => 'delivered']);
        ShipmentContainer::factory()->customsHold()->create(['shipment_id' => $shipment->id]);

        $this->actingAsAdmin()
            ->getJson('/api/v1/containers?needs_attention=1')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    /**
     * No figure to compare, so no percentage. A confident 0 would be an answer to a
     * question nobody asked.
     */
    public function test_an_unweighed_container_has_no_utilisation(): void
    {
        $container = ShipmentContainer::factory()->create();

        $this->assertNull($container->utilisationPercent());

        $this->actingAsAdmin()
            ->getJson('/api/v1/containers/'.$container->id)
            ->assertOk()
            ->assertJsonPath('data.utilisation_percent', null);
    }

    public function test_utilisation_is_measured_against_the_rating_on_the_door(): void
    {
        $container = ShipmentContainer::factory()->create([
            'max_gross_weight_kg' => 30480,
            'gross_weight_kg' => 22860,
        ]);

        $this->assertSame(25, $container->utilisationPercent());
    }

    /**
     * An overload is a fact about the load, not a typo to reject, and the honest
     * reading of one is a number below zero.
     */
    public function test_an_overloaded_box_reports_negative_utilisation(): void
    {
        $container = ShipmentContainer::factory()->create([
            'max_gross_weight_kg' => 30480,
            'gross_weight_kg' => 32000,
        ]);

        $this->actingAsAdmin()
            ->getJson('/api/v1/containers/'.$container->id)
            ->assertOk()
            ->assertJsonPath('data.utilisation_percent', -5);
    }

    /**
     * A movement on a truck has no containers. That is a legitimate state, so
     * neither the schema nor the controller may make one look like a gap.
     */
    public function test_a_consignment_does_not_have_to_have_containers(): void
    {
        $shipment = Shipment::factory()->create();

        $this->assertCount(0, $shipment->containers);
        $this->assertDatabaseMissing('shipment_containers', ['shipment_id' => $shipment->id]);
    }

    /**
     * The equipment is part of the consignment, so a hard delete takes it along.
     * Left behind, these rows would name a shipment that no longer exists and there
     * would be nothing on them to say so.
     */
    public function test_deleting_a_consignment_takes_its_containers_with_it(): void
    {
        $shipment = Shipment::factory()->create();
        $container = ShipmentContainer::factory()->create(['shipment_id' => $shipment->id]);

        $shipment->forceDelete();

        $this->assertDatabaseMissing('shipment_containers', ['id' => $container->id]);
    }

    /**
     * Archiving is the opposite case, and it is the common one: an archived
     * consignment can come back, and its boxes come back with it.
     */
    public function test_archiving_a_consignment_keeps_its_containers(): void
    {
        $shipment = Shipment::factory()->create();
        $container = ShipmentContainer::factory()->create(['shipment_id' => $shipment->id]);

        $shipment->delete();

        $this->assertSoftDeleted('shipments', ['id' => $shipment->id]);
        $this->assertDatabaseHas('shipment_containers', ['id' => $container->id]);
    }

    public function test_it_finds_a_container_by_its_consignment_reference(): void
    {
        $shipment = Shipment::factory()->create(['reference' => 'OPWT-4471']);
        ShipmentContainer::factory()->create([
            'shipment_id' => $shipment->id,
            'container_number' => 'OOLU1111111-2',
        ]);

        $this->actingAsAdmin()
            ->getJson('/api/v1/containers?q=OPWT-4471')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.container_number', 'OOLU1111111-2');
    }

    public function test_it_filters_to_one_consignment(): void
    {
        $shipment = Shipment::factory()->create();
        ShipmentContainer::factory()->count(2)->create(['shipment_id' => $shipment->id]);
        ShipmentContainer::factory()->create();

        $this->actingAsAdmin()
            ->getJson('/api/v1/containers?shipment_id='.$shipment->id)
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_it_deletes_a_container(): void
    {
        $container = ShipmentContainer::factory()->create();

        $this->actingAsAdmin()
            ->deleteJson('/api/v1/containers/'.$container->id)
            ->assertOk()
            ->assertJsonPath('message', 'Record deleted.');

        $this->assertDatabaseMissing('shipment_containers', ['id' => $container->id]);
    }
}
