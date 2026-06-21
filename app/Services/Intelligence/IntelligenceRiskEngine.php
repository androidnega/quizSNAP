<?php

namespace App\Services\Intelligence;

class IntelligenceRiskEngine
{
    public const LEVEL_LOW = 'low';
    public const LEVEL_MEDIUM = 'medium';
    public const LEVEL_HIGH = 'high';
    public const LEVEL_CRITICAL = 'critical';

    public function compositeScore(array $components): int
    {
        $weights = [
            'performance' => 0.30,
            'attendance' => 0.25,
            'engagement' => 0.20,
            'integrity' => 0.15,
            'participation' => 0.10,
        ];

        $total = 0.0;
        $weightSum = 0.0;
        foreach ($weights as $key => $weight) {
            if (isset($components[$key])) {
                $total += ((float) $components[$key]) * $weight;
                $weightSum += $weight;
            }
        }

        return (int) round($weightSum > 0 ? $total / $weightSum : 0);
    }

    public function riskFromScore(int $score): string
    {
        return match (true) {
            $score >= 75 => self::LEVEL_LOW,
            $score >= 50 => self::LEVEL_MEDIUM,
            $score >= 30 => self::LEVEL_HIGH,
            default => self::LEVEL_CRITICAL,
        };
    }

    public function riskScoreFromComponents(array $components): int
    {
        $success = $this->compositeScore($components);

        return max(0, min(100, 100 - $success));
    }

    public function trend(array $values): string
    {
        if (count($values) < 2) {
            return 'stable';
        }

        $first = array_slice($values, 0, (int) ceil(count($values) / 2));
        $second = array_slice($values, (int) floor(count($values) / 2));
        $avgFirst = array_sum($first) / max(1, count($first));
        $avgSecond = array_sum($second) / max(1, count($second));
        $delta = $avgSecond - $avgFirst;

        if ($delta > 5) {
            return 'improving';
        }
        if ($delta < -5) {
            return 'declining';
        }

        return 'stable';
    }

    public function predictPassProbability(float $avgScore, float $participationRate, float $attendanceRate): float
    {
        $score = ($avgScore * 0.5) + ($participationRate * 0.25) + ($attendanceRate * 0.25);

        return round(max(0, min(100, $score)), 1);
    }

    public function predictFailProbability(float $passProbability): float
    {
        return round(100 - $passProbability, 1);
    }
}
