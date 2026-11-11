<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extends the stock users table with the fields authentication and RBAC need.
     *
     * These are added as a separate migration rather than folded into
     * 0001_01_01_000000 so that the Laravel baseline stays untouched and this
     * file reads as an explicit statement of what this application requires of
     * a user account.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 32)->nullable()->after('email');
            $table->string('avatar_path')->nullable();

            // Defaults to true so an account is never silently locked out by the
            // upgrade itself. Deactivation is always a deliberate act by an
            // administrator, and App\Http\Middleware\EnsureUserIsActive is what
            // acts on it.
            $table->boolean('is_active')->default(true)->index();

            $table->timestamp('last_login_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'avatar_path', 'is_active', 'last_login_at']);
        });
    }
};
