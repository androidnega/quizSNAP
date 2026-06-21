<?php

namespace Tests\Unit;

use App\Models\MonitoringUserSession;
use App\Services\Monitoring\SessionTerminationService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SessionTerminationServiceTest extends TestCase
{
    public function test_terminates_file_backed_session(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('monitoring_user_sessions')) {
            $this->markTestSkipped('monitoring_user_sessions table not available.');
        }

        config(['session.driver' => 'file', 'session.files' => storage_path('framework/sessions')]);

        $sessionId = 'test-session-'.uniqid();
        $path = storage_path('framework/sessions/'.$sessionId);
        File::put($path, 'test-payload');

        MonitoringUserSession::unguard();
        $record = MonitoringUserSession::query()->create([
            'session_id' => $sessionId,
            'user_id' => 1,
            'user_name' => 'Test User',
            'user_role' => 'super_admin',
            'ip_address' => '127.0.0.1',
            'is_active' => true,
            'last_activity_at' => now(),
        ]);
        MonitoringUserSession::reguard();

        $service = app(SessionTerminationService::class);
        $result = $service->terminate($sessionId);

        $this->assertTrue($result);
        $this->assertFalse(File::exists($path));
        $this->assertFalse($record->fresh()->is_active);
    }
}
