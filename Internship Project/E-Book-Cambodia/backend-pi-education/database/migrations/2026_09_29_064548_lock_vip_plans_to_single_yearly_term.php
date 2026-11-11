<?php

use App\Services\VipService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * VIP is sold as a single one-year term, so every plan record is pinned to
     * that duration and anything other than the yearly plan is retired.
     */
    public function up(): void
    {
        Schema::table('vip_plans', function (Blueprint $table) {
            $table->unsignedInteger('duration_days')->default(VipService::SUBSCRIPTION_DAYS)->change();
        });

        DB::table('vip_plans')->update(['duration_days' => VipService::SUBSCRIPTION_DAYS]);

        DB::table('vip_plans')
            ->where('slug', '!=', 'vip-yearly')
            ->update(['is_active' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('vip_plans')->update(['is_active' => true]);
    }
};
