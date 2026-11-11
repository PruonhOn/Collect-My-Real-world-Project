<?php

namespace Tests\Feature\MasterData;

use App\Models\Carrier;
use App\Models\Driver;
use App\Models\Truck;

/**
 * Drivers, the module with two relations and a compliance window.
 */
class DriverTest extends MasterDataTestCase
{
    public function test_it_requires_a_session(): void
    {
        $this->getJson('/api/v1/drivers')
            ->assertUnauthorized();
    }

    public function test_it_creates_a_driver_with_a_carrier_and_truck(): void
    {
        $carrier = Carrier::factory()->create(['name' => 'Swee Heng Logistics']);
        $truck = Truck::factory()->forCarrier($carrier)->create();

        $this->actingAsAdmin()
            ->postJson('/api/v1/drivers', [
                'code' => 'drv-001',
                'name' => 'Lim Wei Ming',
                'license_number' => 'CDL-1234567',
                'nationality' => 'sg',
                'carrier_id' => $carrier->id,
                'truck_id' => $truck->id,
                'license_expiry' => '2029-06-30',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'DRV-001')
            ->assertJsonPath('data.nationality', 'SG')
            ->assertJsonPath('data.carrier.id', $carrier->id)
            ->assertJsonPath('data.truck.id', $truck->id)
            // Date-only columns come back as plain dates, not a misleading time.
            ->assertJsonPath('data.license_expiry', '2029-06-30');
    }

    public function test_it_rejects_a_soft_deleted_truck(): void
    {
        $truck = Truck::factory()->create();
        $truck->delete();

        $this->actingAsAdmin()
            ->postJson('/api/v1/drivers', [
                'code' => 'DRV-002',
                'name' => 'Retired Rig',
                'truck_id' => $truck->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('truck_id');
    }

    public function test_a_driver_code_cannot_be_reused(): void
    {
        Driver::factory()->unassigned()->create(['code' => 'DRV-001']);

        $this->actingAsAdmin()
            ->postJson('/api/v1/drivers', ['code' => 'drv-001', 'name' => 'Impostor'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    /**
     * Backs the compliance screen: a driver whose licence or medical lapses
     * inside the window has to be findable without paging through everyone.
     */
    public function test_it_filters_by_licence_expiry_window(): void
    {
        $soon = Driver::factory()->unassigned()->expiringSoon(10)->create(['name' => 'Expiring Soon']);
        $later = Driver::factory()->unassigned()->expiringSoon(400)->create(['name' => 'Later']);
        Driver::factory()->unassigned()->expired()->create(['name' => 'Already Expired']);

        $response = $this->actingAsAdmin()
            ->getJson('/api/v1/drivers?expiring_within_days=30')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $names = collect($response->json('data'))->pluck('name');

        $this->assertContains($soon->name, $names);
        $this->assertContains('Already Expired', $names);
        $this->assertNotContains($later->name, $names);
    }

    /**
     * A medical certificate lapsing counts too, not just the driving licence.
     */
    public function test_the_expiry_window_catches_a_lapsing_medical(): void
    {
        Driver::factory()->unassigned()->create([
            'name' => 'Medical First',
            'license_expiry' => now()->addYears(3)->toDateString(),
            'medical_expiry' => now()->addDays(5)->toDateString(),
        ]);

        $this->actingAsAdmin()
            ->getJson('/api/v1/drivers?expiring_within_days=30')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Medical First');
    }

    /**
     * Both relations have to be on every row. `preventLazyLoading` means a
     * missing eager load raises instead of quietly querying per row, so a
     * populated key is a sufficient guard - see TruckTest for the reasoning.
     */
    public function test_the_listing_carries_both_relations_on_every_row(): void
    {
        $carrier = Carrier::factory()->create(['name' => 'Kerry Logistics']);
        Driver::factory()->count(3)->assigned($carrier)->create();

        $response = $this->actingAsAdmin()
            ->getJson('/api/v1/drivers')
            ->assertOk()
            ->assertJsonCount(3, 'data');

        foreach ($response->json('data') as $row) {
            $this->assertSame($carrier->id, $row['carrier']['id']);
            $this->assertNotNull($row['truck']['id']);
            $this->assertNotNull($row['truck']['plate_number']);
        }
    }

    public function test_a_driver_may_have_no_relations_at_all(): void
    {
        $this->actingAsAdmin()
            ->getJson('/api/v1/drivers')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->actingAsAdmin()
            ->postJson('/api/v1/drivers', [
                'code' => 'DRV-003',
                'name' => 'Agency Driver',
            ])
            ->assertCreated()
            // Present and null, not absent: the client should not have to tell
            // "unassigned" apart from "this field does not exist".
            ->assertJsonPath('data.carrier', null)
            ->assertJsonPath('data.truck', null);
    }

    public function test_it_filters_by_carrier(): void
    {
        $wanted = Carrier::factory()->create();
        $other = Carrier::factory()->create();

        Driver::factory()->assigned($wanted)->create();
        Driver::factory()->assigned($other)->create();

        $this->actingAsAdmin()
            ->getJson('/api/v1/drivers?carrier_id='.$wanted->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.carrier.id', $wanted->id);
    }

    public function test_it_soft_deletes_a_driver(): void
    {
        $driver = Driver::factory()->assigned()->create();

        $this->actingAsAdmin()
            ->deleteJson('/api/v1/drivers/'.$driver->id)
            ->assertOk();

        $this->assertSoftDeleted('drivers', ['id' => $driver->id]);

        $this->actingAsAdmin()
            ->getJson('/api/v1/drivers/'.$driver->id)
            ->assertNotFound();
    }
}
