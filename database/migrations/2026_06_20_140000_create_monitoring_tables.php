<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('system_errors')) {
            Schema::create('system_errors', function (Blueprint $table) {
                $table->id();
                $table->string('fingerprint', 64)->unique();
                $table->string('exception_class');
                $table->string('exception_type', 32)->nullable();
                $table->text('message');
                $table->string('error_code', 32)->nullable();
                $table->string('severity', 16)->index();
                $table->string('file')->nullable();
                $table->unsignedInteger('line')->nullable();
                $table->string('class_name')->nullable();
                $table->string('method')->nullable();
                $table->string('route')->nullable();
                $table->text('url')->nullable();
                $table->string('http_method', 16)->nullable();
                $table->json('source_context')->nullable();
                $table->unsignedInteger('occurrence_count')->default(1);
                $table->unsignedInteger('affected_users_count')->default(0);
                $table->json('affected_user_ids')->nullable();
                $table->string('resolution_status', 32)->default('open')->index();
                $table->timestamp('first_seen_at')->useCurrent();
                $table->timestamp('last_seen_at')->useCurrent();
                $table->timestamps();

                $table->index(['severity', 'last_seen_at']);
                $table->index(['resolution_status', 'last_seen_at']);
            });
        }

        if (! Schema::hasTable('system_error_occurrences')) {
            Schema::create('system_error_occurrences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('system_error_id')->constrained('system_errors')->cascadeOnDelete();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('user_name')->nullable();
                $table->string('user_role', 32)->nullable();
                $table->string('session_id', 128)->nullable();
                $table->string('browser', 128)->nullable();
                $table->string('device', 128)->nullable();
                $table->string('operating_system', 128)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('environment', 32)->nullable();
                $table->json('request_payload')->nullable();
                $table->longText('stack_trace')->nullable();
                $table->timestamp('occurred_at')->useCurrent()->index();

                $table->index(['system_error_id', 'occurred_at']);
            });
        }

        if (! Schema::hasTable('database_query_logs')) {
            Schema::create('database_query_logs', function (Blueprint $table) {
                $table->id();
                $table->longText('sql');
                $table->json('bindings')->nullable();
                $table->unsignedInteger('execution_time_ms');
                $table->string('status', 32)->default('slow')->index();
                $table->string('route')->nullable();
                $table->string('controller')->nullable();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('connection', 64)->nullable();
                $table->text('error_message')->nullable();
                $table->timestamp('occurred_at')->useCurrent()->index();

                $table->index(['execution_time_ms', 'occurred_at']);
            });
        }

        if (! Schema::hasTable('api_request_logs')) {
            Schema::create('api_request_logs', function (Blueprint $table) {
                $table->id();
                $table->string('endpoint', 512)->index();
                $table->string('method', 16)->index();
                $table->unsignedSmallInteger('status_code')->index();
                $table->unsignedInteger('response_time_ms');
                $table->unsignedInteger('request_size')->nullable();
                $table->unsignedInteger('response_size')->nullable();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('occurred_at')->useCurrent()->index();

                $table->index(['endpoint', 'occurred_at']);
            });
        }

        if (! Schema::hasTable('performance_logs')) {
            Schema::create('performance_logs', function (Blueprint $table) {
                $table->id();
                $table->string('route')->nullable()->index();
                $table->string('controller')->nullable();
                $table->unsignedInteger('page_load_time_ms')->nullable();
                $table->unsignedInteger('controller_time_ms')->nullable();
                $table->unsignedInteger('memory_usage_kb')->nullable();
                $table->unsignedInteger('query_time_ms')->nullable();
                $table->unsignedInteger('request_duration_ms')->nullable();
                $table->unsignedInteger('response_duration_ms')->nullable();
                $table->unsignedInteger('cache_hits')->default(0);
                $table->unsignedInteger('cache_misses')->default(0);
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->timestamp('occurred_at')->useCurrent()->index();
            });
        }

        if (! Schema::hasTable('security_events')) {
            Schema::create('security_events', function (Blueprint $table) {
                $table->id();
                $table->string('event_type', 64)->index();
                $table->string('severity', 16)->default('warning')->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('user_name')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->string('route')->nullable();
                $table->text('description')->nullable();
                $table->json('meta')->nullable();
                $table->timestamp('occurred_at')->useCurrent()->index();

                $table->index(['event_type', 'occurred_at']);
            });
        }

        if (! Schema::hasTable('system_audit_logs')) {
            Schema::create('system_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('user_name')->nullable();
                $table->string('user_role', 32)->nullable();
                $table->string('action', 128)->index();
                $table->string('subject_type', 128)->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('occurred_at')->useCurrent()->index();

                $table->index(['action', 'occurred_at']);
                $table->index(['subject_type', 'subject_id']);
            });
        }

        if (! Schema::hasTable('monitoring_user_sessions')) {
            Schema::create('monitoring_user_sessions', function (Blueprint $table) {
                $table->id();
                $table->string('session_id', 128)->unique();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('user_name')->nullable();
                $table->string('user_role', 32)->nullable();
                $table->string('actor_type', 32)->default('staff')->index();
                $table->string('ip_address', 45)->nullable();
                $table->string('current_page', 512)->nullable();
                $table->string('browser', 128)->nullable();
                $table->string('device', 128)->nullable();
                $table->string('location', 128)->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamp('last_activity_at')->useCurrent();
                $table->timestamp('started_at')->useCurrent();
                $table->timestamps();

                $table->index(['is_active', 'last_activity_at']);
            });
        }

        if (! Schema::hasTable('server_health_snapshots')) {
            Schema::create('server_health_snapshots', function (Blueprint $table) {
                $table->id();
                $table->string('status', 16)->index();
                $table->decimal('cpu_usage', 5, 2)->nullable();
                $table->decimal('ram_usage', 5, 2)->nullable();
                $table->decimal('disk_usage', 5, 2)->nullable();
                $table->unsignedBigInteger('disk_free_bytes')->nullable();
                $table->decimal('load_average', 8, 2)->nullable();
                $table->string('php_version', 32)->nullable();
                $table->string('laravel_version', 32)->nullable();
                $table->string('mysql_version', 32)->nullable();
                $table->unsignedInteger('queue_workers')->nullable();
                $table->unsignedBigInteger('storage_usage_bytes')->nullable();
                $table->unsignedInteger('uptime_seconds')->nullable();
                $table->string('network_status', 32)->nullable();
                $table->json('meta')->nullable();
                $table->timestamp('recorded_at')->useCurrent()->index();
            });
        }

        if (! Schema::hasTable('monitoring_notifications')) {
            Schema::create('monitoring_notifications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('type', 64)->index();
                $table->string('severity', 16)->index();
                $table->string('title');
                $table->text('message');
                $table->json('meta')->nullable();
                $table->timestamp('read_at')->nullable()->index();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['user_id', 'read_at', 'created_at']);
            });
        }

        if (! Schema::hasTable('monitoring_settings')) {
            Schema::create('monitoring_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->json('value')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_settings');
        Schema::dropIfExists('monitoring_notifications');
        Schema::dropIfExists('server_health_snapshots');
        Schema::dropIfExists('monitoring_user_sessions');
        Schema::dropIfExists('system_audit_logs');
        Schema::dropIfExists('security_events');
        Schema::dropIfExists('performance_logs');
        Schema::dropIfExists('api_request_logs');
        Schema::dropIfExists('database_query_logs');
        Schema::dropIfExists('system_error_occurrences');
        Schema::dropIfExists('system_errors');
    }
};
