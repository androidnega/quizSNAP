<?php

namespace App\Providers;

use App\Models\AttendanceUploadLog;
use App\Models\Course;
use App\Models\Department;
use App\Models\ExamCalendar;
use App\Models\Quiz;
use App\Models\Setting;
use App\Models\User;
use App\Observers\AttendanceUploadAuditObserver;
use App\Observers\CourseAuditObserver;
use App\Observers\DepartmentAuditObserver;
use App\Observers\ExamCalendarAuditObserver;
use App\Observers\QuizAuditObserver;
use App\Observers\SettingAuditObserver;
use App\Observers\UserAuditObserver;
use App\Services\Monitoring\DatabaseMonitoringService;
use App\Services\Monitoring\ReverbAnalyticsService;
use App\Services\Monitoring\SecurityMonitoringService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class MonitoringServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Quiz::observe(QuizAuditObserver::class);
        User::observe(UserAuditObserver::class);
        Course::observe(CourseAuditObserver::class);
        Setting::observe(SettingAuditObserver::class);
        Department::observe(DepartmentAuditObserver::class);
        ExamCalendar::observe(ExamCalendarAuditObserver::class);
        AttendanceUploadLog::observe(AttendanceUploadAuditObserver::class);

        if ($this->app->runningInConsole()) {
            return;
        }

        app(DatabaseMonitoringService::class)->registerListeners();

        Event::listen(\Illuminate\Auth\Events\Failed::class, function ($event) {
            $identifier = $event->credentials['username'] ?? $event->credentials['email'] ?? 'unknown';
            app(SecurityMonitoringService::class)->recordFailedLogin($identifier);
        });

        foreach ([
            \App\Events\Monitoring\MonitoringErrorOccurred::class,
            \App\Events\Monitoring\MonitoringActivityLogged::class,
            \App\Events\Monitoring\MonitoringHealthChanged::class,
            \App\Events\Monitoring\MonitoringQueueChanged::class,
            \App\Events\Monitoring\MonitoringNotificationCreated::class,
            \App\Events\Monitoring\MonitoringSecurityEventOccurred::class,
            \App\Events\Monitoring\MonitoringSlowQueryDetected::class,
            \App\Events\Monitoring\MonitoringLiveQuizUpdated::class,
            \App\Events\Monitoring\MonitoringLiveAttendanceUpdated::class,
            \App\Events\Monitoring\MonitoringCommandCenterUpdated::class,
        ] as $eventClass) {
            Event::listen($eventClass, function () {
                app(ReverbAnalyticsService::class)->recordBroadcast(class_basename(func_get_args()[0] ?? 'event'));
            });
        }
    }
}
