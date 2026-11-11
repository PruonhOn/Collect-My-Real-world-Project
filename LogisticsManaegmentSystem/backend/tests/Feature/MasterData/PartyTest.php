<?php

namespace Tests\Feature\MasterData;

use App\Enums\PartyType;
use App\Models\Party;

/**
 * The three party surfaces, which share a table and a controller.
 *
 * The behaviour worth pinning down is the isolation between them: a record
 * created through /shippers must never appear in /customers, and a client must
 * not be able to choose the type itself.
 */
class PartyTest extends MasterDataTestCase
{
    public function test_it_requires_a_session(): void
    {
        $this->getJson('/api/v1/customers')
            ->assertUnauthorized();
    }

    public function test_a_view_can_read_but_not_write(): void
    {
        $this->actingAsRole('viewer');

        $this->getJson('/api/v1/customers')
            ->assertOk();

        // viewer holds `*.view` but not the write half of any module.
        $this->postJson('/api/v1/customers', ['code' => 'X1', 'name' => 'Nope'])
            ->assertForbidden();
    }

    public function test_it_creates_a_customer_and_returns_the_record(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/customers', [
                'code' => 'acme',
                'name' => 'Acme Freight Ltd',
                'contact_email' => 'ops@acme.test',
                'city' => 'Singapore',
                'country_code' => 'sg',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'customer')
            // Stored upper-cased, so the code on a bill of lading matches whatever
            // the client typed.
            ->assertJsonPath('data.code', 'ACME')
            ->assertJsonPath('data.address.country_code', 'SG')
            ->assertJsonPath('data.address.city', 'Singapore')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('parties', ['code' => 'ACME', 'type' => 'customer']);
    }

    public function test_the_created_record_location_header_points_at_the_new_record(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/v1/customers', ['code' => 'ACME', 'name' => 'Acme Freight Ltd'])
            ->assertCreated();

        $id = $response->json('data.id');

        // Built from the request path, not a route name, so the shared party
        // surfaces hand back the one the caller actually posted to.
        $this->assertSame(
            '/api/v1/customers/'.$id,
            $response->headers->get('Location'),
        );

        $this->actingAsAdmin()
            ->getJson('/api/v1/customers/'.$id)
            ->assertOk();
    }

    /**
     * The surfaces share a table, so isolation has to come from the filter. If
     * this breaks, a shipper shows up in the customer dropdown and gets picked
     * by accident.
     */
    public function test_each_surface_only_lists_its_own_kind(): void
    {
        Party::factory()->ofType(PartyType::Customer)->create(['name' => 'Customer Co']);
        Party::factory()->ofType(PartyType::Shipper)->create(['name' => 'Shipper Co']);
        Party::factory()->ofType(PartyType::Consignee)->create(['name' => 'Consignee Co']);

        $this->actingAsAdmin()->getJson('/api/v1/customers')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Customer Co');

        $this->getJson('/api/v1/shippers')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Shipper Co');

        $this->getJson('/api/v1/consignees')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Consignee Co');
    }

    /**
     * `type` is not fillable, so a client cannot smuggle a different kind past
     * the route it posted to.
     */
    public function test_a_client_cannot_choose_the_type_it_creates(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/shippers', [
                'code' => 'SMUGGLED',
                'name' => 'Posted to shippers',
                'type' => 'customer',
            ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'shipper');

        $this->assertDatabaseHas('parties', ['code' => 'SMUGGLED', 'type' => 'shipper']);
        $this->assertDatabaseMissing('parties', ['code' => 'SMUGGLED', 'type' => 'customer']);
    }

    public function test_a_code_can_be_reused_across_types_but_not_within_one(): void
    {
        Party::factory()->ofType(PartyType::Customer)->create(['code' => 'DUPLICATE']);

        // Same short code, different surface: allowed, and the index is on
        // (type, code) precisely so this is expressible.
        $this->actingAsAdmin()
            ->postJson('/api/v1/shippers', ['code' => 'DUPLICATE', 'name' => 'Fine'])
            ->assertCreated();

        $this->postJson('/api/v1/customers', ['code' => 'DUPLICATE', 'name' => 'Clashes'])
            ->assertStatus(422)
            ->assertJsonPath('errors.code.0', 'The code has already been taken.');
    }

    public function test_it_updates_only_the_fields_that_were_sent(): void
    {
        $customer = Party::factory()->ofType(PartyType::Customer)->create([
            'code' => 'KEEPME',
            'name' => 'Original Name',
            'city' => 'Original City',
        ]);

        $this->actingAsAdmin()
            ->patchJson('/api/v1/customers/'.$customer->id, ['name' => 'Renamed Co'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Renamed Co')
            ->assertJsonPath('data.code', 'KEEPME')
            ->assertJsonPath('data.address.city', 'Original City');
    }

    public function test_updating_a_record_does_not_change_its_type(): void
    {
        $shipper = Party::factory()->ofType(PartyType::Shipper)->create();

        $this->actingAsAdmin()
            ->patchJson('/api/v1/shippers/'.$shipper->id, ['type' => 'customer', 'name' => 'Still A Shipper'])
            ->assertOk()
            ->assertJsonPath('data.type', 'shipper');
    }

    public function test_a_deleted_record_disappears_from_the_list_and_returns_404(): void
    {
        $customer = Party::factory()->ofType(PartyType::Customer)->create();

        $this->actingAsAdmin()
            ->deleteJson('/api/v1/customers/'.$customer->id)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->getJson('/api/v1/customers/'.$customer->id)->assertNotFound();
        $this->getJson('/api/v1/customers')->assertOk()->assertJsonCount(0, 'data');

        // Soft deleted, so the row survives for historical references.
        $this->assertSoftDeleted('parties', ['id' => $customer->id]);
    }

    public function test_a_record_from_another_surface_is_not_reachable_through_this_one(): void
    {
        $shipper = Party::factory()->ofType(PartyType::Shipper)->create();

        // /customers/7 exists in the database but is not a customer, so the
        // surface must not serve it.
        $this->actingAsAdmin()
            ->getJson('/api/v1/customers/'.$shipper->id)
            ->assertNotFound();
    }

    public function test_it_searches_across_the_useful_columns(): void
    {
        Party::factory()->ofType(PartyType::Customer)->create(['name' => 'Northern Freight']);
        Party::factory()->ofType(PartyType::Customer)->create(['name' => 'Southern Freight']);

        $this->actingAsAdmin()
            ->getJson('/api/v1/customers?q=Northern')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Northern Freight');
    }

    public function test_it_filters_by_active_state(): void
    {
        Party::factory()->ofType(PartyType::Customer)->create();
        Party::factory()->ofType(PartyType::Customer)->inactive()->create();

        $this->actingAsAdmin()
            ->getJson('/api/v1/customers?is_active=0')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.is_active', false);

        // Absent means "both", so a caller can still list everything.
        $this->getJson('/api/v1/customers')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_it_paginates_and_reports_the_totals(): void
    {
        Party::factory()->count(7)->ofType(PartyType::Customer)->create();

        $response = $this->actingAsAdmin()
            ->getJson('/api/v1/customers?per_page=3&page=2')
            ->assertOk()
            ->assertJsonCount(3, 'data');

        $this->assertSame(7, $response->json('meta.total'));
        $this->assertSame(3, $response->json('meta.last_page'));
        $this->assertSame(2, $response->json('meta.current_page'));
    }

    public function test_per_page_is_capped_so_one_request_cannot_drain_the_table(): void
    {
        Party::factory()->count(3)->ofType(PartyType::Customer)->create();

        $this->actingAsAdmin()
            ->getJson('/api/v1/customers?per_page=5000')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100);
    }

    public function test_an_unknown_sort_column_falls_back_instead_of_erroring(): void
    {
        Party::factory()->ofType(PartyType::Customer)->create();

        // Passed through unvalidated, this is a 500 and a way to probe columns.
        $this->actingAsAdmin()
            ->getJson('/api/v1/customers?sort=password')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_it_validates_the_payload(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/customers', [
                'code' => 'has spaces',
                'name' => '',
                'contact_email' => 'not-an-email',
                'country_code' => 'SGP',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code', 'name', 'contact_email', 'country_code'])
            ->assertJsonPath('success', false);
    }

    public function test_a_missing_record_reports_404_not_500(): void
    {
        $this->actingAsAdmin()
            ->getJson('/api/v1/customers/999999')
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_a_viewer_can_read_but_not_write(): void
    {
        $customer = Party::factory()->ofType(PartyType::Customer)->create();

        $this->actingAsRole('viewer');

        $this->getJson('/api/v1/customers')->assertOk();
        $this->getJson('/api/v1/customers/'.$customer->id)->assertOk();

        $this->postJson('/api/v1/customers', ['code' => 'NEW1', 'name' => 'Blocked'])
            ->assertForbidden();

        $this->patchJson('/api/v1/customers/'.$customer->id, ['name' => 'Blocked'])
            ->assertForbidden();

        $this->deleteJson('/api/v1/customers/'.$customer->id)->assertForbidden();
    }

    /**
     * Reading a shipper needs only `customer.view`, because every shipment
     * screen has to name its shipper. Requiring `shipper.view` for that would
     * leave an operator unable to read the screen they are working on.
     */
    public function test_customer_view_is_enough_to_read_a_shipper(): void
    {
        $shipper = Party::factory()->ofType(PartyType::Shipper)->create();

        $this->actingAsRole('operations-clerk')
            ->getJson('/api/v1/shippers/'.$shipper->id)
            ->assertOk();
    }
}
