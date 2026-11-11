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
            $table->foreignId('student_fee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('parents')->nullOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('method', 50)->default('manual');
            $table->string('gateway', 50)->nullable();
            $table->string('transaction_id', 100)->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('paid_on')->nullable();
            $table->string('notes', 191)->nullable();
            $table->timestamps();

            $table->index(['status', 'paid_on']);
            $table->index(['student_id', 'paid_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
