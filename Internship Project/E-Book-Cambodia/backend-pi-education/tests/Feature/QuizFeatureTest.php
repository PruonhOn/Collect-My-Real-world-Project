<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function madeQuiz(array $questions = []): Quiz
    {
        $quiz = Quiz::create([
            'title' => 'Math Quiz',
            'description' => 'Basic arithmetic',
            'pass_percent' => 50,
            'is_published' => true,
        ]);

        $quiz->questions()->create([
            'question' => 'What is 2 + 2?',
            'options' => ['3', '4', '5'],
            'correct_option' => 1,
            'position' => 1,
        ]);

        return $quiz;
    }

    public function test_admin_can_create_quiz_with_questions(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/admin/quizzes', [
                'title' => 'Geography Quiz',
                'pass_percent' => 60,
                'questions' => [
                    [
                        'question' => 'Capital of Cambodia?',
                        'options' => ['Bangkok', 'Phnom Penh', 'Hanoi'],
                        'correct_option' => 1,
                        'position' => 1,
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('quiz.questions.0.question', 'Capital of Cambodia?');

        $this->assertDatabaseHas('quiz_questions', ['question' => 'Capital of Cambodia?']);
    }

    public function test_non_admin_cannot_manage_quizzes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/admin/quizzes', ['title' => 'Nope'])
            ->assertForbidden();
    }

    public function test_public_list_only_includes_published_quizzes(): void
    {
        Quiz::create(['title' => 'Published', 'is_published' => true]);
        Quiz::create(['title' => 'Draft', 'is_published' => false]);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/v1/quizzes')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_public_show_hides_correct_answers(): void
    {
        $quiz = $this->madeQuiz();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson("/api/v1/quizzes/{$quiz->id}")
            ->assertOk()
            ->assertJsonPath('quiz.questions.0.question', 'What is 2 + 2?')
            ->assertJsonMissingPath('quiz.questions.0.correct_option')
            ->assertJsonStructure(['quiz' => ['questions' => [['question', 'options']]]]);
    }

    public function test_completed_attempt_returns_score_and_review(): void
    {
        $user = User::factory()->create();
        $quiz = $this->madeQuiz();
        $question = $quiz->questions()->first();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/quizzes/{$quiz->id}/attempt", [
                'answers' => [$question->id => 1],
            ])
            ->assertOk()
            ->assertJsonPath('attempt.score', 1)
            ->assertJsonPath('attempt.total', 1)
            ->assertJsonPath('passed', true)
            ->assertJsonPath('review.0.is_correct', true)
            ->assertJsonPath('review.0.correct_option', 1);
    }

    public function test_wrong_answer_is_marked_incorrect(): void
    {
        $user = User::factory()->create();
        $quiz = $this->madeQuiz();
        $question = $quiz->questions()->first();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/quizzes/{$quiz->id}/attempt", [
                'answers' => [$question->id => 0],
            ])
            ->assertOk()
            ->assertJsonPath('attempt.score', 0)
            ->assertJsonPath('passed', false)
            ->assertJsonPath('review.0.is_correct', false);
    }

    public function test_attempts_are_limited(): void
    {
        $user = User::factory()->create();
        $quiz = $this->madeQuiz();
        $question = $quiz->questions()->first();

        QuizAttempt::factory()
            ->count(Quiz::MAX_ATTEMPTS)
            ->create([
                'user_id' => $user->id,
                'quiz_id' => $quiz->id,
                'score' => 1,
                'total' => 1,
                'passed' => true,
            ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/quizzes/{$quiz->id}/attempt", [
                'answers' => [$question->id => 1],
            ])
            ->assertStatus(422);
    }

    public function test_my_attempts_are_listed_for_the_quiz(): void
    {
        $user = User::factory()->create();
        $quiz = $this->madeQuiz();

        QuizAttempt::create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'score' => 3,
            'total' => 4,
            'passed' => false,
            'completed_at' => now(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/quizzes/{$quiz->id}/attempts")
            ->assertOk()
            ->assertJsonCount(1, 'attempts');
    }
}
