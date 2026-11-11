<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Enums\PartyType;
use App\Models\Invoice;
use App\Models\Party;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /**
     * A draft bill, totalling nothing.
     *
     * All five amounts zero and a `draft` status, because that is the state a new
     * invoice is in and the one the finance screens are opened on. A factory that
     * started a paid invoice would make every list look settled.
     *
     * The totals are zero rather than random even though they are stored columns,
     * because they are not independent: they are the sum of the charges, and the
     * payment and balance figures are the sum of the payments. A factory that
     * invented a `total` would produce an invoice whose lines do not add up to it -
     * the exact contradiction the whole settlement chain is arranged to prevent.
     * Use `withCharges()` and `settled()` to build a populated one.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            /*
             * `ofType()` rather than a bare `Party::factory()`. The party factory
             * already defaults to `customer`, so this is technically redundant -
             * but the invoice's rules are the strictest place the type matters
             * (`where('type', 'customer')`), and a factory that only happens to
             * satisfy them by a default in another file is one edit away from
             * producing invoices this factory's own model cannot create.
             */
            'customer_id' => Party::factory()->ofType(PartyType::Customer),

            // Unique because the column is: `INV-2026-000001` per
            // `logistics.numbering.invoice`, and still client-supplied in the API.
            'reference' => 'INV-'.now()->format('Y').'-'.fake()->unique()->numerify('######'),

            'status' => InvoiceStatus::Draft,
            'issue_date' => null,
            'due_date' => null,
            'currency' => Money::baseCurrency(),

            'subtotal' => '0.0000',
            'tax_amount' => '0.0000',
            'total' => '0.0000',
            'paid_amount' => '0.0000',
            'balance' => '0.0000',

            'notes' => null,
        ];
    }

    /**
     * Sent, with the clock started and a date to pay by.
     *
     * Thirty days is `logistics.operations.default_payment_terms_days` rather
     * than a literal, because a factory that hard-coded it would drift from the
     * business's actual terms the first time somebody changed the config. It
     * lives under `operations` rather than `finance` because it is a customer
     * term with an operational consequence - how long to hold a consignment
     * before chasing - and not a money rule.
     *
     * Note the issue date is set and the status is `issued`, so `isOverdue()` is
     * false for the whole of the term - this state is *not* the alarm, and a test
     * for the alarm should use `overdue()`.
     */
    public function issued(): static
    {
        return $this->state(fn (): array => [
            'status' => InvoiceStatus::Issued,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays((int) config('logistics.operations.default_payment_terms_days', 30))->toDateString(),
        ]);
    }

    /**
     * Issued, and past due with money still owing: the receivables worklist.
     *
     * The total and balance are set explicitly, and that is not decoration. A row
     * that is `issued` with a zero balance is a *settled* invoice as far as every
     * query is concerned - `isOutstanding()` is a strict positive and
     * `scopeOverdue()` filters on `balance > 0` - so a factory that left the
     * definition's zeros in place would build something called `overdue` that no
     * overdue filter would ever return, and the test asserting on the worklist
     * would fail for a reason that has nothing to do with the worklist.
     *
     * The window is at least a day in the past so the row is unambiguously on the
     * late side of the line when the suite runs slowly, which is the same
     * reasoning `ShipmentDeliveryFactory::overdue()` uses.
     */
    public function overdue(): static
    {
        return $this->issued()->state(fn (): array => [
            'total' => '1000.0000',
            'paid_amount' => '0.0000',
            'balance' => '1000.0000',
            'due_date' => now()->subDays(fake()->numberBetween(1, 45))->toDateString(),
        ]);
    }

    /**
     * Issued, with some money received and some still owing.
     *
     * `partially_paid` is set directly rather than left to `settle()`, so the state
     * can be built without a payment row - useful for a listing or filter test
     * where the payments are not the thing under examination. A test about the
     * derivation itself should create a payment and call `settle()`.
     */
    public function partiallyPaid(): static
    {
        return $this->issued()->state(fn (): array => [
            'status' => InvoiceStatus::PartiallyPaid,
            'total' => '1000.0000',
            'paid_amount' => '250.0000',
            'balance' => '750.0000',
        ]);
    }

    /**
     * Issued, with a month or more to pay.
     *
     * The case `isOverdue()` must return false for even though nothing is
     * outstanding yet having been sent, and the case a naive
     * `due_date < now()` comparison gets wrong for the whole of the due date.
     */
    public function collectable(): static
    {
        return $this->issued();
    }

    /**
     * Withdrawn. Never overdue and never collectable, whatever the dates say.
     */
    public function voided(): static
    {
        return $this->issued()->state(fn (): array => [
            'status' => InvoiceStatus::Void,
        ]);
    }

    /**
     * Billed to a specific party rather than a new one.
     */
    public function forCustomer(Party $customer): static
    {
        return $this->state(fn (): array => [
            'customer_id' => $customer->getKey(),
        ]);
    }
}
