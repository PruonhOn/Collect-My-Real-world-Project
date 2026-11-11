<?php
// ============================================================
// DATABASE MIGRATIONS — Run in order
// ============================================================

// ---- 1. work_orders table ----
// php artisan make:migration create_work_orders_table

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();                      // e.g. AST-001
            $table->string('category');                            // HVAC, Electrical, Plumbing...
            $table->string('location');                            // Building A, Floor 2
            $table->enum('status', ['active', 'inactive', 'retired'])->default('active');
            $table->date('installed_at')->nullable();
            $table->date('warranty_expires_at')->nullable();
            $table->json('meta')->nullable();                      // flexible extra data
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();                      // WO-1088
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['corrective', 'preventive', 'inspection', 'emergency']);
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', [
                'open', 'assigned', 'in_progress', 'on_hold', 'completed', 'cancelled'
            ])->default('open');
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('progress')->default(0);  // 0-100
            $table->timestamp('due_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'due_at']);                   // common filter combo
            $table->index('assigned_to');
        });

        Schema::create('maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->enum('action', [
                'created', 'assigned', 'started', 'progress_updated',
                'note_added', 'status_changed', 'completed', 'cancelled'
            ]);
            $table->text('note')->nullable();
            $table->json('meta')->nullable();                      // old_status, new_status etc.
            $table->timestamps();
        });

        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('message')->nullable();
            $table->enum('severity', ['info', 'warning', 'critical'])->default('info');
            $table->morphs('alertable');                           // work_order or asset
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['alertable_type', 'alertable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
        Schema::dropIfExists('maintenance_logs');
        Schema::dropIfExists('work_orders');
        Schema::dropIfExists('assets');
    }
};
