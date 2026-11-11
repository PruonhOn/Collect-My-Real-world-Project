<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vessels: the ships we book cargo space on.
 *
 * `imo_number` is the definitive identity of a ship and is the only field here
 * that is genuinely required - a vessel moves between names, flags and classes
 * over its life, but not between IMO numbers. `mmsi` is the radio identity and
 * is unique among live vessels, so a retired MMSI is reusable and a plain unique
 * index would reject a legitimate re-assignment.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vessels', function (Blueprint $table): void {
            $table->id();

            $table->string('name');
            $table->string('imo_number', 7)->unique();
            $table->string('mmsi', 9)->nullable()->unique();

            // ISO 3166-1 alpha-2 of the flag state.
            $table->string('flag_country', 2)->nullable();
            $table->string('vessel_type', 50)->nullable();

            $table->unsignedSmallInteger('length_meters')->nullable();
            $table->unsignedSmallInteger('beam_meters')->nullable();
            // Nominal 20-foot-equivalent capacity, the unit charter parties quote.
            $table->unsignedInteger('capacity_teu')->nullable();

            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('flag_country');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vessels');
    }
};
