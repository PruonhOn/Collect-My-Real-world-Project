<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained('courses')->cascadeOnDelete();
            $table->foreignId('quiz_id')->nullable()->constrained('quizzes')->cascadeOnDelete();
            $table->foreignId('assignment_id')->nullable()->constrained('assignments')->cascadeOnDelete();
            $table->decimal('score', 6, 2);
            $table->decimal('max_score', 6, 2);
            $table->enum('type', ['quiz', 'assignment', 'exam', 'classwork'])->default('assignment');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
