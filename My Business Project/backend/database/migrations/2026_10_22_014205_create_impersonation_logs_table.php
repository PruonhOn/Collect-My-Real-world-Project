<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('impersonation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('impersonator_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('impersonated_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('business_id')->nullable()->constrained('businesses')->nullOnDelete();
            $table->string('reason');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['impersonator_id']);
            $table->index(['impersonated_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('impersonation_logs');
    }
};
