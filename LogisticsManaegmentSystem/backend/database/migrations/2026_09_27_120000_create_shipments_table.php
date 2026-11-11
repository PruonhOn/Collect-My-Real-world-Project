<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The consignment itself: what moves, for whom, on whose account, and where it
 * is.
 *
 * Every partner reference is nullable and not required, because the first thing
 * anyone does with a new shipment is save it before they have the paperwork. A
 * draft with no carrier and no dates is the normal state of a record being
 * created, not a validation error to work around. What the FKs buy is referential
 * integrity once a reference is set: a hard-deleted carrier cannot leave a
 * shipment pointing at nothing, it nulls the reference and the shipment shows up
 * in "unassigned" instead of silently disappearing from a report.
 *
 * Note there is no `booking_id` here. Bookings are their own module with their
 * own permissions and screens, and a column that can only ever be null until
 * that module exists would be a lie in the schema, so the link is added by a
 * later migration once `bookings` exists to point at. It goes on this side
 * because a booking can produce several shipments, and it is nullable because
 * plenty of shipments are raised without one. See
 * 2026_09_27_130100_add_booking_id_to_shipments_table.php.
 *
 * `is_active` is deliberately absent. A shipment is not active or inactive, it
 * is moving or it is not - that is what `status` and the lifecycle dates record.
 * Copying the flag from master data here would produce two columns disagreeing
 * about the same shipment.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table): void {
            $table->id();

            /**
             * The human key.
             *
             * Unique because a consignment is quoted, booked and invoiced by this
             * reference across several documents, so two shipments sharing one
             * would be indistinguishable on the paperwork. Upper-cased on write
             * so a reference typed lower-case still matches.
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

            $table->foreignId('truck_id')->nullable()
                ->constrained('trucks')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()
                ->constrained('drivers')->nullOnDelete();

            $table->string('incoterm', 3)->nullable();
            $table->string('incoterm_place', 120)->nullable();

            $table->string('cargo_description', 255)->nullable();
            $table->unsignedInteger('pieces')->nullable();
            $table->unsignedInteger('weight_kg')->nullable();
            $table->decimal('volume_cbm', 10, 3)->nullable();

            /**
             * Dates in pairs: what was planned and what happened.
             *
             * Kept as separate columns rather than overwriting the estimate,
             * because "this arrived nine hours late" is the fact an operator is
             * looking for, and it is destroyed the moment the actual time is
             * written over the planned one.
             */
            $table->date('etd')->nullable()->index();
            $table->date('eta')->nullable();
            $table->dateTime('actual_departure')->nullable();
            $table->dateTime('actual_arrival')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['shipper_id', 'status']);
            $table->index(['consignee_id', 'status']);
            $table->index(['carrier_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
