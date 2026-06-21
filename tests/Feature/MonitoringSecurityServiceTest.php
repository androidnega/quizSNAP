<?php

namespace Tests\Feature;

use App\Services\Monitoring\IncidentManagementService;
use App\Services\Monitoring\SecurityMonitoringService;
use Tests\TestCase;

class MonitoringSecurityServiceTest extends TestCase
{
    public function test_security_service_records_events_with_risk_score(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('security_events')) {
            $this->markTestSkipped('security_events table not available.');
        }

        $service = app(SecurityMonitoringService::class);
        $event = $service->record('permission_denied', 'Access denied to route', 'critical');

        $this->assertNotNull($event->risk_score);
        $this->assertGreaterThan(0, $event->risk_score);
    }

    public function test_incident_service_creates_incident(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('monitoring_incidents')) {
            $this->markTestSkipped('monitoring_incidents table not available.');
        }

        $service = app(IncidentManagementService::class);
        $incident = $service->create([
            'title' => 'Test outage',
            'severity' => 'P2',
            'affected_services' => ['api'],
        ]);

        $this->assertSame('Test outage', $incident->title);
        $this->assertSame('P2', $incident->severity);
        $this->assertSame('open', $incident->status);
    }
}
