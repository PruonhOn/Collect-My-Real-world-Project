<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['license', 'id_card', 'contract', 'certification', 'other']);
            $table->string('document_number', 100)->nullable();
            $table->string('file_path', 500);
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();

            $table->index('driver_id');
            $table->index('type');
            $table->index('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_documents');
    }
};
