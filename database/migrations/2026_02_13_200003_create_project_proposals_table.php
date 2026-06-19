<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Docu Mentor project proposals. coordinator_comment is added by a later migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_proposals')) {
            return;
        }

        Schema::create('project_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('file', 255)->nullable();
            $table->text('comment')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_proposals');
    }
};
