<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link a shipment back to the booking it was raised from.
 *
 * The foreign key lives on `shipments` rather than on `bookings` because one
 * booking can produce several shipments. A booking is made for a quantity of
 * space, and a booking covering more than one vessel is split into a shipment
 * per sailing, so the relationship is one-to-many and this is the many side.
 * Putting the key on `bookings` would force the schema to pretend that never
 * happens.
 *
 * Nullable, and that is the part that matters. Not every shipment comes from a
 * booking: a consignment can be entered from a paper bill of lading, raised
 * directly by an operator who already holds the space, or created by a carrier
 * who booked themselves. A non-nullable column would force one of those to
 * invent a booking that nobody requested, and the bookings list would then show
 * a request that was never sent to anyone.
 *
 * `nullOnDelete` rather than cascade, matching every other reference in the
 * schema. A shipment that has already sailed must not disappear because the
 * booking record was tidied away, so the link is dropped and the shipment stays
 * exactly as it was - it simply stops pointing at a request, which is the truth
 * after the parent is gone. Soft deletes mean this only happens if something
 * force-deletes the row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table): void {
            $table->foreignId('booking_id')->nullable()->after('reference')
                ->constrained('bookings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('booking_id');
        });
    }
};
