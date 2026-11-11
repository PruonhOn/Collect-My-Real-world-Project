<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['free', 'pro'])->default('free');
            $table->bigInteger('price_monthly')->default(0); // minor units
            $table->bigInteger('price_yearly')->default(0);  // minor units
            $table->string('currency', 3)->default('USD');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->boolean('trial_enabled')->default(true);
            $table->integer('trial_days')->default(14);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
