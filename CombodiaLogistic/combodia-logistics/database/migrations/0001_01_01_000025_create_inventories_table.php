<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->foreignId('warehouse_shelf_id')->nullable()->constrained()->onDelete('set null');
            $table->string('product_name');
            $table->string('sku')->unique();
            $table->string('barcode')->nullable();
            $table->text('description')->nullable();
            $table->string('category', 100)->nullable();
            $table->string('unit', 20)->default('pcs');
            $table->decimal('quantity', 12, 2)->default(0);
            $table->decimal('min_quantity', 12, 2)->default(0);
            $table->decimal('max_quantity', 12, 2)->default(0);
            $table->decimal('reorder_point', 12, 2)->default(0);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->decimal('selling_price', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('warehouse_id');
            $table->index('warehouse_shelf_id');
            $table->index('sku');
            $table->index('barcode');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
