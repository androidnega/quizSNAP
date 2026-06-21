<?php

namespace Tests\Unit;

use App\Services\Monitoring\ErrorMonitoringService;
use Tests\TestCase;

class ErrorMonitoringServiceTest extends TestCase
{
    public function test_classifies_validation_exception_as_warning(): void
    {
        $service = app(ErrorMonitoringService::class);
        $exception = \Illuminate\Validation\ValidationException::withMessages(['email' => ['Invalid']]);

        $this->assertSame('warning', $service->classifySeverity($exception));
    }

    public function test_classifies_pdo_exception_as_critical(): void
    {
        $service = app(ErrorMonitoringService::class);

        $this->assertSame('critical', $service->classifySeverity(new \PDOException('connection failed')));
    }

    public function test_builds_stable_fingerprint(): void
    {
        $service = app(ErrorMonitoringService::class);
        $exception = new \RuntimeException('Test error', 500);

        $a = $service->buildFingerprint($exception);
        $b = $service->buildFingerprint($exception);

        $this->assertSame($a, $b);
        $this->assertSame(64, strlen($a));
    }
}
