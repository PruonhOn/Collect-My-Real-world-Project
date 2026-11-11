<?php

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\Models\Category;
use App\Models\ContactInquiry;
use App\Models\Course;
use App\Models\SchoolSetting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class PublicControllerTest extends TestCase
{
    use LazilyRefreshDatabase;
    use SeedsRolesAndPermissions;

    public function test_settings_returns_only_public_settings(): void
    {
        SchoolSetting::create(['group' => 'general', 'key' => 'school_name', 'value' => json_encode('Test School'), 'is_public' => true]);
        SchoolSetting::create(['group' => 'general', 'key' => 'secret_key', 'value' => 'hunter2', 'is_public' => false]);

        $this->getJson('/api/v1/public/settings')
            ->assertOk()
            ->assertJsonPath('data.school_name', 'Test School')
            ->assertJsonMissingPath('data.secret_key');
    }

    public function test_courses_returns_only_published_and_active_courses(): void
    {
        Course::factory()->create(['name' => 'Visible Course']);
        Course::factory()->unpublished()->create(['name' => 'Hidden Unpublished']);
        Course::factory()->archived()->create(['name' => 'Hidden Archived']);

        $this->getJson('/api/v1/public/courses')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Visible Course')
            ->assertJsonCount(1, 'data')
            ->assertJsonMissingPath('data.1');
    }

    public function test_courses_filters_by_featured_flag(): void
    {
        Course::factory()->featured()->create(['name' => 'Featured Course']);
        Course::factory()->create(['name' => 'Regular Course']);

        $this->getJson('/api/v1/public/courses?featured=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Featured Course');
    }

    public function test_courses_filters_by_type_and_category(): void
    {
        $category = Category::factory()->create();
        Course::factory()->create(['name' => 'Online Course', 'type' => 'online', 'category_id' => $category->id]);
        Course::factory()->create(['name' => 'Classroom Course', 'type' => 'face_to_face']);

        $this->getJson("/api/v1/public/courses?type=online&category_id={$category->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Online Course');
    }

    public function test_categories_returns_courses_counts_only_for_published_active(): void
    {
        $category = Category::factory()->create();
        Course::factory()->count(2)->create(['category_id' => $category->id]);
        Course::factory()->unpublished()->create(['category_id' => $category->id]);

        $this->getJson('/api/v1/public/categories')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.courses_count', 2);
    }

    public function test_contact_creates_inquiry_and_returns_201(): void
    {
        $this->postJson('/api/v1/public/contact', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '+1234567890',
            'subject' => 'Admissions',
            'message' => 'I would like more information about enrollment.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.message', 'Inquiry submitted successfully.');

        $this->assertDatabaseHas('contact_inquiries', [
            'email' => 'jane@example.com',
            'subject' => 'Admissions',
        ]);
    }

    public function test_contact_returns_422_when_required_fields_are_missing(): void
    {
        $this->postJson('/api/v1/public/contact', ['email' => 'jane@example.com'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'message']);
    }

    public function test_inquiries_returns_403_without_the_view_permission(): void
    {
        $this->seedRolesAndPermissions();
        $student = $this->studentUser();

        $this->actingAs($student, 'sanctum')
            ->getJson('/api/v1/admin/contact-inquiries')
            ->assertForbidden();
    }

    public function test_inquiries_return_only_visible_fields(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();
        ContactInquiry::factory()->create(['name' => 'Acme Applicant', 'email' => 'acme@example.com']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/contact-inquiries')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Acme Applicant')
            ->assertJsonPath('data.0.email', 'acme@example.com');
    }

    public function test_inquiries_returns_401_when_unauthenticated(): void
    {
        $this->getJson('/api/v1/admin/contact-inquiries')
            ->assertUnauthorized();
    }
}
