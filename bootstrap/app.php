<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            require base_path('routes/monitoring.php');
            require base_path('routes/operations.php');
            require base_path('routes/intelligence.php');
        },
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['middleware' => ['web', 'broadcasting.auth']],
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: []);
        $middleware->web(append: [
            \App\Http\Middleware\CheckUpdateMode::class,
            \App\Http\Middleware\MonitorHttpRequests::class,
            \App\Http\Middleware\RecordMonitoringSecurityEvents::class,
        ]);
        $middleware->alias([
            'rules.accepted' => \App\Http\Middleware\EnsureRulesAccepted::class,
            'dashboard.auth' => \App\Http\Middleware\EnsureDashboardAuthenticated::class,
            'student.auth' => \App\Http\Middleware\EnsureStudentAuthenticated::class,
            'admin.auth' => \App\Http\Middleware\EnsureAdminAuthenticated::class,
            'block.superadmin.coordinator' => \App\Http\Middleware\BlockSuperAdminFromCoordinatorLecturer::class,
            'admin.role' => \App\Http\Middleware\EnsureSuperAdminRole::class,
            'super_admin.role' => \App\Http\Middleware\EnsureSuperAdminRole::class,
            'examiner.role' => \App\Http\Middleware\EnsureExaminerRole::class,
            'examiner.only' => \App\Http\Middleware\EnsureExaminerOnlyRole::class,
            'course.creation' => \App\Http\Middleware\EnsureCourseCreationAllowed::class,
            'coordinator.only' => \App\Http\Middleware\EnsureCoordinatorRole::class,
            'staff.tokens' => \App\Http\Middleware\EnsureStaffTokenManager::class,
            'student.has-level' => \App\Http\Middleware\EnsureStudentHasLevel::class,
            'monitoring.access' => \App\Http\Middleware\EnsureMonitoringAccess::class,
            'broadcasting.auth' => \App\Http\Middleware\EnsureBroadcastingAuthenticated::class,
            'operations.access' => \App\Http\Middleware\EnsureOperationsAccess::class,
            'intelligence.access' => \App\Http\Middleware\EnsureIntelligenceAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            $message = \App\Support\UserFriendlyMessages::SESSION_EXPIRED;
            if ($request->expectsJson() || $request->ajax()) {
                return \Illuminate\Support\Facades\Response::json(['message' => $message], 419);
            }
            return redirect()
                ->to('/login')
                ->exceptInput('password', 'password_confirmation')
                ->with('error', $message);
        });

        $exceptions->reportable(function (\Throwable $e) {
            if ($e instanceof \Illuminate\Session\TokenMismatchException) {
                try {
                    app(\App\Services\Monitoring\SecurityMonitoringService::class)->recordCsrfFailure();
                } catch (\Throwable) {
                    // ignore
                }
            }

            if (! app()->runningInConsole()) {
                try {
                    app(\App\Services\Monitoring\ErrorMonitoringService::class)->capture($e, request());
                } catch (\Throwable) {
                    // never break reporting
                }
            }
        });
    })->create();
