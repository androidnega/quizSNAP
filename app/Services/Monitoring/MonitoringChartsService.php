<?php

namespace App\Services\Monitoring;

use App\Models\ApiRequestLog;
use App\Models\AttendanceUploadLog;
use App\Models\AuthAuditLog;
use App\Models\DatabaseQueryLog;
use App\Models\PerformanceLog;
use App\Models\SecurityEvent;
use App\Models\ServerHealthSnapshot;
use App\Models\SystemErrorOccurrence;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MonitoringChartsService
{
    public function chartData(string $chart, string $period = '24h'): array
    {
        $range = $this->resolveRange($period);

        return match ($chart) {
            'errors' => $this->timeSeries('system_error_occurrences', 'occurred_at', $range),
            'requests' => $this->timeSeries('api_request_logs', 'occurred_at', $range),
            'security' => $this->timeSeries('security_events', 'occurred_at', $range, 'day'),
            'slow_queries' => $this->timeSeries('database_query_logs', 'occurred_at', $range, 'day', ['status' => 'slow']),
            'queue_jobs' => $this->queueJobsSeries($range),
            'memory' => $this->metricSeries('performance_logs', 'memory_usage_kb', 'occurred_at', $range),
            'cpu' => $this->healthMetricSeries('cpu_usage', $range),
            'storage' => $this->capacitySeries(\App\Models\MonitoringCapacitySnapshot::TYPE_STORAGE, $range),
            'attendance' => $this->attendanceSeries($range),
            'quiz' => $this->quizActivitySeries($range),
            default => ['labels' => [], 'values' => []],
        };
    }

    public function allCharts(string $period = '24h'): array
    {
        $keys = ['errors', 'requests', 'security', 'slow_queries', 'queue_jobs', 'memory', 'cpu', 'storage', 'attendance', 'quiz'];

        $data = [];
        foreach ($keys as $key) {
            $data[$key] = $this->chartData($key, $period);
        }

        return $data;
    }

    protected function resolveRange(string $period): array
    {
        return match ($period) {
            '7d' => [now()->subDays(7), 'day'],
            '30d' => [now()->subDays(30), 'day'],
            '90d' => [now()->subDays(90), 'day'],
            default => [now()->subHours(24), 'hour'],
        };
    }

    protected function bucketExpression(string $column, string $bucket): string
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            return $bucket === 'hour'
                ? "strftime('%Y-%m-%d %H:00', {$column})"
                : "strftime('%Y-%m-%d', {$column})";
        }

        $format = $bucket === 'hour' ? '%Y-%m-%d %H:00' : '%Y-%m-%d';

        return "DATE_FORMAT({$column}, '{$format}')";
    }

    protected function unixBucketExpression(string $column, string $bucket): string
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            return $bucket === 'hour'
                ? "strftime('%Y-%m-%d %H:00', datetime({$column}, 'unixepoch'))"
                : "strftime('%Y-%m-%d', datetime({$column}, 'unixepoch'))";
        }

        $format = $bucket === 'hour' ? '%Y-%m-%d %H:00' : '%Y-%m-%d';

        return "DATE_FORMAT(FROM_UNIXTIME({$column}), '{$format}')";
    }

    protected function timeSeries(string $table, string $column, array $range, ?string $bucket = null, array $where = []): array
    {
        if (! Schema::hasTable($table)) {
            return ['labels' => [], 'values' => []];
        }

        [$since, $defaultBucket] = $range;
        $bucket = $bucket ?? $defaultBucket;
        $bucketSql = $this->bucketExpression($column, $bucket);

        $query = DB::table($table)
            ->selectRaw("{$bucketSql} as bucket, COUNT(*) as total")
            ->where($column, '>=', $since);

        foreach ($where as $field => $value) {
            $query->where($field, $value);
        }

        $rows = $query->groupBy('bucket')->orderBy('bucket')->get();

        return [
            'labels' => $rows->pluck('bucket')->all(),
            'values' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    protected function queueJobsSeries(array $range): array
    {
        if (! Schema::hasTable('jobs')) {
            return ['labels' => [], 'values' => []];
        }

        [$since, $bucket] = $range;
        $bucketSql = $this->unixBucketExpression('created_at', $bucket);

        $rows = DB::table('jobs')
            ->selectRaw("{$bucketSql} as bucket, COUNT(*) as total")
            ->where('created_at', '>=', $since->timestamp)
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        return [
            'labels' => $rows->pluck('bucket')->all(),
            'values' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    protected function metricSeries(string $table, string $metric, string $column, array $range): array
    {
        if (! Schema::hasTable($table)) {
            return ['labels' => [], 'values' => []];
        }

        [$since, $bucket] = $range;
        $bucketSql = $this->bucketExpression($column, $bucket);

        $rows = DB::table($table)
            ->selectRaw("{$bucketSql} as bucket, AVG({$metric}) as avg_val")
            ->where($column, '>=', $since)
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        return [
            'labels' => $rows->pluck('bucket')->all(),
            'values' => $rows->pluck('avg_val')->map(fn ($v) => round((float) $v, 2))->all(),
        ];
    }

    protected function healthMetricSeries(string $metric, array $range): array
    {
        if (! Schema::hasTable('server_health_snapshots')) {
            return ['labels' => [], 'values' => []];
        }

        [$since, $bucket] = $range;
        $bucketSql = $this->bucketExpression('recorded_at', $bucket);

        $rows = ServerHealthSnapshot::query()
            ->selectRaw("{$bucketSql} as bucket, AVG({$metric}) as avg_val")
            ->where('recorded_at', '>=', $since)
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        return [
            'labels' => $rows->pluck('bucket')->all(),
            'values' => $rows->pluck('avg_val')->map(fn ($v) => round((float) $v, 2))->all(),
        ];
    }

    protected function capacitySeries(string $type, array $range): array
    {
        if (! Schema::hasTable('monitoring_capacity_snapshots')) {
            return ['labels' => [], 'values' => []];
        }

        [$since] = $range;

        $rows = DB::table('monitoring_capacity_snapshots')
            ->selectRaw('DATE(recorded_at) as bucket, AVG(used_bytes) as avg_val')
            ->where('snapshot_type', $type)
            ->where('recorded_at', '>=', $since)
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        return [
            'labels' => $rows->pluck('bucket')->all(),
            'values' => $rows->pluck('avg_val')->map(fn ($v) => round(((float) $v) / 1048576, 2))->all(),
        ];
    }

    protected function attendanceSeries(array $range): array
    {
        if (! Schema::hasTable('attendance_upload_logs')) {
            return ['labels' => [], 'values' => []];
        }

        [$since, $bucket] = $range;
        $bucketSql = $this->bucketExpression('uploaded_at', $bucket);

        $rows = AttendanceUploadLog::query()
            ->selectRaw("{$bucketSql} as bucket, SUM(rows_added) as total")
            ->where('uploaded_at', '>=', $since)
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        return [
            'labels' => $rows->pluck('bucket')->all(),
            'values' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    protected function quizActivitySeries(array $range): array
    {
        if (! Schema::hasTable('quiz_sessions')) {
            return ['labels' => [], 'values' => []];
        }

        [$since, $bucket] = $range;
        $bucketSql = $this->bucketExpression('start_time', $bucket);

        $rows = DB::table('quiz_sessions')
            ->selectRaw("{$bucketSql} as bucket, COUNT(*) as total")
            ->whereNotNull('start_time')
            ->where('start_time', '>=', $since)
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        return [
            'labels' => $rows->pluck('bucket')->all(),
            'values' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }
}
