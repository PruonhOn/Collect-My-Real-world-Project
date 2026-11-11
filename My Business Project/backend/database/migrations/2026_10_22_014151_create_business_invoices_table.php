<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('invoice_number');
            $table->bigInteger('subtotal')->default(0);
            $table->bigInteger('tax')->default(0);
            $table->bigInteger('discount')->default(0);
            $table->bigInteger('total')->default(0);
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['draft', 'unpaid', 'paid', 'overdue', 'canceled'])->default('unpaid');
            $table->date('issue_date')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('line_items')->nullable();
            $table->boolean('is_branded')->default(false);
            $table->timestamps();

            $table->index(['business_id', 'status']);
            $table->unique(['business_id', 'invoice_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_invoices');
    }
};
