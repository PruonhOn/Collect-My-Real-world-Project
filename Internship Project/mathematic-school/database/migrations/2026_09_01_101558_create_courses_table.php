<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('title');
            $table->string('course_code')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('age_group')->nullable();
            $table->string('level')->nullable();
            $table->integer('duration')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->text('requirements')->nullable();
            $table->text('objectives')->nullable();
            $table->enum('status', ['draft', 'pending_review', 'published', 'archived'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['title', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
