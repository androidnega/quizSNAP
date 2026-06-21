<?php

use App\Http\Controllers\Admin\Monitoring\MonitoringActivityController;
use App\Http\Controllers\Admin\Monitoring\MonitoringErrorController;
use App\Http\Controllers\Admin\Monitoring\MonitoringInfrastructureController;
use App\Http\Controllers\Admin\Monitoring\MonitoringLiveController;
use App\Http\Controllers\Admin\Monitoring\MonitoringNotificationController;
use App\Http\Controllers\Admin\Monitoring\MonitoringOpsController;
use App\Http\Controllers\Admin\Monitoring\MonitoringOverviewController;
use App\Http\Controllers\Admin\Monitoring\MonitoringQueueController;
use App\Http\Controllers\Admin\Monitoring\MonitoringSecurityController;
use App\Http\Controllers\Admin\Monitoring\MonitoringMaintenanceController;
use App\Http\Controllers\Admin\Monitoring\MonitoringSettingsController;
use App\Http\Controllers\Admin\Monitoring\MonitoringStudentActivityController;
use Illuminate\Support\Facades\Route;

Route::middleware(['admin.auth', 'monitoring.access'])->prefix('dashboard/monitoring')->name('dashboard.monitoring.')->group(function () {
    Route::get('/', [MonitoringOverviewController::class, 'index'])->name('overview');
    Route::get('/overview', [MonitoringOverviewController::class, 'index'])->name('overview.alias');
    Route::get('/live-stats', [MonitoringOverviewController::class, 'liveStats'])->name('live-stats');
    Route::get('/charts', [MonitoringOverviewController::class, 'charts'])->name('charts');
    Route::get('/live-events', [MonitoringOverviewController::class, 'liveEvents'])->name('live-events');

    Route::get('/live-quiz-monitor', [MonitoringLiveController::class, 'quizMonitor'])->name('live-quiz.index');
    Route::get('/live-quiz-monitor/live', [MonitoringLiveController::class, 'quizLive'])->name('live-quiz.live');
    Route::get('/live-attendance-monitor', [MonitoringLiveController::class, 'attendanceMonitor'])->name('live-attendance.index');
    Route::get('/live-attendance-monitor/live', [MonitoringLiveController::class, 'attendanceLive'])->name('live-attendance.live');

    Route::get('/command-center', [MonitoringOpsController::class, 'commandCenter'])->name('command-center.index');
    Route::get('/command-center/live', [MonitoringOpsController::class, 'commandCenterLive'])->name('command-center.live');

    Route::get('/backups', [MonitoringOpsController::class, 'backups'])->name('backups.index');
    Route::post('/backups/scan', [MonitoringOpsController::class, 'scanBackups'])->name('backups.scan');

    Route::get('/deployments', [MonitoringOpsController::class, 'deployments'])->name('deployments.index');
    Route::post('/deployments', [MonitoringOpsController::class, 'recordDeployment'])->name('deployments.store');

    Route::get('/incidents', [MonitoringOpsController::class, 'incidents'])->name('incidents.index');
    Route::post('/incidents', [MonitoringOpsController::class, 'storeIncident'])->name('incidents.store');
    Route::post('/incidents/{incident}/resolve', [MonitoringOpsController::class, 'resolveIncident'])->name('incidents.resolve');

    Route::get('/database-capacity', [MonitoringOpsController::class, 'databaseCapacity'])->name('capacity.database');
    Route::get('/storage-capacity', [MonitoringOpsController::class, 'storageCapacity'])->name('capacity.storage');

    Route::get('/errors', [MonitoringErrorController::class, 'index'])->name('errors.index');
    Route::get('/errors/feed', [MonitoringErrorController::class, 'feed'])->name('errors.feed');
    Route::get('/errors/{error}', [MonitoringErrorController::class, 'show'])->name('errors.show');
    Route::post('/errors/{error}/resolve', [MonitoringErrorController::class, 'resolve'])->name('errors.resolve');
    Route::post('/errors/{error}/ignore', [MonitoringErrorController::class, 'ignore'])->name('errors.ignore');

    Route::get('/activity', [MonitoringActivityController::class, 'index'])->name('activity.index');

    Route::get('/failed-jobs', [MonitoringQueueController::class, 'failedJobs'])->name('failed-jobs.index');
    Route::get('/queue-monitor', [MonitoringQueueController::class, 'index'])->name('queue.index');
    Route::post('/queue/retry', [MonitoringQueueController::class, 'retry'])->name('queue.retry');
    Route::post('/queue/retry-all', [MonitoringQueueController::class, 'retryAll'])->name('queue.retry-all');
    Route::post('/queue/delete', [MonitoringQueueController::class, 'delete'])->name('queue.delete');
    Route::post('/queue/delete-all', [MonitoringQueueController::class, 'deleteAll'])->name('queue.delete-all');

    Route::get('/api', [MonitoringInfrastructureController::class, 'api'])->name('api.index');
    Route::get('/database', [MonitoringInfrastructureController::class, 'database'])->name('database.index');
    Route::get('/performance', [MonitoringInfrastructureController::class, 'performance'])->name('performance.index');
    Route::get('/server-health', [MonitoringInfrastructureController::class, 'serverHealth'])->name('server-health.index');
    Route::get('/websocket-monitor', [MonitoringInfrastructureController::class, 'websocket'])->name('websocket.index');

    Route::get('/security', [MonitoringSecurityController::class, 'security'])->name('security.index');
    Route::get('/audit-trail', [MonitoringSecurityController::class, 'auditTrail'])->name('audit-trail.index');
    Route::get('/user-sessions', [MonitoringSecurityController::class, 'sessions'])->name('sessions.index');
    Route::post('/user-sessions/terminate', [MonitoringSecurityController::class, 'terminateSession'])->name('sessions.terminate');
    Route::post('/user-sessions/force-logout', [MonitoringSecurityController::class, 'forceLogout'])->name('sessions.force-logout');

    Route::get('/notifications', [MonitoringNotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [MonitoringNotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::get('/notifications/recent', [MonitoringNotificationController::class, 'recent'])->name('notifications.recent');
    Route::post('/notifications/read', [MonitoringNotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [MonitoringNotificationController::class, 'markAllRead'])->name('notifications.read-all');

    Route::get('/settings', [MonitoringSettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [MonitoringSettingsController::class, 'update'])->name('settings.update');

    Route::get('/student-activities', [MonitoringStudentActivityController::class, 'index'])->name('student-activities.index');

    Route::post('/maintenance/clear-logs', [MonitoringMaintenanceController::class, 'clearLogs'])->name('maintenance.clear-logs');
    Route::get('/errors/export', [MonitoringMaintenanceController::class, 'exportErrors'])->name('errors.export');
    Route::get('/errors/{error}/export', [MonitoringMaintenanceController::class, 'exportError'])->name('errors.export.single');
});

Route::redirect('/admin/monitoring', '/dashboard/monitoring');
Route::redirect('/admin/monitoring/{path}', '/dashboard/monitoring/{path}')->where('path', '.*');
