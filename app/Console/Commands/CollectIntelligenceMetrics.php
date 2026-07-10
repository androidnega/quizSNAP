<?php

namespace App\Console\Commands;

use App\Services\Intelligence\IntelligenceAnomalyDetectionService;
use App\Services\Intelligence\IntelligenceEarlyWarningService;
use App\Services\Intelligence\IntelligenceExecutiveDashboardService;
use App\Services\Intelligence\IntelligenceRecommendationEngine;
use Illuminate\Console\Command;
use Throwable;

class CollectIntelligenceMetrics extends Command
{
    protected $signature = 'intelligence:collect-metrics';

    protected $description = 'Run intelligence engines, early warnings, anomalies, and broadcast dashboard updates';

    public function handle(): int
    {
        foreach ([
            [IntelligenceRecommendationEngine::class, 'generate'],
            [IntelligenceEarlyWarningService::class, 'scan'],
            [IntelligenceAnomalyDetectionService::class, 'detect'],
            [IntelligenceExecutiveDashboardService::class, 'broadcast'],
        ] as [$service, $method]) {
            try {
                app($service)->{$method}();
            } catch (Throwable $e) {
                report($e);
            }
        }

        $this->info('Intelligence metrics collected and broadcast.');

        return self::SUCCESS;
    }
}
