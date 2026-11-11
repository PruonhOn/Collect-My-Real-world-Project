<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('video_url')->nullable();
            $table->string('video_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->text('external_resources')->nullable();
            $table->integer('duration')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_free')->default(false);
            $table->boolean('is_published')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['course_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
