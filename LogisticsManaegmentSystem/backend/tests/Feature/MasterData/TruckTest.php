<?php

namespace Tests\Feature\MasterData;

use App\Models\Carrier;
use App\Models\Truck;

/**
 * Trucks, the first module with a real foreign key.
 */
class TruckTest extends MasterDataTestCase
{
    public function test_it_requires_a_session(): void
    {
        $this->getJson('/api/v1/trucks')
            ->assertUnauthorized();
    }

    public function test_it_creates_a_truck_with_a_carrier(): void
    {
        $carrier = Carrier::factory()->create(['name' => 'Swee Heng Logistics']);

        $this->actingAsAdmin()
            ->postJson('/api/v1/trucks', [
                'plate_number' => 'sh1234a',
                'carrier_id' => $carrier->id,
                'truck_type' => 'tractor',
                'capacity_kg' => 24000,
                'make' => 'Volvo',
                'year' => '2021',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.plate_number', 'SH1234A')
            ->assertJsonPath('data.carrier.id', $carrier->id)
            ->assertJsonPath('data.carrier.name', 'Swee Heng Logistics');
    }

    /**
     * A vehicle is often onboarded before a carrier is assigned, so the column is
     * optional. The listing still has to say "unassigned" rather than omit the key
     * and leave the client guessing.
     */
    public function test_a_truck_can_be_created_without_a_carrier(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/trucks', [
                'plate_number' => 'NEW001A',
                'truck_type' => 'box_truck',
            ])
            ->assertCreated()
            ->assertJsonPath('data.carrier', null);
    }

    public function test_it_rejects_an_inactive_carrier(): void
    {
        $carrier = Carrier::factory()->inactive()->create();

        // A live truck has to be attached to a carrier the scheduler can actually
        // book, so an inactive one is not an acceptable target.
        $this->actingAsAdmin()
            ->postJson('/api/v1/trucks', [
                'plate_number' => 'BAD001A',
                'carrier_id' => $carrier->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('carrier_id');
    }

    public function test_it_rejects_a_soft_deleted_carrier(): void
    {
        $carrier = Carrier::factory()->create();
        $carrier->delete();

        $this->actingAsAdmin()
            ->postJson('/api/v1/trucks', [
                'plate_number' => 'BAD002A',
                'carrier_id' => $carrier->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('carrier_id');
    }

    public function test_a_plate_number_cannot_be_reused_in_another_case(): void
    {
        Truck::factory()->create(['plate_number' => 'SH1234A']);

        $this->actingAsAdmin()
            ->postJson('/api/v1/trucks', ['plate_number' => 'sh1234a'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('plate_number');
    }

    public function test_it_rejects_an_unknown_truck_type(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/trucks', [
                'plate_number' => 'OK1234A',
                'truck_type' => 'hovercraft',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('truck_type');
    }

    /**
     * The listing must carry the carrier, not just the carrier id.
     *
     * `preventLazyLoading` is on outside production, so if the relation were
     * missing from `eagerLoads()` the resource would raise rather than quietly
     * issue a query per row. That makes "the key is present and populated" a
     * sufficient guard against N+1, and unlike a query counter it does not go
     * stale the next time pagination changes.
     */
    public function test_the_listing_carries_the_carrier_on_every_row(): void
    {
        $carrier = Carrier::factory()->create(['name' => 'Kerry Logistics']);
        Truck::factory()->count(3)->forCarrier($carrier)->create();

        $response = $this->actingAsAdmin()
            ->getJson('/api/v1/trucks')
            ->assertOk()
            ->assertJsonCount(3, 'data');

        foreach ($response->json('data') as $row) {
            $this->assertSame($carrier->id, $row['carrier']['id']);
            $this->assertSame('Kerry Logistics', $row['carrier']['name']);
        }
    }

    /**
     * The carrier is nested by reference only. A full nested carrier would drag
     * that carrier's trucks, and their drivers, into every row of a paged list.
     */
    public function test_the_carrier_is_nested_by_reference(): void
    {
        $carrier = Carrier::factory()->create();
        Truck::factory()->count(2)->forCarrier($carrier)->create();

        $this->actingAsAdmin()
            ->getJson('/api/v1/trucks')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    ['carrier' => ['id', 'code', 'name']],
                ],
            ]);
    }

    public function test_it_filters_by_carrier(): void
    {
        $wanted = Carrier::factory()->create();
        $other = Carrier::factory()->create();

        Truck::factory()->forCarrier($wanted)->create();
        Truck::factory()->forCarrier($other)->create();
        Truck::factory()->withoutCarrier()->create();

        $this->actingAsAdmin()
            ->getJson('/api/v1/trucks?carrier_id='.$wanted->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.carrier.id', $wanted->id);
    }

    public function test_it_updates_only_the_fields_that_were_sent(): void
    {
        $truck = Truck::factory()->create(['make' => 'Volvo', 'year' => '2019']);

        $this->actingAsAdmin()
            ->putJson('/api/v1/trucks/'.$truck->id, ['year' => '2024'])
            ->assertOk()
            ->assertJsonPath('data.year', '2024')
            ->assertJsonPath('data.make', 'Volvo');
    }

    public function test_it_soft_deletes_a_truck(): void
    {
        $truck = Truck::factory()->create();

        $this->actingAsAdmin()
            ->deleteJson('/api/v1/trucks/'.$truck->id)
            ->assertOk();

        $this->assertSoftDeleted('trucks', ['id' => $truck->id]);

        $this->actingAsAdmin()
            ->getJson('/api/v1/trucks/'.$truck->id)
            ->assertNotFound();
    }
}
