<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('client_token', 64);
            $table->string('status', 20)->default('waiting'); // waiting, active, closed
            $table->string('student_index', 64)->nullable();
            $table->string('student_name', 255)->nullable();
            $table->string('page_url', 500)->nullable();
            $table->string('issue_category', 64)->nullable();
            $table->foreignId('assigned_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->boolean('screen_share_active')->default(false);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'last_message_at']);
        });

        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_session_id')->constrained('support_sessions')->cascadeOnDelete();
            $table->string('sender_type', 16); // student, admin, system
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->string('message_type', 24)->default('text'); // text, webrtc, system
            $table->text('body')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['support_session_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_messages');
        Schema::dropIfExists('support_sessions');
    }
};
