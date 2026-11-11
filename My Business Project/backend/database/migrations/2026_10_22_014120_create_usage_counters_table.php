<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('key');
            $table->bigInteger('count')->default(0);
            $table->string('period')->nullable(); // e.g. 2026-10
            $table->timestamp('reset_at')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'key', 'period']);
            $table->index(['business_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_counters');
    }
};
