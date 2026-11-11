<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * What we last heard about each consignment.
 *
 * One row per shipment, enforced by the unique index on `shipment_id`. Not a
 * milestone log: this table answers "where is it and when did we last hear",
 * which is a single current state, so a second row for the same consignment would
 * be a second answer to the same question rather than a new fact. History belongs
 * to the activity log, and an operator who needs "what did this report say last
 * Tuesday" is asking a different question from the one this screen answers.
 *
 * The foreign key cascades, which is the one place in this schema that departs
 * from the `nullOnDelete` used everywhere else, and the reason is that there is
 * nothing left to point at. A hard-deleted shipment has taken its consignment
 * with it; keeping a tracking row describing a consignment that no longer exists
 * would be a row nobody can ever resolve or delete, because this module has no
 * delete endpoint to clean it up with.
 *
 * No soft deletes either, and that follows from the permission set rather than
 * from taste: `tracking` grants `view` and `update` and nothing else, so there is
 * no code path anywhere that could remove one of these rows. Adding the column
 * would advertise a capability the API does not have.
 *
 * `last_milestone` is nullable and means "nobody has reported anything yet", which
 * is the true state of a consignment the moment it is raised and the reason the
 * stale filter is worth having.
 *
 * `revised_eta` is not a second copy of `shipments.eta`. The shipment's ETA is
 * what was planned; this is what the carrier has since promised. Overwriting the
 * plan would destroy the only record that the plan was ever wrong, which is the
 * fact an operations team is actually being paid to notice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_trackings', function (Blueprint $table): void {
            $table->id();

            // The unique index is the whole point of the table: it is what makes
            // this one-to-one rather than one-to-many, and it is what lets the
            // controller answer "the tracking for this consignment" with a lookup
            // rather than an "and if there are several, which one?" question.
            $table->foreignId('shipment_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('last_milestone', 30)->nullable()->index();
            $table->string('current_location', 160)->nullable();
            $table->timestamp('last_reported_at')->nullable()->index();
            $table->date('revised_eta')->nullable()->index();

            // A delay is recorded by giving a reason, so the two are set together
            // in practice. Neither is required: an operator told the cargo is
            // running two days behind will often know the new date and not the
            // reason, and a mandatory reason would mean inventing one.
            $table->string('delay_reason', 255)->nullable();

            /*
             * A plain string rather than an enum, which every other closed
             * vocabulary in this schema is. A carrier's own status codes are not
             * ours to fix: they arrive as "OCD - Over Container Delayed", "SHI"
             * and "PCD", and the set grows every time a new carrier is onboarded.
             * An enum would mean a migration to add a code we do not control, so
             * this records the vocabulary instead of constraining it.
             */
            $table->string('source', 50)->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
        });

        /*
         * Give every consignment that already exists a row, so the invariant holds
         * on both sides of the migration rather than only for consignments raised
         * after it.
         *
         * The model's `created` hook cannot do this: it fires on inserts, and these
         * rows are already in `shipments`. Without this step the table would be
         * quietly incomplete on an existing install - a consignment from last year
         * would have no tracking row, and the 1:1 lookup the resource relies on
         * would return null for it while still being true for everything created
         * afterwards. Nothing would error, and the gap would only show as consignments
         * silently missing from the worklists.
         *
         * The rows are blank rather than invented. A backfill must not guess a
         * milestone or a location, because a fabricated "departed Singapore" is
         * worse than nothing: it would look like a carrier had actually said so.
         * Blank is the true state, and the stale filter immediately puts these
         * consignments on the list, which is the correct thing to do with tracking
         * nobody has reported on.
         *
         * Soft-deleted consignments are included, because their rows existed before
         * the migration too and the model's hook does not distinguish them. Leaving
         * them out would mean restoring a soft-deleted consignment later finds a
         * missing row, and `tracking` has no delete endpoint to repair one with.
         */
        DB::table('shipment_trackings')->insert(
            DB::table('shipments')
                ->pluck('id')
                ->map(fn (mixed $id): array => [
                    'shipment_id' => $id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
                ->all()
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_trackings');
    }
};
