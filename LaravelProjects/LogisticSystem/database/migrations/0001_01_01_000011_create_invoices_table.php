<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->string('type')->default('invoice')->comment('invoice, receipt, credit_note, debit_note');
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 15, 2);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0);
            $table->string('currency')->default('USD');
            $table->decimal('exchange_rate', 10, 4)->default(1.0000)->comment('USD to KHR');
            $table->string('status')->default('unpaid')->comment('paid, unpaid, partial, cancelled, overdue');
            $table->text('notes')->nullable();
            $table->string('pdf_path')->nullable();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('shipment_id')->nullable()->constrained();
            $table->foreignId('branch_id')->constrained();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['invoice_number', 'status']);
            $table->index(['invoice_date', 'status']);
            $table->index('customer_id');
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->string('type')->comment('shipping, handling, storage, customs, insurance, other');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total', 15, 2);
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 50)->unique();
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->string('payment_method')->comment('cash, bank_transfer, cheque, credit_card, other');
            $table->string('currency')->default('USD');
            $table->decimal('exchange_rate', 10, 4)->default(1.0000);
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['payment_date', 'payment_method']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
