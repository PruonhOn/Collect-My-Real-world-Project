<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status', 20)->default('pending'); // pending, paid, failed, refunded
            $table->timestamps();
            $table->unique(['user_id', 'book_id']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->string('method', 30)->default('bakong'); // bakong, aba, mock
            $table->string('status', 20)->default('pending'); // pending, paid, failed
            $table->string('provider_ref')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('purchases');
    }
};
