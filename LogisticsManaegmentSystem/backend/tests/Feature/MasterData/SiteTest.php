<?php

namespace Tests\Feature\MasterData;

use App\Enums\SiteKind;
use App\Models\Site;

/**
 * Warehouses and factories, the second pair of surfaces sharing a table.
 *
 * Two things are worth pinning down: the surfaces stay isolated, and the two
 * warehouse-only columns are refused on a factory rather than stored and then
 * silently ignored by every screen that reads them.
 */
class SiteTest extends MasterDataTestCase
{
    public function test_it_requires_a_session(): void
    {
        $this->getJson('/api/v1/warehouses')
            ->assertUnauthorized();
    }

    public function test_it_creates_a_warehouse_and_returns_the_record(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/warehouses', [
                'code' => 'wh-sg1',
                'name' => 'Jurong West Logistics Centre',
                'city' => 'Singapore',
                'country_code' => 'sg',
                'site_type' => 'bonded',
                'capacity_pallets' => 2400,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            // The kind comes from the route, and is echoed back so the client can
            // confirm what it created even though it did not choose it.
            ->assertJsonPath('data.kind', 'warehouse')
            ->assertJsonPath('data.code', 'WH-SG1')
            ->assertJsonPath('data.site_type', 'bonded')
            ->assertJsonPath('data.capacity_pallets', 2400);
    }

    public function test_it_creates_a_factory(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/factories', [
                'code' => 'fac-sg1',
                'name' => 'Assembly Plant 1',
                'city' => 'Singapore',
                'country_code' => 'sg',
            ])
            ->assertCreated()
            ->assertJsonPath('data.kind', 'factory')
            ->assertJsonPath('data.code', 'FAC-SG1');
    }

    public function test_a_client_cannot_choose_the_kind(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/warehouses', [
                'code' => 'SMUGGLED',
                'name' => 'Smuggled',
                'kind' => 'factory',
            ])
            ->assertCreated()
            ->assertJsonPath('data.kind', 'warehouse');
    }

    public function test_each_surface_only_lists_its_own_kind(): void
    {
        $warehouse = Site::factory()->ofKind(SiteKind::Warehouse)->create(['name' => 'The Warehouse']);
        Site::factory()->ofKind(SiteKind::Factory)->create(['name' => 'The Factory']);

        $this->actingAsAdmin()
            ->getJson('/api/v1/warehouses')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $warehouse->id);

        $this->actingAsAdmin()
            ->getJson('/api/v1/factories')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'The Factory');
    }

    /**
     * The failure this prevents: a factory listed as a storage location, or a
     * warehouse quietly rewritten through the factory endpoint.
     */
    public function test_a_factory_is_not_reachable_through_the_warehouse_surface(): void
    {
        $factory = Site::factory()->ofKind(SiteKind::Factory)->create();

        $this->actingAsAdmin()
            ->getJson('/api/v1/warehouses/'.$factory->id)
            ->assertNotFound();

        $this->actingAsAdmin()
            ->putJson('/api/v1/warehouses/'.$factory->id, ['name' => 'Hijacked'])
            ->assertNotFound();

        $this->actingAsAdmin()
            ->deleteJson('/api/v1/warehouses/'.$factory->id)
            ->assertNotFound();

        $this->assertDatabaseHas('sites', [
            'id' => $factory->id,
            'name' => $factory->name,
            'deleted_at' => null,
        ]);
    }

    public function test_a_warehouse_is_not_reachable_through_the_factory_surface(): void
    {
        $warehouse = Site::factory()->ofKind(SiteKind::Warehouse)->create();

        $this->actingAsAdmin()
            ->getJson('/api/v1/factories/'.$warehouse->id)
            ->assertNotFound();

        $this->actingAsAdmin()
            ->deleteJson('/api/v1/factories/'.$warehouse->id)
            ->assertNotFound();

        $this->assertDatabaseHas('sites', ['id' => $warehouse->id, 'deleted_at' => null]);
    }

    /**
     * `site_type` and `capacity_pallets` describe storage. Storing them on a
     * factory would put a pallet capacity on a plant and let a capacity planner
     * book space that does not exist.
     */
    public function test_it_refuses_warehouse_only_fields_on_a_factory(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/factories', [
                'code' => 'FAC-SG2',
                'name' => 'Assembly Plant 2',
                'site_type' => 'bonded',
                'capacity_pallets' => 500,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['site_type', 'capacity_pallets']);
    }

    public function test_a_warehouse_code_is_unique_only_among_warehouses(): void
    {
        Site::factory()->ofKind(SiteKind::Factory)->create(['code' => 'SHARED']);

        // Same code, different kind: allowed, because the index is on (kind, code).
        $this->actingAsAdmin()
            ->postJson('/api/v1/warehouses', [
                'code' => 'SHARED',
                'name' => 'Same Code Warehouse',
            ])
            ->assertCreated();

        $this->actingAsAdmin()
            ->postJson('/api/v1/warehouses', [
                'code' => 'shared',
                'name' => 'Duplicate Warehouse',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    public function test_it_updates_only_the_fields_that_were_sent(): void
    {
        $site = Site::factory()->ofKind(SiteKind::Warehouse)->create([
            'name' => 'Old Name',
            'site_type' => 'bonded',
        ]);

        $this->actingAsAdmin()
            ->putJson('/api/v1/warehouses/'.$site->id, ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.site_type', 'bonded')
            ->assertJsonPath('data.kind', 'warehouse');
    }

    public function test_it_searches_across_code_name_and_city(): void
    {
        Site::factory()->ofKind(SiteKind::Warehouse)->create(['code' => 'WH-SG1', 'name' => 'Jurong']);
        Site::factory()->ofKind(SiteKind::Factory)->create(['code' => 'FAC-SG1', 'name' => 'Jurong']);

        $this->actingAsAdmin()
            ->getJson('/api/v1/warehouses?q=jurong')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'WH-SG1');
    }

    public function test_it_soft_deletes_a_warehouse(): void
    {
        $site = Site::factory()->ofKind(SiteKind::Warehouse)->create();

        $this->actingAsAdmin()
            ->deleteJson('/api/v1/warehouses/'.$site->id)
            ->assertOk();

        $this->assertSoftDeleted('sites', ['id' => $site->id]);

        $this->actingAsAdmin()
            ->getJson('/api/v1/warehouses/'.$site->id)
            ->assertNotFound();
    }
}
