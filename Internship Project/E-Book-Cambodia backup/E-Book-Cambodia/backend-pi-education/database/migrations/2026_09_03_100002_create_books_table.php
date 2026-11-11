<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('author')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_type')->default('application/pdf');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('language')->default('km');
            $table->unsignedInteger('pages')->default(0);
            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('is_free')->default(false);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['is_published', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
