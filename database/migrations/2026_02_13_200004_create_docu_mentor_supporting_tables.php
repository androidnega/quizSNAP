<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remaining Docu Mentor tables referenced by later migrations and cleanup.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('project_supervisors')) {
            Schema::create('project_supervisors', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['project_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('project_files')) {
            Schema::create('project_files', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->string('path');
                $table->string('original_name')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('chapters')) {
            Schema::create('chapters', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->string('title');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->string('status', 20)->default('pending');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('features')) {
            Schema::create('features', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('submissions')) {
            Schema::create('submissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('chapter_id')->constrained('chapters')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('file', 1024)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('document_ai_reviews')) {
            Schema::create('document_ai_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('submission_id')->constrained('submissions')->cascadeOnDelete();
                $table->text('review')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_ai_reviews');
        Schema::dropIfExists('submissions');
        Schema::dropIfExists('features');
        Schema::dropIfExists('chapters');
        Schema::dropIfExists('project_files');
        Schema::dropIfExists('project_supervisors');
    }
};
