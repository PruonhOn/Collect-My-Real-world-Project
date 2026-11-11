<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('page');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'book_id']);
            $table->unique(['user_id', 'book_id', 'page']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookmarks');
    }
};
