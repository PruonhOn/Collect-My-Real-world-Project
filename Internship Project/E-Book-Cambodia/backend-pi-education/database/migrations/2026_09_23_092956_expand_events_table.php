<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Expand the basic events table into the full event management schema.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('events_is_published_index');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['tag', 'icon', 'date', 'is_published']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->string('cover_image')->nullable()->after('description');
            $table->string('category')->nullable()->after('cover_image');
            $table->dateTime('start_date')->nullable()->after('category');
            $table->dateTime('end_date')->nullable()->after('start_date');
            $table->string('location')->nullable()->after('end_date');
            $table->string('organizer')->nullable()->after('location');
            $table->string('registration_link')->nullable()->after('organizer');
            $table->string('contact_phone')->nullable()->after('registration_link');
            $table->string('contact_email')->nullable()->after('contact_phone');
            $table->unsignedInteger('max_participants')->nullable()->after('contact_email');
            $table->string('fee_type')->default('free')->after('max_participants');
            $table->decimal('fee_amount', 10, 2)->nullable()->after('fee_type');
            $table->string('speaker')->nullable()->after('fee_amount');
            $table->json('social_links')->nullable()->after('speaker');
            $table->string('status')->default('draft')->after('social_links');
            $table->boolean('is_featured')->default(false)->after('status');

            $table->index('status');
            $table->index('is_featured');
            $table->index('start_date');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'cover_image', 'category', 'start_date', 'end_date', 'location',
                'organizer', 'registration_link', 'contact_phone', 'contact_email',
                'max_participants', 'fee_type', 'fee_amount', 'speaker',
                'social_links', 'status', 'is_featured',
            ]);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->string('tag')->nullable();
            $table->string('icon')->default('📅')->after('title');
            $table->string('date')->nullable();
            $table->boolean('is_published')->default(true);
        });
    }
};
