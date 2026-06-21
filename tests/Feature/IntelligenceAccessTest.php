<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class IntelligenceAccessTest extends TestCase
{
    public function test_user_can_access_intelligence_only_for_allowed_roles(): void
    {
        $superAdmin = new User(['role' => User::ROLE_SUPER_ADMIN]);
        $systemAdmin = new User(['role' => User::ROLE_SYSTEM_ADMIN]);
        $examiner = new User(['role' => User::ROLE_EXAMINER]);

        $this->assertFalse($superAdmin->canAccessIntelligence());
        $this->assertTrue($systemAdmin->canAccessIntelligence());
        $this->assertFalse($examiner->canAccessIntelligence());
    }
}
