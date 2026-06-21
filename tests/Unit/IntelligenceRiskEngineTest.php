<?php

namespace Tests\Unit;

use App\Services\Intelligence\IntelligenceRiskEngine;
use Tests\TestCase;

class IntelligenceRiskEngineTest extends TestCase
{
    public function test_composite_score_and_risk_levels(): void
    {
        $engine = app(IntelligenceRiskEngine::class);

        $score = $engine->compositeScore([
            'performance' => 80,
            'attendance' => 70,
            'engagement' => 60,
            'participation' => 50,
        ]);

        $this->assertGreaterThan(50, $score);
        $this->assertContains($engine->riskFromScore($score), [
            IntelligenceRiskEngine::LEVEL_LOW,
            IntelligenceRiskEngine::LEVEL_MEDIUM,
        ]);

        $pass = $engine->predictPassProbability(75, 80, 70);
        $this->assertGreaterThan(50, $pass);
        $this->assertSame(round(100 - $pass, 1), $engine->predictFailProbability($pass));
    }
}
