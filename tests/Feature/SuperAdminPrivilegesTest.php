<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class SuperAdminPrivilegesTest extends TestCase
{
    public function test_all_super_admins_can_access_enterprise_centers(): void
    {
        $first = new User(['role' => User::ROLE_SUPER_ADMIN]);
        $second = new User(['role' => User::ROLE_LEGACY_ADMIN]);

        $this->assertTrue($first->canAccessMonitoring());
        $this->assertTrue($second->canAccessMonitoring());
        $this->assertTrue($first->canAccessOperations());
        $this->assertTrue($second->canAccessIntelligence());
    }

    public function test_super_admin_creatable_roles_include_admin_and_system_monitor(): void
    {
        $roles = User::superAdminCreatableRoles();

        $this->assertArrayHasKey(User::ROLE_SUPER_ADMIN, $roles);
        $this->assertArrayHasKey(User::ROLE_SYSTEM_ADMIN, $roles);
    }
}
