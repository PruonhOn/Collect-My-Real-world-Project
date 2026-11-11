<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Expand the authors table into a full author account + profile system.
     *
     * `name` stays as the author's display/full name and remains the field
     * mirrored on the books.author column for legacy compatibility. Email is
     * the new unique identifier; it is nullable only so legacy author names
     * (backfilled from books.author) can live on without an account. Accounts
     * created by admins always have a required, unique email.
     */
    public function up(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            $table->dropUnique('authors_name_unique');

            $table->string('email')->nullable()->unique()->after('name');
            $table->string('password')->nullable()->after('email');
            $table->string('phone')->nullable()->after('password');
            $table->string('status')->default('active')->after('phone');
            $table->string('photo')->nullable()->after('status');
            $table->text('bio')->nullable()->after('photo');
            $table->string('specialization')->nullable()->after('bio');
            $table->json('social_links')->nullable()->after('specialization');
            $table->string('organization')->nullable()->after('social_links');
            $table->string('school')->nullable()->after('organization');
            $table->timestamp('email_verified_at')->nullable()->after('school');
            $table->timestamp('last_login_at')->nullable()->after('email_verified_at');

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            $table->dropColumn([
                'email', 'password', 'phone', 'status', 'photo', 'bio',
                'specialization', 'social_links', 'organization', 'school',
                'email_verified_at', 'last_login_at',
            ]);

            $table->string('name')->unique()->change();
        });
    }
};
