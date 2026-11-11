<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained();
            $table->foreignId('brand_id')->nullable()->constrained();
            $table->foreignId('supplier_id')->nullable()->constrained();
            $table->string('name', 200);
            $table->string('slug', 200)->unique();
            $table->string('sku', 50)->unique();
            $table->string('barcode', 50)->nullable()->unique();
            $table->text('description')->nullable();
            $table->decimal('cost_price', 12, 2);
            $table->decimal('selling_price', 12, 2);
            $table->integer('stock_quantity')->default(0);
            $table->integer('min_stock')->default(5);
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
