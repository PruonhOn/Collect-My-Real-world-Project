<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->unique();
            $table->string('warehouse_location', 100)->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->decimal('reserved_quantity', 12, 2)->default(0);
            $table->decimal('min_quantity', 12, 2)->default(0);
            $table->decimal('max_quantity', 12, 2)->default(0);
            $table->decimal('reorder_level', 12, 2)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
