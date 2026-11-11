<?php

namespace Tests\Feature\Api;

use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizQuestion;
use Illuminate\Foundation\Testing\WithFaker;

class QuizApiTest extends ApiTestCase
{
    use WithFaker;

    public function test_enrolled_student_can_start_unpublished_quiz(): void
    {
        $user = $this->createUser(['role' => 'student'], 'student');
        $student = $this->createStudent($user);
        $course = $this->createPublishedCourse();
        $this->createEnrollment($student, $course);

        $lesson = $this->createLesson($course->id);
        $quiz = $this->createQuiz($course->id, $lesson->id, true);
        $this->addQuestion($quiz->id);

        $this->withHeaders($this->bearerAuth($user))
            ->postJson("/api/v1/quizzes/{$quiz->id}/start")
            ->assertOk()
            ->assertJsonStructure(['quiz', 'attempt']);
    }

    public function test_student_cannot_start_unpublished_quiz(): void
    {
        $user = $this->createUser(['role' => 'student'], 'student');
        $student = $this->createStudent($user);
        $course = $this->createPublishedCourse();
        $this->createEnrollment($student, $course);

        $quiz = Quiz::create([
            'course_id' => $course->id,
            'title' => 'Unpublished Quiz',
            'passing_score' => 50,
            'allow_retry' => true,
            'is_published' => false,
        ]);

        $this->withHeaders($this->bearerAuth($user))
            ->postJson("/api/v1/quizzes/{$quiz->id}/start")
            ->assertNotFound();
    }

    public function test_non_enrolled_student_cannot_start_quiz(): void
    {
        $user = $this->createUser(['role' => 'student'], 'student');
        $this->createStudent($user);
        $course = $this->createPublishedCourse();

        $lesson = $this->createLesson($course->id);
        $quiz = $this->createQuiz($course->id, $lesson->id, true);

        $this->withHeaders($this->bearerAuth($user))
            ->postJson("/api/v1/quizzes/{$quiz->id}/start")
            ->assertForbidden();
    }

    public function test_student_can_submit_quiz_and_get_graded(): void
    {
        $user = $this->createUser(['role' => 'student'], 'student');
        $student = $this->createStudent($user);
        $course = $this->createPublishedCourse();
        $this->createEnrollment($student, $course);

        $lesson = $this->createLesson($course->id);
        $quiz = $this->createQuiz($course->id, $lesson->id, true);
        $question = $this->addQuestion($quiz->id);

        $correctAnswer = QuizAnswer::create([
            'question_id' => $question->id,
            'answer' => '4',
            'is_correct' => true,
        ]);
        QuizAnswer::create([
            'question_id' => $question->id,
            'answer' => '5',
            'is_correct' => false,
        ]);

        // Start quiz to get attempt
        $start = $this->withHeaders($this->bearerAuth($user))
            ->postJson("/api/v1/quizzes/{$quiz->id}/start")
            ->assertOk();
        $attemptId = $start->json('attempt.id');

        $this->withHeaders($this->bearerAuth($user))
            ->postJson("/api/v1/quizzes/{$quiz->id}/submit", [
                'attempt_id' => $attemptId,
                'answers' => [$question->id => [$correctAnswer->id]],
            ])
            ->assertOk()
            ->assertJsonPath('attempt.percentage', '100.00');
    }

    public function test_student_cannot_submit_another_students_attempt(): void
    {
        $userX = $this->createUser(['role' => 'student'], 'student');
        $this->createStudent($userX);
        $studentY = $this->createStudent();
        $course = $this->createPublishedCourse();
        $this->createEnrollment($studentY, $course);

        $lesson = $this->createLesson($course->id);
        $quiz = $this->createQuiz($course->id, $lesson->id, true);
        $this->addQuestion($quiz->id);

        // Student Y starts & has an attempt
        $attempt = \App\Models\QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $studentY->id,
            'started_at' => now(),
        ]);

        $this->withHeaders($this->bearerAuth($userX))
            ->postJson("/api/v1/quizzes/{$quiz->id}/submit", [
                'attempt_id' => $attempt->id,
                'answers' => [],
            ])
            ->assertNotFound();
    }

    protected function createLesson(int $courseId): Lesson
    {
        return Lesson::create([
            'course_id' => $courseId,
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'order' => 1,
            'is_free' => false,
            'is_published' => true,
        ]);
    }

    protected function createQuiz(int $courseId, ?int $lessonId, bool $published): Quiz
    {
        return Quiz::create([
            'course_id' => $courseId,
            'lesson_id' => $lessonId,
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'time_limit' => 10,
            'passing_score' => 50,
            'allow_retry' => true,
            'is_published' => $published,
        ]);
    }

    protected function addQuestion(int $quizId): QuizQuestion
    {
        return QuizQuestion::create([
            'quiz_id' => $quizId,
            'question' => 'What is 2 + 2?',
            'question_type' => 'single',
            'marks' => 1,
            'order' => 1,
        ]);
    }
}
