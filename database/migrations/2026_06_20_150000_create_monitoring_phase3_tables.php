<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('monitoring_deployments')) {
            Schema::create('monitoring_deployments', function (Blueprint $table) {
                $table->id();
                $table->string('version', 64)->nullable();
                $table->string('git_commit', 64)->nullable()->index();
                $table->string('branch', 128)->nullable();
                $table->unsignedBigInteger('deployed_by')->nullable()->index();
                $table->string('deployed_by_name')->nullable();
                $table->text('notes')->nullable();
                $table->json('meta')->nullable();
                $table->timestamp('deployed_at')->useCurrent()->index();
            });
        }

        if (! Schema::hasTable('monitoring_incidents')) {
            Schema::create('monitoring_incidents', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('severity', 8)->index();
                $table->string('status', 32)->default('open')->index();
                $table->unsignedBigInteger('owner_id')->nullable()->index();
                $table->string('owner_name')->nullable();
                $table->json('affected_services')->nullable();
                $table->json('linked_error_ids')->nullable();
                $table->unsignedBigInteger('linked_deployment_id')->nullable()->index();
                $table->text('timeline')->nullable();
                $table->text('resolution_notes')->nullable();
                $table->timestamp('started_at')->useCurrent();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('monitoring_backups')) {
            Schema::create('monitoring_backups', function (Blueprint $table) {
                $table->id();
                $table->string('backup_type', 32)->default('database')->index();
                $table->string('status', 32)->index();
                $table->unsignedBigInteger('size_bytes')->nullable();
                $table->string('location', 512)->nullable();
                $table->unsignedInteger('retention_days')->nullable();
                $table->string('restore_test_status', 32)->nullable();
                $table->timestamp('backed_up_at')->useCurrent()->index();
                $table->json('meta')->nullable();
            });
        }

        if (! Schema::hasTable('monitoring_capacity_snapshots')) {
            Schema::create('monitoring_capacity_snapshots', function (Blueprint $table) {
                $table->id();
                $table->string('snapshot_type', 32)->index();
                $table->unsignedBigInteger('total_bytes')->nullable();
                $table->unsignedBigInteger('used_bytes')->nullable();
                $table->unsignedBigInteger('free_bytes')->nullable();
                $table->decimal('growth_rate_daily', 12, 4)->nullable();
                $table->json('breakdown')->nullable();
                $table->json('forecast')->nullable();
                $table->timestamp('recorded_at')->useCurrent()->index();
                $table->index(['snapshot_type', 'recorded_at']);
            });
        }

        if (! Schema::hasTable('monitoring_reverb_metrics')) {
            Schema::create('monitoring_reverb_metrics', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('connected_users')->default(0);
                $table->unsignedInteger('connected_channels')->default(0);
                $table->unsignedInteger('messages_per_minute')->default(0);
                $table->unsignedInteger('events_per_minute')->default(0);
                $table->unsignedInteger('failed_broadcasts')->default(0);
                $table->unsignedInteger('connection_failures')->default(0);
                $table->unsignedInteger('average_latency_ms')->nullable();
                $table->unsignedInteger('broadcast_queue_delay_ms')->nullable();
                $table->decimal('health_score', 5, 2)->nullable();
                $table->timestamp('recorded_at')->useCurrent()->index();
            });
        }

        if (Schema::hasTable('security_events') && ! Schema::hasColumn('security_events', 'risk_score')) {
            Schema::table('security_events', function (Blueprint $table) {
                $table->unsignedTinyInteger('risk_score')->nullable()->after('severity');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('security_events') && Schema::hasColumn('security_events', 'risk_score')) {
            Schema::table('security_events', function (Blueprint $table) {
                $table->dropColumn('risk_score');
            });
        }

        Schema::dropIfExists('monitoring_reverb_metrics');
        Schema::dropIfExists('monitoring_capacity_snapshots');
        Schema::dropIfExists('monitoring_backups');
        Schema::dropIfExists('monitoring_incidents');
        Schema::dropIfExists('monitoring_deployments');
    }
};
