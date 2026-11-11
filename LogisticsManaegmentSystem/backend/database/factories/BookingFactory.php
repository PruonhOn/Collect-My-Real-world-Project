<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Enums\Incoterm;
use App\Enums\PartyType;
use App\Models\Booking;
use App\Models\Carrier;
use App\Models\Party;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    /**
     * A booking needs a reference that is actually unique, and the parties need to
     * be the right *kind* of party - a shipper is not a customer, and a factory
     * that picked at random would produce records the API would then reject on a
     * type check.
     *
     * The routing columns are left null by default, exactly as on a shipment: a
     * request is raised before the carrier has said which vessel or which ports.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => 'BKG-'.Str::upper(Str::random(6)),
            'status' => fake()->randomElement(BookingStatus::values()),
            'shipper_id' => Party::factory()->ofType(PartyType::Shipper),
            'consignee_id' => Party::factory()->ofType(PartyType::Consignee),
            'carrier_id' => Carrier::factory(),
            'vessel_id' => null,
            'port_of_loading_id' => null,
            'port_of_discharge_id' => null,
            'origin_site_id' => null,
            'destination_site_id' => null,
            'incoterm' => fake()->randomElement(Incoterm::values()),
            'incoterm_place' => fake()->city(),
            'cargo_description' => fake()->randomElement([
                'Electronics',
                'Textiles',
                'Auto parts',
                'Frozen seafood',
                'Furniture',
                'Pharmaceuticals',
            ]),
            'pieces' => fake()->numberBetween(1, 500),
            'weight_kg' => fake()->numberBetween(50, 20000),
            'volume_cbm' => fake()->randomFloat(3, 0.5, 40),
            'requested_etd' => fake()->dateTimeBetween('+1 day', '+3 weeks')->format('Y-m-d'),
            'requested_eta' => fake()->dateTimeBetween('+2 weeks', '+6 weeks')->format('Y-m-d'),
            'notes' => null,
        ];
    }

    public function withStatus(BookingStatus $status): static
    {
        return $this->state(fn (): array => ['status' => $status]);
    }

    public function draft(): static
    {
        return $this->withStatus(BookingStatus::Draft);
    }

    /**
     * Sent to the carrier and not yet answered: somebody's follow-up.
     */
    public function requested(): static
    {
        return $this->withStatus(BookingStatus::Requested);
    }

    /**
     * Accepted by the carrier, so a shipment may be raised from it.
     */
    public function confirmed(): static
    {
        return $this->withStatus(BookingStatus::Confirmed);
    }

    /**
     * A request that was started but never sent anywhere: no carrier, no dates.
     */
    public function unassigned(): static
    {
        return $this->state(fn (): array => [
            'carrier_id' => null,
            'requested_etd' => null,
            'requested_eta' => null,
        ]);
    }

    /**
     * Asked for a sailing that has already passed with no answer.
     */
    public function overdue(): static
    {
        return $this->state(fn (): array => [
            'status' => BookingStatus::Requested,
            'requested_etd' => now()->subDays(30)->format('Y-m-d'),
            'requested_eta' => now()->subDays(3)->format('Y-m-d'),
        ]);
    }

    /**
     * Turned down, so no shipment may be raised from it.
     */
    public function cancelled(): static
    {
        return $this->withStatus(BookingStatus::Cancelled);
    }
}
