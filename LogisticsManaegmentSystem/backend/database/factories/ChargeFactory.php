<?php

namespace Database\Factories;

use App\Enums\ChargeStatus;
use App\Enums\ChargeType;
use App\Models\Charge;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Charge>
 */
class ChargeFactory extends Factory
{
    protected $model = Charge::class;

    /**
     * A quoted line, not on an invoice yet.
     *
     * `draft` and a null `invoice_id` because that is the honest state of a charge
     * the moment it is raised, and it is the state most of them sit in: a
     * consignment's freight is priced at quote stage and does not reach a bill
     * until the work is done. A factory that started invoices would hide the
     * uninvoiced worklist, which is the screen this module is mostly opened on.
     *
     * The three totals are *not* in the definition. `Charge::booted()` writes them
     * from `quantity * unit_price` on save, so setting them here would be silently
     * overwritten - and a factory that looks like it can control them is worse
     * than one that plainly cannot. Use `total()` if a test needs the figure.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),
            'invoice_id' => null,
            'status' => ChargeStatus::Draft,
            'type' => fake()->randomElement(ChargeType::cases()),
            'description' => fake()->randomElement([
                "Ocean freight, 1 x 40' HC Singapore to Rotterdam, FAK",
                'Customs entry and clearance',
                'Documentation fee',
                'Port handling, origin',
                'Inland trucking, port to door',
                'Insurance, all risks, cargo value',
                'Terminal detention, 3 days',
            ]),
            'quantity' => fake()->randomElement(['1.0000', '1.0000', '2.0000', '0.5000', '3.0000']),
            'unit_price' => Money::of((string) fake()->randomFloat(2, 50, 4200)),
            'tax_rate' => fake()->randomElement(['0.00', '0.00', '5.00', '9.00']),

            // Copied from config rather than hard-coded, so a business running on a
            // different base currency does not get a factory that quietly writes USD
            // into a EUR column. See `logistics.finance`.
            'currency' => Money::baseCurrency(),

            'notes' => null,
        ];
    }

    /**
     * A line priced and sitting against a consignment, waiting to be billed.
     */
    public function quoted(): static
    {
        return $this->state(fn (): array => [
            'status' => ChargeStatus::Draft,
            'invoice_id' => null,
        ]);
    }

    /**
     * On an invoice, so it counts towards that bill's total.
     *
     * Takes the invoice rather than creating one, so a test can build an invoice
     * and its lines together and know which is which. The status is set to
     * `issued` to match, though `Charge::booted()` would derive it from the
     * `invoice_id` on save anyway - being explicit here means a failing test's
     * message names the status rather than the derivation.
     */
    public function onInvoice(Invoice $invoice): static
    {
        return $this->state(fn (): array => [
            'invoice_id' => $invoice->getKey(),
            'status' => ChargeStatus::Issued,
        ]);
    }

    /**
     * Withdrawn, and excluded from every total.
     */
    public function voided(): static
    {
        return $this->state(fn (): array => [
            'status' => ChargeStatus::Void,
        ]);
    }

    /**
     * Priced per unit rather than as a flat fee, for the arithmetic tests.
     *
     * A quantity and a rate are both explicit because a test asserting
     * `subtotal === 'quantity * unit_price'` needs to state them rather than
     * inherit random values it cannot predict. Zero tax as well, so the expected
     * total is the subtotal and the assertion is about the multiply.
     */
    public function priced(string $quantity, string $unitPrice, string $taxRate = '0.00'): static
    {
        return $this->state(fn (): array => [
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax_rate' => $taxRate,
        ]);
    }
}
