<?php

namespace Tests\Unit;

use App\Services\Monitoring\MonitoringChartsService;
use Tests\TestCase;

class MonitoringChartsServiceTest extends TestCase
{
    public function test_all_charts_returns_expected_keys(): void
    {
        $service = app(MonitoringChartsService::class);
        $charts = $service->allCharts('24h');

        $this->assertArrayHasKey('errors', $charts);
        $this->assertArrayHasKey('requests', $charts);
        $this->assertArrayHasKey('security', $charts);
        $this->assertArrayHasKey('quiz', $charts);

        foreach ($charts as $chart) {
            $this->assertArrayHasKey('labels', $chart);
            $this->assertArrayHasKey('values', $chart);
        }
    }

    public function test_chart_data_supports_all_periods(): void
    {
        $service = app(MonitoringChartsService::class);

        foreach (['24h', '7d', '30d', '90d'] as $period) {
            $data = $service->chartData('errors', $period);
            $this->assertIsArray($data['labels']);
            $this->assertIsArray($data['values']);
        }
    }
}
