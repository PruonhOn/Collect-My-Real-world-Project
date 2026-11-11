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
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('image')->nullable();
            $table->json('images')->nullable();
            $table->boolean('status')->default(true);
            $table->boolean('featured')->default(false);
            $table->integer('stock')->default(0);
            $table->string('size')->nullable();
            $table->string('color')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
