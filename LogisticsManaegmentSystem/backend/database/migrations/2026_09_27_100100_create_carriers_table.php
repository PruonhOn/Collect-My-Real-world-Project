<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Carriers: the transport providers we buy space and capacity from.
 *
 * `scac` is the four-letter Standard Carrier Alpha Code used on bills of lading
 * and airway bills. It is nullable rather than unique because small inland
 * hauliers frequently have none.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carriers', function (Blueprint $table): void {
            $table->id();

            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('scac', 4)->nullable()->unique();

            // sea | air | road | rail | multimodal - see App\Enums\CarrierType.
            $table->string('type', 20)->nullable()->index();

            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 50)->nullable();

            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code', 30)->nullable();
            $table->string('country_code', 2)->nullable();

            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'is_active']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carriers');
    }
};
