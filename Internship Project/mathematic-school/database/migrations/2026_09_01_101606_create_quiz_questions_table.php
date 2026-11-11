<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->text('question');
            $table->enum('question_type', ['single', 'true_false', 'multiple'])->default('single');
            $table->decimal('marks', 5, 2)->default(1);
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index(['quiz_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_questions');
    }
};
