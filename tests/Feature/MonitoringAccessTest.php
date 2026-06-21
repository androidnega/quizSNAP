<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class MonitoringAccessTest extends TestCase
{
    public function test_user_can_access_monitoring_only_for_allowed_roles(): void
    {
        $superAdmin = new User(['role' => User::ROLE_SUPER_ADMIN]);
        $systemAdmin = new User(['role' => User::ROLE_SYSTEM_ADMIN]);
        $coordinator = new User(['role' => User::ROLE_COORDINATOR]);
        $examiner = new User(['role' => User::ROLE_EXAMINER]);

        $this->assertTrue($superAdmin->canAccessMonitoring());
        $this->assertTrue($systemAdmin->canAccessMonitoring());
        $this->assertFalse($coordinator->canAccessMonitoring());
        $this->assertFalse($examiner->canAccessMonitoring());
    }

    public function test_system_administrator_role_constant_exists(): void
    {
        $this->assertSame('system_admin', User::ROLE_SYSTEM_ADMIN);
        $this->assertArrayHasKey(User::ROLE_SYSTEM_ADMIN, User::monitoringRoleLabels());
    }
}
