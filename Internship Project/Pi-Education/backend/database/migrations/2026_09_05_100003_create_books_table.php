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
            $table->string('type', 10)->default('FREE');
            $table->decimal('price', 10, 2)->default(0);
            $table->string('status', 20)->default('DRAFT');
            $table->string('cover_image')->nullable();
            $table->string('language', 5)->default('km');
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('reader_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'type']);
            $table->index('published_at');
        });

        Schema::create('book_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('title');
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();

            $table->unique(['book_id', 'locale']);
        });

        Schema::create('book_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['book_id', 'category_id']);
        });

        Schema::create('book_author', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['book_id', 'author_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_author');
        Schema::dropIfExists('book_category');
        Schema::dropIfExists('book_translations');
        Schema::dropIfExists('books');
    }
};
