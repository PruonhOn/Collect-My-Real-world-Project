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
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 191);
            $table->string('code', 50)->nullable()->unique();
            $table->text('description')->nullable();
            $table->string('type', 20)->default('face_to_face');
            $table->decimal('fee', 12, 2)->default(0);
            $table->unsignedInteger('capacity')->default(0);
            $table->string('status', 20)->default('active');
            $table->boolean('featured')->default(false);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'academic_year_id']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
