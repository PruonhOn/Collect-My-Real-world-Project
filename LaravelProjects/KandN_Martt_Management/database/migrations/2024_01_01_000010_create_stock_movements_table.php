<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('stock_id')->nullable();
            $table->string('reference_type', 100);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->enum('type', ['in', 'out']);
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->decimal('total_price', 15, 2)->nullable();
            $table->decimal('before_quantity', 12, 2);
            $table->decimal('after_quantity', 12, 2);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('stock_id')->references('id')->on('stocks')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
