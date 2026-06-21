<?php

namespace App\Console\Commands;

use App\Services\Intelligence\IntelligenceAnomalyDetectionService;
use App\Services\Intelligence\IntelligenceEarlyWarningService;
use App\Services\Intelligence\IntelligenceExecutiveDashboardService;
use App\Services\Intelligence\IntelligenceRecommendationEngine;
use Illuminate\Console\Command;

class CollectIntelligenceMetrics extends Command
{
    protected $signature = 'intelligence:collect-metrics';

    protected $description = 'Run intelligence engines, early warnings, anomalies, and broadcast dashboard updates';

    public function handle(): int
    {
        app(IntelligenceRecommendationEngine::class)->generate();
        app(IntelligenceEarlyWarningService::class)->scan();
        app(IntelligenceAnomalyDetectionService::class)->detect();
        app(IntelligenceExecutiveDashboardService::class)->broadcast();

        $this->info('Intelligence metrics collected and broadcast.');

        return self::SUCCESS;
    }
}
