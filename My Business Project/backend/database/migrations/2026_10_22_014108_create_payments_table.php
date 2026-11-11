<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('business_subscription_id')->nullable()->constrained('business_subscriptions')->nullOnDelete();
            $table->string('payment_number')->unique();
            $table->string('provider')->nullable();
            $table->string('provider_payment_id')->nullable()->index();
            $table->string('provider_transaction_id')->nullable()->index();
            $table->bigInteger('amount')->default(0); // minor units
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['pending', 'processing', 'succeeded', 'failed', 'refunded', 'canceled'])->default('pending');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'status']);
            $table->index(['provider', 'provider_payment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
