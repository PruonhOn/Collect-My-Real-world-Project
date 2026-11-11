<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How a consignment stands with the customs authority.
 *
 * One row per consignment per port, enforced by a unique index on
 * (`shipment_id`, `port_id`). That pairing is the whole design of this table and it
 * is worth being explicit about why it is not one row per consignment:
 *
 * - A transshipment clears at every port it touches. A Singapore-to-Rotterdam
 *   consignment that goes via Colombo has two separate customs episodes with two
 *   separate authorities, and collapsing them into one row means one of the two
 *   ports silently has no record of what happened there.
 * - A consignment refused at a port and re-presented at the same port is the harder
 *   case. A second row is the honest record; a unique index on `shipment_id` alone
 *   would forbid it, and the refusal that caused it would vanish from the system at
 *   exactly the moment somebody needs to read it.
 *
 * The alternative - an append-only log of attempts, one row per filing - was
 * rejected because this screen answers "where does this consignment stand with
 * customs", which is a current state per port. The history of attempts belongs to
 * the activity log, for the same reason ShipmentTracking is a single row rather
 * than a milestone log.
 *
 * A consignment is not required to have one of these at all. Air freight and cargo
 * that has already been cleared at origin is ordinary, and a non-nullable row would
 * mean a fake filing on a consignment nobody filed.
 *
 * The foreign key cascades, matching `shipment_trackings` and `shipment_containers`:
 * a clearance record describes one consignment's dealings with one authority, and a
 * hard-deleted consignment has taken the consignment with it. `port_id` nulls on
 * delete instead, because a port in our lookup table going away says nothing about
 * whether a clearance happened - and a cargo manifest with a hole in it is worse
 * than one naming a port that has been renamed.
 *
 * There are no money columns here. Duty assessed, duty paid and deferred duty are
 * all real and all absent, because money is the finance module's business and it
 * owns the currency handling, the decimal scale and the rounding. Adding a duty
 * amount to a customs table would mean the first arithmetic on money in the system
 * living in a module that has no reason to know about it, and the second and third
 * would follow that one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_customs_clearances', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();

            /*
             * nullable() before constrained(), and both are needed:
             * nullOnDelete() only sets the ON DELETE action, so without the nullable
             * the column stays NOT NULL and the delete is refused outright - which is
             * the exact opposite of what a removed port is supposed to do here.
             */
            $table->foreignId('port_id')->nullable()->constrained()->nullOnDelete();

            // Indexed rather than constrained: like every closed vocabulary in this
            // schema it is validated with `Rule::enum` at the boundary.
            $table->string('status', 30)->default('pending')->index();

            /*
             * The authority's own reference for the filing, not ours. Indexed because
             * "what came back on entry number 1234/2026" is asked constantly when
             * chasing a held consignment, and the operator has nothing else to quote.
             *
             * Not unique, deliberately. A customs entry number is unique within one
             * authority's jurisdiction, and our `ports` table has no jurisdiction
             * column to scope it to - so a global index would reject two entirely
             * legitimate filings that merely share a number across two countries.
             */
            $table->string('entry_number', 60)->nullable()->index();

            $table->string('broker', 120)->nullable();

            /*
             * When the filing went in and when the release came back, both as
             * timestamps rather than dates.
             *
             * A submission is an event with a time - the authority's queue is
             * timestamped to the minute and a consignment that missed the cut-off is
             * a consignment that missed the sailing - whereas the completion is a
             * document date, kept as a timestamp anyway so the two read the same way
             * in a comparison and a caller never has to wonder which is which.
             */
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('cleared_at')->nullable();

            /*
             * Set when the authority has said no, or when goods have been held for
             * examination. Required in practice with a rejection and optional with a
             * hold, so it is nullable: an operator told the goods are being examined
             * usually does not know the reference number they have quoted.
             */
            $table->string('hold_reason', 255)->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            // shipment_id first, because the subquery this serves - "the clearances
            // on this consignment" - is a prefix match on the leftmost column.
            $table->unique(['shipment_id', 'port_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_customs_clearances');
    }
};
