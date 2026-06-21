<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;

class OperationsBroadcastChannelTest extends TestCase
{
    public function test_operations_channel_allows_system_admin(): void
    {
        $user = new User(['role' => User::ROLE_SYSTEM_ADMIN]);
        $callback = fn ($u) => $u instanceof User && $u->canAccessOperations();

        $this->assertTrue($callback($user));
    }
}
