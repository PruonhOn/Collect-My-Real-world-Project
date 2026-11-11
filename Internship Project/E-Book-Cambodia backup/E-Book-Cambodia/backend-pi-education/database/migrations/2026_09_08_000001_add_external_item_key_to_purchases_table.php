<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('external_item_key')->nullable()->after('course_id');
            $table->index(['user_id', 'external_item_key', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'external_item_key', 'status']);
            $table->dropColumn('external_item_key');
        });
    }
};
