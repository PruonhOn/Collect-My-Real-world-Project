<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ports: the load, discharge and transhipment points shipments move between.
 *
 * `un_locode` is the UN/LOCODE - two letters of country plus two of location,
 * e.g. SGSIN - and is the identifier every carrier quotation, bill of lading
 * and customs declaration is keyed on. It is stored uppercase and unique,
 * because two rows differing only in case are the same port.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ports', function (Blueprint $table): void {
            $table->id();

            $table->string('un_locode', 10)->unique();
            $table->string('name');

            // ISO 3166-1 alpha-2.
            $table->string('country_code', 2)->index();
            $table->string('port_type', 50)->nullable();

            $table->decimal('latitude', 10, 6)->nullable();
            $table->decimal('longitude', 10, 6)->nullable();

            $table->string('timezone', 64)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index(['country_code', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ports');
    }
};
