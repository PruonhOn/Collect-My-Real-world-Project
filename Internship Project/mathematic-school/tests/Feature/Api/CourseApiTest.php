<?php

namespace Tests\Feature\Api;

use App\Models\Course;
use Illuminate\Foundation\Testing\WithFaker;

class CourseApiTest extends ApiTestCase
{
    use WithFaker;

    public function test_public_can_list_published_courses(): void
    {
        $published = $this->createPublishedCourse();
        $draft = Course::create(array_merge($published->only([
            'category_id', 'title', 'course_code', 'slug', 'description',
            'age_group', 'level', 'duration', 'price',
        ]), [
            'title' => 'Draft course',
            'slug' => $this->faker->unique()->slug(),
            'course_code' => 'DRF-' . strtoupper($this->faker->unique()->bothify('###')),
            'status' => 'draft',
        ]));

        $this->getJson('/api/v1/courses')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissing(['title' => 'Draft course']);
    }

    public function test_public_can_view_published_course_detail(): void
    {
        $course = $this->createPublishedCourse();

        $this->getJson("/api/v1/courses/{$course->id}")
            ->assertOk()
            ->assertJsonPath('id', $course->id);
    }

    public function test_draft_course_is_not_publicly_accessible(): void
    {
        $course = $this->createPublishedCourse();
        $course->update(['status' => 'draft']);

        $this->getJson("/api/v1/courses/{$course->id}")->assertNotFound();
    }

    public function test_course_search_filter(): void
    {
        $this->createPublishedCourse();
        Course::create([
            'category_id' => $this->createCategory()->id,
            'title' => 'Special Math 101',
            'course_code' => 'MT-' . strtoupper($this->faker->unique()->bothify('###')),
            'slug' => 'special-math-101-' . $this->faker->unique()->randomNumber(),
            'description' => 'A special math course',
            'age_group' => '8-10',
            'level' => 'beginner',
            'duration' => 10,
            'price' => 5,
            'status' => 'published',
        ]);

        $this->getJson('/api/v1/courses?search=Special')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['title' => 'Special Math 101']);
    }

    protected function createCategory()
    {
        return \App\Models\Category::create([
            'name' => $this->faker->unique()->word(),
            'slug' => $this->faker->unique()->slug(),
        ]);
    }
}
