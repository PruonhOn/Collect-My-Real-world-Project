<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_toc_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('book_toc_items')->cascadeOnDelete();
            $table->string('title');
            $table->string('title_en')->nullable();
            $table->unsignedInteger('page');
            $table->unsignedInteger('position')->default(0);
            $table->unsignedTinyInteger('depth')->default(1);
            $table->timestamps();

            $table->index(['book_id', 'position']);
            $table->index(['book_id', 'page']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_toc_items');
    }
};
