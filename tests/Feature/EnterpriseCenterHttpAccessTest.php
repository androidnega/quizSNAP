<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\EnterpriseCenterAccess;
use Illuminate\Http\Request;
use Tests\TestCase;

class EnterpriseCenterHttpAccessTest extends TestCase
{
    public function test_enterprise_center_routes_use_web_middleware(): void
    {
        foreach ([
            'dashboard.monitoring.overview',
            'dashboard.operations.index',
            'dashboard.intelligence.index',
        ] as $name) {
            $route = app('router')->getRoutes()->getByName($name);
            $this->assertNotNull($route, "Missing route: {$name}");
            $this->assertContains('web', $route->gatherMiddleware(), "Route {$name} must use web middleware for sessions");
        }
    }

    public function test_system_administrator_routes_include_enterprise_centers(): void
    {
        $request = Request::create('/dashboard/monitoring', 'GET');
        $request->setRouteResolver(function () {
            $route = app('router')->getRoutes()->getByName('dashboard.monitoring.overview');

            return $route;
        });

        $this->assertTrue(EnterpriseCenterAccess::systemMonitorRouteAllowed($request));

        $operations = Request::create('/dashboard/operations', 'GET');
        $operations->setRouteResolver(fn () => app('router')->getRoutes()->getByName('dashboard.operations.index'));
        $this->assertTrue(EnterpriseCenterAccess::systemMonitorRouteAllowed($operations));

        $intelligence = Request::create('/dashboard/intelligence', 'GET');
        $intelligence->setRouteResolver(fn () => app('router')->getRoutes()->getByName('dashboard.intelligence.index'));
        $this->assertTrue(EnterpriseCenterAccess::systemMonitorRouteAllowed($intelligence));
    }

    public function test_system_administrator_and_super_admin_can_access_enterprise_centers(): void
    {
        $systemAdmin = new User(['role' => User::ROLE_SYSTEM_ADMIN]);
        $superAdmin = new User(['role' => User::ROLE_SUPER_ADMIN]);

        foreach ([$systemAdmin, $superAdmin] as $user) {
            $this->assertTrue($user->canAccessMonitoring(), $user->role);
            $this->assertTrue($user->canAccessOperations(), $user->role);
            $this->assertTrue($user->canAccessIntelligence(), $user->role);
        }
    }
}
