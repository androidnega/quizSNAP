<?php

namespace App\Services\Monitoring;

use App\Models\MonitoringReverbMetric;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ReverbAnalyticsService
{
    public function recordBroadcast(string $eventName, bool $success = true, ?int $latencyMs = null): void
    {
        $minute = now()->format('Y-m-d-H-i');
        $key = "monitoring:reverb:minute:{$minute}";

        $stats = Cache::get($key, [
            'messages' => 0,
            'events' => 0,
            'failed' => 0,
            'latency_total' => 0,
            'latency_count' => 0,
        ]);

        $stats['messages']++;
        $stats['events']++;
        if (! $success) {
            $stats['failed']++;
        }
        if ($latencyMs !== null) {
            $stats['latency_total'] += $latencyMs;
            $stats['latency_count']++;
        }

        Cache::put($key, $stats, now()->addMinutes(10));
        Cache::increment('monitoring:reverb:connected_channels', 1);
    }

    public function recordConnectionFailure(): void
    {
        Cache::increment('monitoring:reverb:connection_failures:'.now()->format('Y-m-d-H-i'));
    }

    public function snapshot(): array
    {
        if (! Schema::hasTable('monitoring_reverb_metrics')) {
            return $this->emptyMetrics();
        }

        $latest = MonitoringReverbMetric::query()->latest('recorded_at')->first();
        if ($latest && $latest->recorded_at?->gt(now()->subMinutes(2))) {
            return $latest->only([
                'connected_users', 'connected_channels', 'messages_per_minute',
                'events_per_minute', 'failed_broadcasts', 'connection_failures',
                'average_latency_ms', 'broadcast_queue_delay_ms', 'health_score', 'recorded_at',
            ]);
        }

        return $this->collectAndPersist();
    }

    public function collectAndPersist(): array
    {
        $minute = now()->format('Y-m-d-H-i');
        $stats = Cache::get("monitoring:reverb:minute:{$minute}", []);
        $connectedUsers = app(SessionMonitoringService::class)->activeCount();
        $failed = (int) ($stats['failed'] ?? 0);
        $messages = (int) ($stats['messages'] ?? 0);
        $latencyCount = (int) ($stats['latency_count'] ?? 0);
        $avgLatency = $latencyCount > 0 ? (int) round($stats['latency_total'] / $latencyCount) : null;

        $healthScore = max(0, min(100, 100 - ($failed * 5) - (max(0, 100 - ($avgLatency ? min($avgLatency, 100) : 100)))));

        $metric = MonitoringReverbMetric::query()->create([
            'connected_users' => $connectedUsers,
            'connected_channels' => 2,
            'messages_per_minute' => $messages,
            'events_per_minute' => (int) ($stats['events'] ?? 0),
            'failed_broadcasts' => $failed,
            'connection_failures' => (int) Cache::get('monitoring:reverb:connection_failures:'.$minute, 0),
            'average_latency_ms' => $avgLatency,
            'broadcast_queue_delay_ms' => null,
            'health_score' => $healthScore,
            'recorded_at' => now(),
        ]);

        return $metric->toArray();
    }

    protected function emptyMetrics(): array
    {
        return [
            'connected_users' => 0,
            'connected_channels' => 0,
            'messages_per_minute' => 0,
            'events_per_minute' => 0,
            'failed_broadcasts' => 0,
            'connection_failures' => 0,
            'average_latency_ms' => null,
            'broadcast_queue_delay_ms' => null,
            'health_score' => 100,
            'recorded_at' => now()->toIso8601String(),
        ];
    }
}
