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
            $table->string('slug')->unique();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained()->nullOnDelete();
            $table->string('cover_image')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_type', 20)->default('pdf');
            $table->bigInteger('file_size')->default(0);
            $table->integer('pages')->default(0);
            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('is_free')->default(true);
            $table->boolean('is_published')->default(false);
            $table->bigInteger('views_count')->default(0);
            $table->bigInteger('downloads_count')->default(0);
            $table->timestamps();
        });

        Schema::create('book_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5)->default('km');
            $table->string('title');
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->unique(['book_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_translations');
        Schema::dropIfExists('books');
    }
};
