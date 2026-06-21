<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('quizzes') && ! Schema::hasColumn('quizzes', 'is_paused')) {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->boolean('is_paused')->default(false)->after('is_active');
                $table->text('operations_broadcast_message')->nullable()->after('is_paused');
            });
        }

        if (! Schema::hasTable('operations_exam_incidents')) {
            Schema::create('operations_exam_incidents', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('quiz_id')->nullable()->index();
                $table->unsignedBigInteger('quiz_session_id')->nullable()->index();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('incident_type', 64)->nullable()->index();
                $table->string('severity', 16)->index();
                $table->string('status', 32)->default('open')->index();
                $table->unsignedBigInteger('assigned_to')->nullable()->index();
                $table->string('assigned_to_name')->nullable();
                $table->text('resolution_notes')->nullable();
                $table->json('meta')->nullable();
                $table->timestamp('started_at')->useCurrent();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'severity', 'started_at']);
            });
        }

        if (! Schema::hasTable('operations_alerts')) {
            Schema::create('operations_alerts', function (Blueprint $table) {
                $table->id();
                $table->string('type', 64)->index();
                $table->string('severity', 16)->index();
                $table->string('title');
                $table->text('message');
                $table->json('meta')->nullable();
                $table->timestamp('read_at')->nullable()->index();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['severity', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('operations_alerts');
        Schema::dropIfExists('operations_exam_incidents');

        if (Schema::hasTable('quizzes')) {
            Schema::table('quizzes', function (Blueprint $table) {
                if (Schema::hasColumn('quizzes', 'operations_broadcast_message')) {
                    $table->dropColumn('operations_broadcast_message');
                }
                if (Schema::hasColumn('quizzes', 'is_paused')) {
                    $table->dropColumn('is_paused');
                }
            });
        }
    }
};
