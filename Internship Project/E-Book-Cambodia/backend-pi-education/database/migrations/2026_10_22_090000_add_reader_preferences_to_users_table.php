<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Reader display preferences (theme, type size, reading mode)
            // stored as JSON so the reader can add options without another
            // migration. Null means "use the client defaults".
            $table->json('reader_preferences')->nullable()->after('avatar');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('reader_preferences');
        });
    }
};
