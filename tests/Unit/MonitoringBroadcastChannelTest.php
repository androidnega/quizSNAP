<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;

class MonitoringBroadcastChannelTest extends TestCase
{
    public function test_monitoring_channel_allows_system_admin(): void
    {
        $user = new User(['role' => User::ROLE_SYSTEM_ADMIN]);
        $this->assertTrue($user->canAccessEnterpriseBroadcasting());
    }

    public function test_monitoring_channel_denies_examiner(): void
    {
        $user = new User(['role' => User::ROLE_EXAMINER]);
        $this->assertFalse($user->canAccessEnterpriseBroadcasting());
    }

    public function test_monitoring_channel_denies_super_admin(): void
    {
        $user = new User(['role' => User::ROLE_SUPER_ADMIN]);
        $this->assertFalse($user->canAccessEnterpriseBroadcasting());
    }

    public function test_monitoring_channel_denies_coordinator(): void
    {
        $user = new User(['role' => User::ROLE_COORDINATOR]);
        $this->assertFalse($user->canAccessEnterpriseBroadcasting());
    }

    /** @deprecated kept for backwards compatibility with channel naming */
    public function test_monitoring_channel_allows_system_admin_legacy_callback(): void
    {
        $user = new User(['role' => User::ROLE_SYSTEM_ADMIN]);
        $callback = function ($callbackUser) {
            return $callbackUser instanceof User && $callbackUser->canAccessMonitoring();
        };

        $this->assertTrue($callback($user));
    }

    /** @deprecated kept for backwards compatibility with channel naming */
    public function test_monitoring_channel_denies_examiner_legacy_callback(): void
    {
        $user = new User(['role' => User::ROLE_EXAMINER]);
        $callback = function ($callbackUser) {
            return $callbackUser instanceof User && $callbackUser->canAccessMonitoring();
        };

        $this->assertFalse($callback($user));
    }
}
