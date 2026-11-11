<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Payment;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * A receipt against a sent invoice, part-paid.
     *
     * The invoice is `issued` with a total the payment can sit inside, and
     * `customer_id` is taken from it rather than being generated. That is the
     * denormalisation the schema exists for - a copy, not a join - and a factory
     * that invented a *second* party would produce receipts whose payer is not the
     * customer they were received from, which is precisely the state the model
     * exists to prevent.
     *
     * The invoice's balance is set to match, so a test that reads the invoice after
     * creating a payment finds a coherent ledger rather than a stale figure. It is
     * not settled, because the settlement is the controller's job and a factory
     * that called `settle()` would hide the bug the test is looking for.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory()->issued()->state(fn (): array => [
                'total' => '1000.0000',
                'paid_amount' => '0.0000',
                'balance' => '1000.0000',
            ]),

            'reference' => 'PAY-'.now()->format('Y').'-'.fake()->unique()->numerify('######'),

            'amount' => Money::of((string) fake()->randomFloat(2, 100, 400)),
            'method' => fake()->randomElement([PaymentMethod::BankTransfer, PaymentMethod::Card]),

            /*
             * In the recent past rather than "now", because a day's takings is cut by
             * the clock and a factory writing `now()` for every row makes a date
             * filter untestable - every receipt lands in the same second, so a
             * window excluding today would exclude all of them at once. A spread
             * over a few hours keeps a window filter honest.
             */
            'paid_at' => now()->subHours(fake()->numberBetween(0, 72)),

            // Present for a transfer, absent otherwise, so `needsReconciliation()`
            // has something real to be right or wrong about.
            'bank_reference' => fake()->numerify('FT#########'),

            'notes' => null,
        ];
    }

    /**
     * Against a specific invoice, with the payer copied from it.
     *
     * Takes the invoice rather than a key so the two cannot disagree, which is the
     * one invariant this table cares most about.
     */
    public function forInvoice(Invoice|Model $invoice): static
    {
        return $this->state(fn (): array => [
            'invoice_id' => $invoice->getKey(),
            'customer_id' => $invoice->getAttribute('customer_id'),
        ]);
    }

    /**
     * An exact amount, for the arithmetic tests.
     *
     * The figure is stated rather than derived from the invoice so a test can say
     * "pay 750 of a 1,000 bill and assert a 250 balance" without having to read
     * the invoice's total to find out what the half is.
     */
    public function ofAmount(string $amount): static
    {
        return $this->state(fn (): array => [
            'amount' => $amount,
        ]);
    }

    /**
     * Money going the other way: a credit note against the invoice.
     *
     * A positive amount with the `adjustment` method, which is the whole point of
     * the model's design - a refund is a receipt whose *method* says "out", never
     * a negative bank transfer. The amount stays positive so it validates under the
     * same `gt:0` rule as any other receipt, and `isReceipt()` comes back false,
     * which is what `Invoice::settle()` signs the sum by.
     */
    public function credit(): static
    {
        return $this->state(fn (): array => [
            'method' => PaymentMethod::Adjustment,
            'amount' => Money::of((string) fake()->randomFloat(2, 10, 150)),
            'bank_reference' => null,
        ]);
    }

    /**
     * A method that has to be matched to a bank statement by eye.
     *
     * The default in `definition()` is already a transfer, so this exists to be
     * explicit in a test rather than to change anything - which is the point: a
     * test about the reconciliation worklist should say what it is setting up.
     */
    public function needingReconciliation(): static
    {
        return $this->state(fn (): array => [
            'method' => PaymentMethod::BankTransfer,
            'bank_reference' => fake()->numerify('IBAN## #### #### ####'),
        ]);
    }

    /**
     * A method that carries a number to match against a statement, but one that is
     * easy to reconcile: a card payment shows up in the merchant feed.
     */
    public function card(): static
    {
        return $this->state(fn (): array => [
            'method' => PaymentMethod::Card,
            'bank_reference' => fake()->numerify('AUTH######'),
        ]);
    }

    /**
     * Counted at the counter rather than told, so it is right by evening and does
     * not need matching. The counterpart to `needingReconciliation()`, and the
     * case that makes the distinction worth having.
     */
    public function countedInCash(): static
    {
        return $this->state(fn (): array => [
            'method' => PaymentMethod::Cash,
            'bank_reference' => null,
        ]);
    }

    /**
     * Backdated to a day somebody else will be looking for it on.
     *
     * The month-end case: a payment entered on the 3rd for the 28th, which is the
     * situation a `created_at` sort would get wrong.
     */
    public function backdated(int $days = 5): static
    {
        return $this->state(fn (): array => [
            'paid_at' => now()->subDays($days),
        ]);
    }

    /**
     * Against an invoice that has been written off.
     *
     * Provided so a test can assert the *absence* of behaviour - that a void
     * invoice is not in the worklist, that its balance stays put - rather than
     * because a receipt against a void invoice is a sensible thing to create.
     */
    public function againstVoidInvoice(): static
    {
        return $this->state(fn (): array => [
            'invoice_id' => Invoice::factory()->voided(),
        ]);
    }

    /**
     * A payment big enough to take the balance negative, for the overpayment guard.
     *
     * Deliberately larger than any plausible invoice so the assertion is
     * unambiguous - the point is that the amount exceeds the balance, and a random
     * figure that happened to land under it would make the test pass for the wrong
     * reason.
     */
    public function overpaying(): static
    {
        return $this->state(fn (): array => [
            'amount' => '999999.0000',
        ]);
    }
}
