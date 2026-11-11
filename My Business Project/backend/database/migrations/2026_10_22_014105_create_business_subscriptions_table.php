<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->unique()->constrained('businesses')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnUpdate()->restrictOnDelete();
            $table->enum('status', ['trialing', 'active', 'past_due', 'canceled', 'expired', 'suspended'])->default('trialing');
            $table->enum('billing_cycle', ['monthly', 'yearly'])->nullable();
            $table->string('provider')->nullable(); // stripe, bakong_khqr, aba_payway
            $table->string('provider_subscription_id')->nullable()->index();
            $table->string('provider_customer_id')->nullable()->index();
            $table->timestamp('trial_starts_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('grace_ends_at')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'ends_at']);
            $table->index(['trial_ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_subscriptions');
    }
};
