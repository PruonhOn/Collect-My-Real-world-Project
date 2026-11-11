<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The booking: a request for space with a carrier, and the answer that came back.
 *
 * This table exists because the request has to be recordable whether or not it
 * ever turns into cargo. A shipper asks for a sailing, the carrier either
 * confirms it or does not, and until the answer arrives there is no consignment
 * to track - only an outstanding request. Putting that state on `shipments`
 * instead would mean inventing a shipment for every booking that was turned down,
 * and the shipment list would fill with rows that never moved.
 *
 * So the booking is the parent and the shipment is what comes out of it. One
 * confirmed booking can produce several shipments - a booking is made for a
 * quantity of space, and a large one gets split across vessels - which is why
 * the foreign key lives on `shipments` rather than here.
 *
 * The cargo columns deliberately repeat the shipment's. A booking confirmation
 * is a document the carrier accepted, and what it accepted was a quantity and a
 * routing as they stood on the day it was confirmed. That is why `etd` is kept
 * separate from `actual_departure` on a shipment rather than overwritten: the
 * agreed figure is a historical fact, not a duplicate of the current one. A
 * shipment raised from this booking copies these values across at creation, and
 * the two are then free to diverge - which is correct, because a rate or a
 * sailing re-quoted after the fact does not retroactively change what was booked.
 *
 * As on shipments, every partner reference is nullable. See the shipments
 * migration for the reasoning; it is the same one, and a draft booking with no
 * carrier and no dates is the normal state of a record being created.
 *
 * There is no `is_active`. A booking is not active or inactive, it is confirmed
 * or it is not - that is what `status` records.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table): void {
            $table->id();

            /**
             * The human key.
             *
             * A booking is requested and confirmed by this reference on the
             * carrier's own paperwork, so it has the same uniqueness requirement
             * as a shipment reference. Upper-cased on write so a reference typed
             * lower-case still matches.
             */
            $table->string('reference', 30)->unique();

            $table->string('status', 20)->default('draft')->index();

            $table->foreignId('shipper_id')->nullable()
                ->constrained('parties')->nullOnDelete();
            $table->foreignId('consignee_id')->nullable()
                ->constrained('parties')->nullOnDelete();
            $table->foreignId('carrier_id')->nullable()
                ->constrained('carriers')->nullOnDelete();

            $table->foreignId('vessel_id')->nullable()
                ->constrained('vessels')->nullOnDelete();
            $table->foreignId('port_of_loading_id')->nullable()
                ->constrained('ports')->nullOnDelete();
            $table->foreignId('port_of_discharge_id')->nullable()
                ->constrained('ports')->nullOnDelete();

            $table->foreignId('origin_site_id')->nullable()
                ->constrained('sites')->nullOnDelete();
            $table->foreignId('destination_site_id')->nullable()
                ->constrained('sites')->nullOnDelete();

            $table->string('incoterm', 3)->nullable();
            $table->string('incoterm_place', 120)->nullable();

            $table->string('cargo_description', 255)->nullable();
            $table->unsignedInteger('pieces')->nullable();
            $table->unsignedInteger('weight_kg')->nullable();
            $table->decimal('volume_cbm', 10, 3)->nullable();

            /**
             * Named `requested_*` rather than `etd`/`eta` on purpose.
             *
             * These are the dates that were asked for, and they are frequently not
             * what the carrier eventually confirms. Calling them `etd` would have
             * the list showing a sailing nobody agreed to, and the revision that
             * follows every booking - the carrier moves it a week - would leave no
             * trace that the original request was ever different.
             */
            $table->date('requested_etd')->nullable()->index();
            $table->date('requested_eta')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Same three pairings as shipments: the questions an operations list
            // actually asks are "what has this shipper booked", "what is coming in
            // for this consignee" and "what is this carrier committed to".
            $table->index(['shipper_id', 'status']);
            $table->index(['consignee_id', 'status']);
            $table->index(['carrier_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
