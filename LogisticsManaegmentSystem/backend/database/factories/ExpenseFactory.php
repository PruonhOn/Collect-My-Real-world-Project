<?php

namespace Database\Factories;

use App\Enums\ExpenseCategory;
use App\Models\Carrier;
use App\Models\Expense;
use App\Models\Shipment;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    /**
     * A carrier's cost, attached to a consignment.
     *
     * The default state of most rows in this table: a forwarder pays a carrier for
     * the carriage, and that bill belongs to a specific consignment. A consignment
     * and a carrier are both created, because "what did this ship cost us" is the
     * question the table exists to answer and it needs both ends to be answerable.
     *
     * `duty` and `tax` are not in the random pool for `category`. Those are the one
     * category that is held money rather than a cost, and a fixture that rolled
     * them at random would make a margin total come out wrong for a reason that has
     * nothing to do with the code under test. `overhead()` is where they belong.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => 'EXP-'.now()->format('Y').'-'.fake()->unique()->numerify('######'),

            'category' => fake()->randomElement([
                ExpenseCategory::Freight,
                ExpenseCategory::Brokerage,
                ExpenseCategory::Handling,
                ExpenseCategory::Storage,
                ExpenseCategory::Demurrage,
                ExpenseCategory::Insurance,
                ExpenseCategory::Fuel,
            ]),

            'shipment_id' => Shipment::factory(),
            'booking_id' => null,
            'carrier_id' => Carrier::factory(),

            'vendor_name' => fake()->company().' Logistics',
            'vendor_reference' => fake()->numerify('STMT-#####'),
            'amount' => Money::of((string) fake()->randomFloat(2, 45, 5200)),
            'currency' => Money::baseCurrency(),

            // In the recent past, spread over days rather than stamped at `now()`,
            // so a month window actually has rows on both sides of it.
            'incurred_on' => now()->subDays(fake()->numberBetween(0, 20))->toDateString(),

            'is_billable' => fake()->boolean(35),
            'description' => fake()->randomElement([
                'Ocean freight, carrier invoice',
                'Customs broker fee',
                'Terminal handling at origin',
                'Container detention, days 4-6',
                'Cargo insurance premium',
                'Fuel surcharge',
            ]),
            'notes' => null,
        ];
    }

    /**
     * Duty and tax: money collected from the customer and remitted, not spent.
     *
     * The case `countsAgainstMargin()` and `marginAmount()` exist for. `is_billable`
     * is forced false even when a state asks for it, because duty is never a cost
     * the business recharges - it is owed to an authority - and the model's
     * `isBillable()` enforces that at read time. A factory that let it be true
     * would let a test assert a margin figure that includes tax, which is the
     * specific misreading the two figures exist to prevent.
     */
    public function dutyAndTax(): static
    {
        return $this->state(fn (): array => [
            'category' => ExpenseCategory::Duty,
            'is_billable' => false,
            'description' => 'Import duty and GST, remitted to customs',
        ]);
    }

    /**
     * An operating cost with nothing behind it: rent, a licence, a salary.
     *
     * All three references null, which is the point - the general running costs of
     * a business belong to no consignment, and requiring a reference would make
     * them impossible to record at all. `unattributed()` in the controller filters
     * for exactly this shape.
     */
    public function overhead(): static
    {
        return $this->state(fn (): array => [
            'category' => ExpenseCategory::Overhead,
            'shipment_id' => null,
            'carrier_id' => null,
            'booking_id' => null,
            'is_billable' => false,
            'description' => fake()->randomElement([
                'Office rent, quarterly',
                'Customs software licence',
                'Professional indemnity insurance',
                'Warehouse lease',
            ]),
        ]);
    }

    /**
     * Staff cost, also unattributable to any one job.
     */
    public function payroll(): static
    {
        return $this->state(fn (): array => [
            'category' => ExpenseCategory::Payroll,
            'shipment_id' => null,
            'carrier_id' => null,
            'booking_id' => null,
            'is_billable' => false,
            'description' => fake()->randomElement([
                'Operations staff, monthly',
                'Night shift premium',
                'Overtime, peak week',
            ]),
        ]);
    }

    /**
     * A cost of doing the deal, before any cargo existed.
     *
     * `shipment_id` null with a booking attached, which is the case a booking-level
     * charge is the only column that can express: a demurrage-free extension bought
     * up front, or a cancellation fee. Attaching these to a consignment instead
     * would make them invisible on every booking that never became one.
     */
    public function bookingCost(): static
    {
        return $this->state(fn (): array => [
            'shipment_id' => null,
            'carrier_id' => null,
            'category' => fake()->randomElement([
                ExpenseCategory::Brokerage,
                ExpenseCategory::Handling,
                ExpenseCategory::Demurrage,
            ]),
            'description' => fake()->randomElement([
                'Booking amendment fee',
                'Cancellation fee',
                'Demurrage-free time extension, 7 days',
                'Equipment repositioning',
            ]),
        ]);
    }

    /**
     * Marked as intended for recharging to the customer.
     *
     * A statement of intent, not a charge: nothing here creates one, because what
     * gets recharged and at what markup is a decision somebody makes.
     */
    public function billable(): static
    {
        return $this->state(fn (): array => [
            'is_billable' => true,
        ]);
    }

    /**
     * An exact figure, for the arithmetic tests.
     */
    public function ofAmount(string $amount): static
    {
        return $this->state(fn (): array => [
            'amount' => $amount,
        ]);
    }

    /**
     * Incurred on a particular day, for the window and month-end tests.
     */
    public function incurredOn(string $date): static
    {
        return $this->state(fn (): array => [
            'incurred_on' => $date,
        ]);
    }
}
