<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;

class MonitoringBroadcastChannelTest extends TestCase
{
    public function test_monitoring_channel_allows_system_admin(): void
    {
        $user = new User(['role' => User::ROLE_SYSTEM_ADMIN]);
        $callback = function ($callbackUser) {
            return $callbackUser instanceof User && $callbackUser->canAccessMonitoring();
        };

        $this->assertTrue($callback($user));
    }

    public function test_monitoring_channel_denies_examiner(): void
    {
        $user = new User(['role' => User::ROLE_EXAMINER]);
        $callback = function ($callbackUser) {
            return $callbackUser instanceof User && $callbackUser->canAccessMonitoring();
        };

        $this->assertFalse($callback($user));
    }
}
