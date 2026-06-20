<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_notifications')) {
            return;
        }

        Schema::create('student_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('student_index', 64);
            $table->string('student_index_hash', 64)->nullable();
            $table->string('type', 32);
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('action_url', 512)->nullable();
            $table->json('meta')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_type', 64)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['student_index', 'read_at', 'created_at'], 'student_notifications_inbox_idx');
            $table->index(['student_index_hash', 'read_at'], 'student_notifications_hash_read_idx');
            $table->index(['type', 'source_type', 'source_id'], 'student_notifications_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_notifications');
    }
};
