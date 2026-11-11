<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('category', 100);
            $table->date('expense_date');
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->string('reference_no', 100)->nullable();
            $table->string('payment_method', 30);
            $table->string('status', 20)->default('paid');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
