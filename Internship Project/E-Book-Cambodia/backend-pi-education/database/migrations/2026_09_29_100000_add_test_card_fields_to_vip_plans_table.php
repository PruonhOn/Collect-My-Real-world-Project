<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vip_plans', function (Blueprint $table) {
            $table->unsignedInteger('duration_hours')->nullable()->after('duration_days');
            $table->boolean('is_test')->default(false)->after('duration_hours');
        });
    }

    public function down(): void
    {
        Schema::table('vip_plans', function (Blueprint $table) {
            $table->dropColumn(['duration_hours', 'is_test']);
        });
    }
};
