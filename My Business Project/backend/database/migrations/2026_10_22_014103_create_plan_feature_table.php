<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_feature', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('feature_id')->constrained('features')->cascadeOnUpdate()->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->bigInteger('limit_value')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['plan_id', 'feature_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_feature');
    }
};
