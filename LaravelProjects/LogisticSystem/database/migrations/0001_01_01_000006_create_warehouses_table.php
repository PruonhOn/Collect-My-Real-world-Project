<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->text('address');
            $table->string('city');
            $table->string('province');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('manager_name')->nullable();
            $table->decimal('total_capacity', 10, 2)->default(0);
            $table->decimal('used_capacity', 10, 2)->default(0);
            $table->decimal('total_area', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('branch_id')->constrained();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['city', 'province']);
        });

        Schema::create('warehouse_locations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('zone')->nullable();
            $table->string('aisle')->nullable();
            $table->string('rack')->nullable();
            $table->string('shelf')->nullable();
            $table->string('bin')->nullable();
            $table->decimal('max_weight', 10, 2)->nullable();
            $table->decimal('max_volume', 10, 2)->nullable();
            $table->boolean('is_occupied')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->index(['warehouse_id', 'zone', 'rack']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_locations');
        Schema::dropIfExists('warehouses');
    }
};
