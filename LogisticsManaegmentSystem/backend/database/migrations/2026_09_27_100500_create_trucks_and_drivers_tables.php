<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trucks and drivers: the road transport fleet.
 *
 * Both hang off a carrier rather than standing alone, because a truck with no
 * carrier behind it cannot be costed - haulage rates are per carrier per lane.
 * Both nullable so a vehicle can be onboarded before it is assigned and a driver
 * before they are given a vehicle.
 *
 * `plate_number` is the natural key and is unique. `license_number` is unique
 * among non-deleted drivers, enforced in the model rather than the schema
 * because a reissued licence legitimately repeats a number that a deleted record
 * still holds.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trucks', function (Blueprint $table): void {
            $table->id();

            $table->string('plate_number', 30)->unique();
            $table->string('code', 50)->nullable();

            $table->foreignId('carrier_id')->nullable()
                ->constrained('carriers')->nullOnDelete();

            // tractor, rigid, box_truck, flatbed, reefer, tanker.
            $table->string('truck_type', 30)->nullable();
            $table->unsignedInteger('capacity_kg')->nullable();
            $table->unsignedSmallInteger('capacity_pallets')->nullable();

            $table->string('make')->nullable();
            $table->string('model')->nullable();
            $table->string('year', 4)->nullable();

            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['carrier_id', 'is_active']);
        });

        Schema::create('drivers', function (Blueprint $table): void {
            $table->id();

            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('license_number', 50)->nullable();

            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('nationality', 2)->nullable();

            $table->foreignId('carrier_id')->nullable()
                ->constrained('carriers')->nullOnDelete();
            $table->foreignId('truck_id')->nullable()
                ->constrained('trucks')->nullOnDelete();

            // Expiry drives a compliance report, so it is a date and not a note.
            $table->date('license_expiry')->nullable();
            $table->date('medical_expiry')->nullable();

            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['carrier_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('trucks');
    }
};
