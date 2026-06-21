<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('system_errors')) {
            $this->addIndexIfMissing('system_errors', ['last_seen_at'], 'system_errors_last_seen_at_perf_index');
        }

        if (Schema::hasTable('api_request_logs')) {
            $this->addIndexIfMissing('api_request_logs', ['occurred_at', 'status_code'], 'api_request_logs_occurred_status_index');
        }

        if (Schema::hasTable('security_events')) {
            $this->addIndexIfMissing('security_events', ['severity', 'occurred_at'], 'security_events_severity_occurred_index');
            if (Schema::hasColumn('security_events', 'risk_score')) {
                $this->addIndexIfMissing('security_events', ['risk_score'], 'security_events_risk_score_index');
            }
        }

        if (Schema::hasTable('monitoring_user_sessions') && Schema::hasColumn('monitoring_user_sessions', 'user_id')) {
            $this->addIndexIfMissing('monitoring_user_sessions', ['user_id', 'is_active'], 'monitoring_user_sessions_user_active_index');
        }
    }

    public function down(): void
    {
        // Non-destructive performance migration.
    }

    protected function addIndexIfMissing(string $table, array $columns, string $indexName): void
    {
        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
        }

        if ($this->hasIndex($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
            $blueprint->index($columns, $indexName);
        });
    }

    protected function hasIndex(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = $connection->select("PRAGMA index_list('{$table}')");

            return collect($indexes)->contains(fn ($index) => $index->name === $indexName);
        }

        if ($driver === 'mysql') {
            $database = $connection->getDatabaseName();
            $result = $connection->select(
                'SELECT COUNT(*) AS aggregate FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
                [$database, $table, $indexName]
            );

            return (int) ($result[0]->aggregate ?? 0) > 0;
        }

        return false;
    }
};
