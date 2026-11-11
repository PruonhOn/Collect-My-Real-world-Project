<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 50)->unique();
            $table->date('transaction_date');
            $table->string('type')->comment('income, expense');
            $table->string('category')->comment('shipping_revenue, service_revenue, fuel, maintenance, salary, warehouse_cost, utilities, other');
            $table->decimal('amount', 15, 2);
            $table->string('currency')->default('USD');
            $table->string('payment_method')->nullable();
            $table->text('description');
            $table->string('reference')->nullable();
            $table->string('status')->default('posted')->comment('pending, posted, void');
            $table->foreignId('branch_id')->constrained();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['transaction_date', 'type', 'category']);
            $table->index('branch_id');
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_number', 50)->unique();
            $table->date('expense_date');
            $table->string('category')->comment('fuel, maintenance, salary, warehouse, utilities, office, travel, other');
            $table->decimal('amount', 15, 2);
            $table->string('currency')->default('USD');
            $table->text('description');
            $table->string('vendor')->nullable();
            $table->string('receipt_path')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->string('status')->default('approved')->comment('pending, approved, rejected');
            $table->foreignId('branch_id')->constrained();
            $table->foreignId('vehicle_id')->nullable()->constrained();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['expense_date', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('transactions');
    }
};
