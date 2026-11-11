<?php

namespace Tests\Feature\MasterData;

use App\Models\Port;

/**
 * Ports, keyed by UN/LOCODE.
 */
class PortTest extends MasterDataTestCase
{
    public function test_it_requires_a_session(): void
    {
        $this->getJson('/api/v1/ports')
            ->assertUnauthorized();
    }

    public function test_it_creates_a_port_and_returns_the_record(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/ports', [
                'un_locode' => 'sgsin',
                'name' => 'Port of Singapore',
                'country_code' => 'sg',
                'port_type' => 'Container Terminal',
                'latitude' => 1.2644,
                'longitude' => 103.8200,
                'timezone' => 'Asia/Singapore',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.un_locode', 'SGSIN')
            ->assertJsonPath('data.country_code', 'SG')
            ->assertJsonPath('data.port_type', 'Container Terminal');
    }

    /**
     * Regression: case folding has to happen before the uniqueness check, or
     * "sgsin" would pass a check against a stored "SGSIN" and then fail on the
     * unique index with a 500.
     */
    public function test_a_un_locode_cannot_be_reused_in_another_case(): void
    {
        Port::factory()->create(['un_locode' => 'SGSIN']);

        $this->actingAsAdmin()
            ->postJson('/api/v1/ports', [
                'un_locode' => 'sgsin',
                'name' => 'Duplicate',
                'country_code' => 'SG',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('un_locode');
    }

    public function test_it_rejects_a_malformed_un_locode(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/ports', [
                'un_locode' => 'S1',
                'name' => 'Bad code',
                'country_code' => 'SG',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('un_locode');
    }

    public function test_a_port_may_keep_its_own_un_locode_on_update(): void
    {
        $port = Port::factory()->create(['un_locode' => 'SGSIN']);

        $this->actingAsAdmin()
            ->putJson('/api/v1/ports/'.$port->id, [
                'un_locode' => 'sgsin',
                'name' => 'Port of Singapore (renamed)',
                'country_code' => 'SG',
            ])
            ->assertOk()
            ->assertJsonPath('data.un_locode', 'SGSIN');
    }

    public function test_it_filters_by_country(): void
    {
        $singapore = Port::factory()->create(['un_locode' => 'SGSIN', 'country_code' => 'SG']);
        Port::factory()->create(['un_locode' => 'DEHAM', 'country_code' => 'DE']);

        $this->actingAsAdmin()
            // Lower-case on purpose: the filter upper-cases before matching.
            ->getJson('/api/v1/ports?country_code=de')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.un_locode', 'DEHAM');

        $this->actingAsAdmin()
            ->getJson('/api/v1/ports?country_code=SG')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $singapore->id);
    }

    public function test_it_narrows_to_active_ports(): void
    {
        $active = Port::factory()->create();
        Port::factory()->inactive()->create();

        $this->actingAsAdmin()
            ->getJson('/api/v1/ports?is_active=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $active->id);

        // Absent means "both", so a caller can still list everything.
        $this->actingAsAdmin()
            ->getJson('/api/v1/ports')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_it_soft_deletes_a_port(): void
    {
        $port = Port::factory()->create();

        $this->actingAsAdmin()
            ->deleteJson('/api/v1/ports/'.$port->id)
            ->assertOk()
            ->assertJsonPath('data', null);

        $this->assertSoftDeleted('ports', ['id' => $port->id]);

        $this->actingAsAdmin()
            ->getJson('/api/v1/ports/'.$port->id)
            ->assertNotFound();
    }
}
