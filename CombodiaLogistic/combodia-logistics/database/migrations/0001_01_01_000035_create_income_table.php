<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('income', function (Blueprint $table) {
            $table->id();
            $table->foreignId('income_category_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->string('income_number')->unique();
            $table->text('description');
            $table->decimal('amount', 12, 2);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->date('income_date');
            $table->string('reference_number', 100)->nullable();
            $table->string('receipt_path', 500)->nullable();
            $table->enum('status', ['pending', 'received', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('income_category_id');
            $table->index('customer_id');
            $table->index('branch_id');
            $table->index('income_number');
            $table->index('status');
            $table->index('income_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('income');
    }
};
