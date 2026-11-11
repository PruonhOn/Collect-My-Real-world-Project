<?php

namespace Tests\Feature\MasterData;

use App\Enums\CarrierType;
use App\Models\Carrier;

/**
 * Carriers, the one master-data module that brings an enum and two unique codes.
 */
class CarrierTest extends MasterDataTestCase
{
    public function test_it_requires_a_session(): void
    {
        $this->getJson('/api/v1/carriers')
            ->assertUnauthorized();
    }

    public function test_it_creates_a_carrier_and_returns_the_record(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/carriers', [
                'code' => 'maeu',
                'name' => 'Maersk Line',
                'scac' => 'maeu',
                'type' => 'sea',
                'contact_email' => 'ops@maersk.test',
                'city' => 'Singapore',
                'country_code' => 'sg',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            // Both codes are upper-cased, because that is how they appear on a
            // bill of lading and in a carrier booking.
            ->assertJsonPath('data.code', 'MAEU')
            ->assertJsonPath('data.scac', 'MAEU')
            // The enum is stored as its backing value, never its PHP name.
            ->assertJsonPath('data.type', 'sea')
            ->assertJsonPath('data.address.country_code', 'SG')
            ->assertJsonPath('data.is_active', true);
    }

    public function test_it_rejects_a_type_outside_the_enum(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/carriers', [
                'code' => 'X1',
                'name' => 'Nope Ltd',
                'type' => 'teleportation',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('type');
    }

    public function test_a_carrier_code_cannot_be_reused(): void
    {
        Carrier::factory()->create(['code' => 'MAEU']);

        $this->actingAsAdmin()
            ->postJson('/api/v1/carriers', ['code' => 'MAEU', 'name' => 'Impostor'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    /**
     * SCAC is the code the industry actually trades on, so it gets its own
     * uniqueness check even though it is optional and sits beside `code`.
     */
    public function test_an_scac_cannot_be_shared_between_carriers(): void
    {
        Carrier::factory()->create(['scac' => 'MAEU']);

        $this->actingAsAdmin()
            ->postJson('/api/v1/carriers', [
                'code' => 'OTHER',
                'name' => 'Other Carrier',
                'scac' => 'maeu',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('scac');
    }

    public function test_it_updates_only_the_fields_that_were_sent(): void
    {
        $carrier = Carrier::factory()->create(['name' => 'Old Name Ltd']);

        $this->actingAsAdmin()
            ->putJson('/api/v1/carriers/'.$carrier->id, ['name' => 'New Name Ltd'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name Ltd')
            // Untouched, not nulled out.
            ->assertJsonPath('data.code', $carrier->code);
    }

    public function test_a_carrier_may_keep_its_own_scac_on_update(): void
    {
        $carrier = Carrier::factory()->create(['scac' => 'MAEU']);

        // The uniqueness rule has to exclude the record being updated, or every
        // save would collide with the row it is already occupying.
        $this->actingAsAdmin()
            ->putJson('/api/v1/carriers/'.$carrier->id, [
                'name' => 'Renamed Ltd',
                'scac' => 'MAEU',
            ])
            ->assertOk()
            ->assertJsonPath('data.scac', 'MAEU');
    }

    public function test_it_filters_by_type(): void
    {
        $sea = Carrier::factory()->ofType(CarrierType::Sea)->create(['name' => 'Sea Line']);
        Carrier::factory()->ofType(CarrierType::Road)->create(['name' => 'Road Line']);

        $this->actingAsAdmin()
            ->getJson('/api/v1/carriers?type=sea')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', $sea->name);
    }

    public function test_it_searches_across_code_name_and_scac(): void
    {
        Carrier::factory()->create(['name' => 'Maersk Line', 'code' => 'AAA111', 'scac' => 'MSKU']);
        Carrier::factory()->create(['name' => 'Kuehne Nagel', 'code' => 'BBB222', 'scac' => 'KNHL']);

        $this->actingAsAdmin()
            ->getJson('/api/v1/carriers?q=msku')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'AAA111');
    }

    public function test_it_paginates_and_reports_the_totals(): void
    {
        Carrier::factory()->count(7)->create();

        $this->actingAsAdmin()
            ->getJson('/api/v1/carriers?per_page=5')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 7)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.has_more_pages', true);
    }

    public function test_it_soft_deletes_a_carrier(): void
    {
        $carrier = Carrier::factory()->create();

        $this->actingAsAdmin()
            ->deleteJson('/api/v1/carriers/'.$carrier->id)
            ->assertOk()
            ->assertJsonPath('data', null);

        $this->assertSoftDeleted('carriers', ['id' => $carrier->id]);

        // Gone from the API, but the row survives for historical references.
        $this->actingAsAdmin()
            ->getJson('/api/v1/carriers/'.$carrier->id)
            ->assertNotFound();
    }

    public function test_it_rejects_an_invalid_payload(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/carriers', [
                'code' => '',
                'name' => '',
                'scac' => 'TOOLONG',
                'contact_email' => 'not-an-email',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code', 'name', 'scac', 'contact_email']);
    }
}
