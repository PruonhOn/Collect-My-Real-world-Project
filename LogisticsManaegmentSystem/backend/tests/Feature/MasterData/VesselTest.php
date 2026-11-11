<?php

namespace Tests\Feature\MasterData;

use App\Models\Vessel;

/**
 * Vessels, where the identity of the record is the IMO number rather than a
 * code we chose ourselves.
 */
class VesselTest extends MasterDataTestCase
{
    public function test_it_requires_a_session(): void
    {
        $this->getJson('/api/v1/vessels')
            ->assertUnauthorized();
    }

    public function test_it_creates_a_vessel_and_returns_the_record(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/vessels', [
                'name' => 'Ever Given',
                'imo_number' => '9811000',
                'mmsi' => '413123456',
                'flag_country' => 'pa',
                'vessel_type' => 'Container Ship',
                'length_meters' => 400,
                'capacity_teu' => 20124,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Ever Given')
            // IMO and MMSI are kept as strings: as integers they would lose
            // leading zeros and break the fixed-width lookups the industry uses.
            ->assertJsonPath('data.imo_number', '9811000')
            ->assertJsonPath('data.mmsi', '413123456')
            ->assertJsonPath('data.flag_country', 'PA');
    }

    public function test_it_rejects_a_malformed_imo_number(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/vessels', [
                'name' => 'Bad IMO',
                // Six digits, and leading zero: both are outside the issued range.
                'imo_number' => '0123456',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('imo_number');
    }

    public function test_an_imo_number_cannot_be_shared(): void
    {
        Vessel::factory()->create(['imo_number' => '9811000']);

        $this->actingAsAdmin()
            ->postJson('/api/v1/vessels', [
                'name' => 'Impostor',
                'imo_number' => '9811000',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('imo_number');
    }

    /**
     * Regression: the payload is trimmed before the uniqueness check.
     *
     * IMO numbers have no letter casing to fold, but clients still pad them, and
     * a uniqueness check that ran before trimming would pass " 9811000 " against
     * a stored 9811000 and then fail on the database index with a 500.
     */
    public function test_a_padded_imo_number_still_collides(): void
    {
        Vessel::factory()->create(['imo_number' => '9811000']);

        $this->actingAsAdmin()
            ->postJson('/api/v1/vessels', [
                'name' => 'Padded',
                'imo_number' => ' 9811000 ',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('imo_number');
    }

    public function test_it_updates_only_the_fields_that_were_sent(): void
    {
        $vessel = Vessel::factory()->create(['name' => 'Old Name', 'vessel_type' => 'Reefer']);

        $this->actingAsAdmin()
            ->putJson('/api/v1/vessels/'.$vessel->id, ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.vessel_type', 'Reefer');
    }

    public function test_a_vessel_may_keep_its_own_imo_number_on_update(): void
    {
        $vessel = Vessel::factory()->create(['imo_number' => '9811000']);

        $this->actingAsAdmin()
            ->putJson('/api/v1/vessels/'.$vessel->id, [
                'name' => 'Renamed',
                'imo_number' => '9811000',
            ])
            ->assertOk()
            ->assertJsonPath('data.imo_number', '9811000');
    }

    public function test_it_searches_by_name_or_imo_number(): void
    {
        $wanted = Vessel::factory()->create(['name' => 'Ever Given', 'imo_number' => '9811000']);
        Vessel::factory()->create(['name' => 'Ever Loaded', 'imo_number' => '9711000']);

        $this->actingAsAdmin()
            ->getJson('/api/v1/vessels?q=9811000')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $wanted->id);
    }

    public function test_it_sorts_on_a_whitelisted_column_only(): void
    {
        Vessel::factory()->create(['name' => 'Bravo', 'capacity_teu' => 100]);
        Vessel::factory()->create(['name' => 'Alpha', 'capacity_teu' => 9000]);

        // capacity_teu is sortable here, so the order is meaningful.
        $this->actingAsAdmin()
            ->getJson('/api/v1/vessels?sort=capacity_teu&direction=asc')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Bravo');

        // An attacker-supplied column falls back to the default instead of being
        // passed through to the query.
        $this->actingAsAdmin()
            ->getJson('/api/v1/vessels?sort=password&direction=asc')
            ->assertOk()
            ->assertJsonPath('data.0.id', Vessel::query()->orderBy('id')->first()->id);
    }

    public function test_it_soft_deletes_a_vessel(): void
    {
        $vessel = Vessel::factory()->create();

        $this->actingAsAdmin()
            ->deleteJson('/api/v1/vessels/'.$vessel->id)
            ->assertOk();

        $this->assertSoftDeleted('vessels', ['id' => $vessel->id]);

        $this->actingAsAdmin()
            ->getJson('/api/v1/vessels/'.$vessel->id)
            ->assertNotFound();
    }
}
