<?php

namespace Database\Factories;

use App\Models\Carrier;
use App\Models\Driver;
use App\Models\Truck;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Driver>
 */
class DriverFactory extends Factory
{
    protected $model = Driver::class;

    /**
     * The driver's truck belongs to the driver's carrier.
     *
     * Letting both columns resolve their own `Carrier::factory()` would attach a
     * driver of one carrier to a truck owned by another, which is not a state
     * the operations screens should ever have to survive. Resolved through a
     * closure so one carrier is created per driver and shared.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'DRV-'.Str::random(6),
            'name' => fake()->name(),
            'license_number' => strtoupper(fake()->bothify('CDL-#######')),
            'phone' => fake()->numerify('+1-###-###-####'),
            'email' => fake()->unique()->safeEmail(),
            'nationality' => fake()->randomElement(['SG', 'US', 'DE', 'CN', 'NL', 'GB']),
            'carrier_id' => null,
            'truck_id' => null,
            // Far enough out that a freshly seeded driver is never reported as
            // expiring; use expiringSoon() to test that path.
            'license_expiry' => fake()->dateTimeBetween('+1 year', '+4 years')->format('Y-m-d'),
            'medical_expiry' => fake()->dateTimeBetween('+6 months', '+3 years')->format('Y-m-d'),
            'is_active' => true,
            'notes' => null,
        ];
    }

    /**
     * Assign a driver to a carrier and a matching truck, the state most tests
     * and the demo seed want.
     */
    public function assigned(Carrier|int|null $carrier = null): static
    {
        return $this->state(function (array $attributes) use ($carrier): array {
            $carrierId = $carrier instanceof Carrier ? $carrier->getKey() : $carrier;

            return [
                'carrier_id' => $carrierId ?? Carrier::factory(),
                'truck_id' => Truck::factory()->forCarrier($carrierId),
            ];
        });
    }

    /**
     * A driver inside the compliance window, so the expiring_within_days filter
     * and the report that depends on it have something to find.
     */
    public function expiringSoon(int $days = 20): static
    {
        return $this->state(fn (): array => [
            'license_expiry' => now()->addDays($days)->format('Y-m-d'),
            'medical_expiry' => now()->addDays($days + 10)->format('Y-m-d'),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'license_expiry' => now()->subDays(5)->format('Y-m-d'),
        ]);
    }

    public function unassigned(): static
    {
        return $this->state(fn (): array => ['carrier_id' => null, 'truck_id' => null]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
