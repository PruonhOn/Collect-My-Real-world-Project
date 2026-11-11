<?php

namespace Tests\Feature\Finance;

use App\Models\Invoice;
use Tests\Feature\MasterData\MasterDataTestCase;

/**
 * Shared setup for the four finance module tests.
 *
 * Extends `MasterDataTestCase` rather than duplicating it: the permission graph and
 * the `actingAsRole()` helper are the same for every module in this API, and the
 * finance roles are in the same catalogue as the rest. The only thing added here
 * is the helper the settlement tests all need, because building an invoice with a
 * known total by hand is otherwise four lines repeated in every test.
 */
abstract class FinanceTestCase extends MasterDataTestCase
{
    /**
     * A settled invoice carrying an exact total, built without going through the API.
     *
     * `settle()` is called explicitly rather than relying on the factory's stored
     * figures, so the returned record is one the server would have produced. Tests
     * about what a *write* does to a balance are then asserting against a starting
     * state that is itself correct, rather than against a fixture whose numbers
     * happen to line up.
     */
    protected function invoiceFor(string $total, ?string $dueDate = null): Invoice
    {
        $invoice = Invoice::factory()->issued()->create([
            'total' => $total,
            'due_date' => $dueDate ?? now()->addDays(30)->toDateString(),
        ]);

        $invoice->settle()->save();

        return $invoice->refresh();
    }
}
