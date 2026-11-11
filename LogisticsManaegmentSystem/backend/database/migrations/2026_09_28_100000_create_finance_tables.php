<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The settlement chain: charges on a consignment, invoices to a customer,
 * payments against an invoice, and what the business paid out.
 *
 * The four tables are created here, in dependency order, because the links run
 * one way and a forwarder chasing an unpaid bill walks them in that order: what
 * did we do for this consignment, what did we bill, what came back, and what did
 * it cost us. `invoices` precedes `charges` even though the charges are the older
 * idea, because a charge carries a nullable `invoice_id` and the column has to
 * exist before the table referencing it is created.
 *
 * Every amount is `decimal(15,4)`. The scale is `config('logistics.finance.scale')`
 * and it is not decorative: a rate card quotes ocean freight at four decimals per
 * container, and storing that at two would round 0.0875 to 0.09 - a 2.9% error on
 * the single largest line on most invoices. The tax on an unrounded net is then
 * exact, rather than a per-line approximation of the 5% the business was quoted.
 *
 * `charges` stores its own subtotal, tax and total rather than the controller
 * computing them on read, for one reason: an invoice total has to be the sum of
 * the lines that were on the invoice *at the time it was sent*. Recomputing from
 * quantity and unit price later would silently restate a historical invoice the
 * moment somebody corrected a line's description, and the customer is holding a
 * copy of the original.
 */
return new class extends Migration
{
    public function up(): void
    {
        /*
         * Invoices first, and standalone: a customer can be invoiced for a
         * consignment, for several consignments, or - for a monthly retainer or a
         * standing storage arrangement - for work that never appeared as a
         * charge against a particular consignment at all. The lines table below
         * is therefore optional from the invoice's side, and the invoice's own
         * totals are stored so it means something with no lines attached.
         */
        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();

            /*
             * The party being billed. Nulls on delete is wrong here and is the one
             * place in this schema where the master-data convention is reversed:
             * every other nullable reference is a piece of equipment or a place
             * that can be retired from a file. A customer cannot - and if one is
             * deleted while invoices are outstanding, the receivable is the whole
             * reason the business still exists. The constraint below is deliberate.
             */
            $table->foreignId('customer_id')->constrained('parties')->restrictOnDelete();

            /*
             * A document number we issue, in the `INV-2026-000001` form from
             * `logistics.numbering.invoice`, and globally unique. Supplied by the
             * client rather than generated here for the same reason a delivery
             * reference is: it is written on the document the customer holds, so
             * the person raising the invoice has to be able to use the number their
             * accounting system already expects.
             */
            $table->string('reference', 30)->unique();

            // Indexed rather than constrained, like every other closed vocabulary
            // here: validated with `Rule::enum` at the boundary.
            $table->string('status', 30)->default('draft')->index();

            /*
             * The two dates that define whether this is late.
             *
             * Both are dates and not timestamps. An invoice is dated, not stamped:
             * "issued on the 3rd" is the fact a customer cites, and a time on it
             * would only ever be the time somebody happened to click save. The
             * late test is therefore `due_date < today`, which is a comparison of
             * days and is not shifted by the hour the request arrives.
             */
            $table->date('issue_date')->nullable();
            $table->date('due_date')->nullable()->index();

            /*
             * ISO 4217, three letters. Stored per row rather than assumed from
             * config, because a forwarder bills a foreign principal in their own
             * currency and a report that silently added a USD invoice to a EUR
             * column is a number nobody can act on. Nothing converts yet - see
             * `App\Support\Money` - so every row written by this system carries
             * the base currency, and this column exists so that is a fact rather
             * than an accident.
             */
            $table->string('currency', 3)->default('USD');

            /*
             * The three totals, in the order they are worked out.
             *
             * `subtotal` is the sum of the lines' net amounts, `tax_amount` the
             * sum of their tax, and `total` the two added together. A discount is
             * a line-level concept - a goodwill reduction is expressed as a
             * negative charge - rather than a column here, so that a discounted
             * invoice can still be checked by adding up its lines. A total that
             * cannot be reproduced from the lines on it is a total nobody can
             * argue with a customer about.
             *
             * `paid_amount` and `balance` are server-owned: they are the sum of
             * the payments and the difference, written by the payment controller
             * and never accepted from a request. Storing them rather than deriving
             * them on read is what lets the overdue worklist be a plain indexed
             * comparison on `balance` and `due_date` instead of a correlated
             * subquery per row.
             */
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('total', 15, 4)->default(0);
            $table->decimal('paid_amount', 15, 4)->default(0);
            $table->decimal('balance', 15, 4)->default(0);

            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('charges', function (Blueprint $table): void {
            $table->id();

            /*
             * What the charge is for. Required, unlike most of this schema's
             * references: a charge that is not against a consignment has no
             * carrier, no route and no volume to justify it, so it would be a
             * figure with nothing behind it. Work that genuinely has no consignment
             * - a monthly retainer, a storage standing fee - is billed straight
             * onto an invoice as a line, which is why `invoices` above can stand
             * on its own.
             *
             * Cascades: a consignment and the charges raised against it are the
             * same piece of work, and a consignment deleted in error should not
             * leave invoices totalling money for cargo that no longer exists.
             */
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();

            /*
             * The invoice this line is on, or null while it is still being quoted.
             *
             * Nulls on delete rather than cascades, and this is the case where
             * the usual "do not lose the history" reasoning does not apply. An
             * invoice is voided or deleted only while it is still a draft, and
             * deleting a draft invoice has to leave its lines behind as drafts
             * rather than delete them: they are quoted work on a live consignment
             * and are owed money whichever piece of paper happens to be attached
             * to them. Cascading here would quietly un-bill a consignment.
             */
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();

            $table->string('status', 30)->default('draft')->index();

            /*
             * What is being charged for, and what for.
             *
             * The type is the priced category - freight, demurrage, brokerage -
             * and it drives nothing arithmetically, but it is what makes a
             * consignment's margin readable afterwards. The description is free
             * text because this is the line the customer reads: "Ocean freight,
             * 1 x 40' HC Singapore to Rotterdam, FAK" is worth writing out even
             * though `freight` plus the consignment reference already implies it,
             * because the invoice is the document that gets queried in six months.
             */
            $table->string('type', 40)->default('other')->index();
            $table->string('description', 255);

            /*
             * How much, at what price.
             *
             * `quantity` is decimal(15,4) and not an integer because the commonest
             * freight unit is not a box - it is a tonne, a cubic metre, a pallet,
             * a per-cent-kilometre, or a day of demurrage, and rounding a rate
             * card's 0.5 tonne to 1 would overbill by a hundred percent. One
             * priced line covers all of them rather than the schema carrying a
             * unit column that would then have to agree with the type.
             *
             * Both default to 1 and 0 rather than being required, so the simplest
             * charge in the business - a flat documentation fee of 75 - is two
             * fields and no arithmetic.
             */
            $table->decimal('quantity', 15, 4)->default(1);
            $table->decimal('unit_price', 15, 4)->default(0);

            /*
             * A percentage, not a fraction: 5 means five percent.
             *
             * `decimal(5,2)` because the largest rate anyone applies is 27% in most
             * jurisdictions and 100 is a generous ceiling, and the scale is two
             * because tax rates are quoted in whole or half points. Storing it this
             * way is why a form field says "5" and not "0.05" - the number a person
             * reads off a rate card is the number stored.
             */
            $table->decimal('tax_rate', 5, 2)->default(0);

            /*
             * What the line adds up to, kept in three parts.
             *
             * `subtotal` is quantity times unit price, `tax_amount` is the tax on
             * that, and `total` is the two added. All three are stored rather than
             * composed at read time, so an issued invoice's total cannot change
             * because somebody later edited a unit price. They are written by the
             * model on save, from `App\Support\Money`, and are not accepted from a
             * request.
             */
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('total', 15, 4)->default(0);

            // Matches the invoice's, and copied onto the line rather than joined
            // for, so a consignment's billed total can be aggregated from
            // `charges` alone without carrying the invoice's currency through
            // every row of the query.
            $table->string('currency', 3)->default('USD');

            $table->text('notes')->nullable();
            $table->timestamps();

            /*
             * The index the receivables worklist is actually served from: "every
             * line still on this invoice", for the invoice total recomputation
             * and for the charge list filtered by invoice.
             */
            $table->index(['invoice_id', 'status']);
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();

            /*
             * The invoice being paid.
             *
             * Required, and the reason the payments module is a genuine extension
             * of the invoice rather than a standalone ledger: money received in a
             * freight business is always received *against something*, and a
             * payment with no invoice would be an unallocated receipt, which is a
             * different problem with a different screen. Cascades for the same
             * reason charges do - an invoice deleted in error and its receipts
             * should both disappear, because a payment for a consignment that no
             * longer exists is not history, it is an orphan.
             */
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();

            /*
             * Who paid, denormalised from the invoice.
             *
             * This is the one place in the schema where a customer id is copied
             * rather than followed, and the reason is the report: "receipts by
             * customer this month" is asked far more often than it is worth a join
             * for, and a receipt's payer should not change if the invoice's is
             * corrected later - the payment is a historical fact about a
             * transaction, and a fact that silently follows a later edit is not a
             * fact. Written by the payment controller from the invoice, never from
             * the request.
             */
            $table->foreignId('customer_id')->constrained('parties')->restrictOnDelete();

            // Our own receipt number, `PAY-2026-000001`, and unique for the same
            // reason an invoice number is: it identifies the row, and the same
            // client's bank reference does not.
            $table->string('reference', 30)->unique();

            /*
             * How much came in, and how it arrived.
             *
             * `amount` is unsigned in practice rather than by constraint, because
             * the arithmetic that makes it so - "this takes the balance to zero or
             * leaves something owing" - is the controller's and needs to produce a
             * 422 that names the field, not a database error. A credit is
             * `PaymentMethod::Adjustment`, never a negative amount: a negative
             * bank transfer is a confusing thing to find in a reconciliation.
             */
            $table->decimal('amount', 15, 4);
            $table->string('method', 30)->default('bank_transfer')->index();

            /*
             * When the money arrived, and what it said on the way.
             *
             * A timestamp and not a date, because a payment posted in the evening
             * batch and one posted at the counter are different things for a bank
             * reconciliation even when they are the same day, and a day's takings
             * is cut by time. The bank's own reference is the string a clerk
             * matches by eye against a statement line - the narrative on a
             * transfer, the number on a cheque - and it is the only field that can
             * answer "was this really our money".
             */
            $table->timestamp('paid_at')->index();
            $table->string('bank_reference', 120)->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table): void {
            $table->id();

            /*
             * What it was for, numbered by us, `EXP-2026-000001`.
             *
             * Unique, and the opposite of the container and customs composite keys
             * for the same reason a delivery reference is unique: this is a
             * document number we issue for a payment we made, so it identifies the
             * row. The supplier's own invoice number goes in `vendor_reference`,
             * unindexed and not unique, because a supplier reuses theirs.
             */
            $table->string('reference', 30)->unique();

            $table->string('category', 40)->default('other')->index();

            /*
             * What the cost was attached to, all three optional.
             *
             * A consignment when the cost is that consignment's, a booking when it
             * is a cost of doing the deal that was not spent on a specific
             * consignment - a booking amendment fee, a cancellation fee, a
             * demurrage-free-time extension bought before the cargo was even
             * booked - and a carrier when it is somebody else's invoice, which is
             * the common case and the one that makes a consignment's true cost
             * computable after the fact.
             *
             * All three null rather than required because the general running costs
             * of a forwarder are not attached to any of them: an office's rent, a
             * licence, a salary. Those are `category` `overhead` and `payroll`, and
             * requiring a consignment would make it impossible to record the costs
             * of running the business at all.
             *
             * Null on delete for all three, matching the master-data tables: a
             * carrier leaving the fleet does not un-pay for the freight they
             * carried, and a consignment being archived should not take the record
             * of what it cost with it. The nullable result is an expense whose
             * subject is gone, which is a fact and not a gap.
             */
            $table->foreignId('shipment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('carrier_id')->nullable()->constrained()->nullOnDelete();

            /*
             * What was paid and to whom.
             *
             * `vendor_reference` is the supplier's own invoice number and is not
             * unique, because a monthly statement from a carrier carries the same
             * number on a dozen lines and making it unique would mean inventing
             * numbers. It is the field that is matched against a statement line,
             * so it is indexed for the search rather than for uniqueness.
             */
            $table->string('vendor_name', 255)->nullable();
            $table->string('vendor_reference', 120)->nullable()->index();

            /*
             * The amount, and the day it was incurred.
             *
             * A date and not a timestamp, unlike a payment. An expense is what a
             * statement says happened on a day - a carrier's demurrage invoice is
             * dated the day the terminal charged it, which is not the day it was
             * typed into this system and may not even be the day the money left.
             * A month-end margin report is read by the day the cost belongs to, and
             * a timestamp would move a cost between months depending on when
             * somebody got round to entering it.
             *
             * `currency` mirrors the charge table for the same reason: a cost paid
             * in another currency is a cost, and a total that silently mixes them
             * is a number nobody can act on.
             */
            $table->decimal('amount', 15, 4);
            $table->string('currency', 3)->default('USD');
            $table->date('incurred_on')->index();

            /*
             * Whether this cost should be pushed on to the customer.
             *
             * The field that makes an expense report useful rather than a list of
             * outgoings: a terminal detention fee is a real cost and is also
             * usually recharged, and knowing which is which is how a consignment's
             * margin is worked out. It is a statement of intent, not an
             * instruction - nothing here creates a charge automatically, because
             * what gets recharged and at what markup is a decision, not a formula.
             */
            $table->boolean('is_billable')->default(false);

            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            /*
             * "What did this consignment cost us", which is the query the whole
             * table exists to answer and which is otherwise a per-consignment
             * aggregate over three nullable columns.
             */
            $table->index(['shipment_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('charges');
        Schema::dropIfExists('invoices');
    }
};
